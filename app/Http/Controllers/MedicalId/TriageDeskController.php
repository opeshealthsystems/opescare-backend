<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\PatientRecordedVital;
use App\Models\Patient;
use App\Models\TriageRecord;
use App\Services\Portal\PortalContextService;
use App\Services\Triage\TriageDeskService;
use App\Support\StaffCapabilities;
use App\Support\TriageAcuity;
use App\Support\TriageBandContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The triage desk — the door onto a clinical schema that had none.
 *
 * `triage_records`, `triage_vital_signs`, `triage_scores`,
 * `triage_reassessments` and `triage_audits` have existed with models and
 * migrations since the clinical MVP; nothing in the product could write or read
 * one. This controller is that surface, and it is only a surface: every write
 * goes through App\Services\Triage\TriageDeskService, which owns the rules.
 *
 * ── THE TWO SAFETY DECISIONS VISIBLE HERE ──────────────────────────────────
 *
 * 1. NO DERIVED ACUITY, EVER. The form offers five priority levels and no
 *    default. The desk does show advisory per-reading flags, but only through
 *    App\Support\TriageBandContext, only when this platform actually has bands
 *    for this patient, and only labelled as advisory adult bands. There is no
 *    code path anywhere in this feature that turns a set of numbers into a
 *    priority.
 *
 * 2. HOME READINGS ARE CONTEXT, NOT OBSERVATIONS. `patient_recorded_vitals`
 *    rows are shown to the nurse — a patient's own week of blood pressures is
 *    real evidence and worth having at the bedside — under an explicit
 *    "patient-reported, not verified" heading. They are read here and passed to
 *    the view. They are never written into `triage_vital_signs`, never
 *    pre-fill the form, and never reach TriageBandContext::interpret(). The
 *    provenance split is structural (App\Enums\VitalProvenance) and this
 *    controller keeps it that way.
 *
 * ── SCOPE ──────────────────────────────────────────────────────────────────
 *
 * The facility is read from PortalContextService — the web tier's convention —
 * and never from the request. `triage_records` carries no facility_id of its
 * own, so every read joins through `visits.facility_id`: a record belonging to
 * another facility is not refused, it does not exist (404). The role gate is
 * `capability:triage` on the routes, which answers 403 `role_forbidden`.
 */
class TriageDeskController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly TriageDeskService $triage,
    ) {
    }

    /**
     * The queue: everyone triaged at this facility, MOST URGENT FIRST.
     *
     * ── Why this ordering IS the feature ─────────────────────────────────
     * Triage does not make a clinic faster; it changes who is seen first, and
     * that is the whole of its clinical value — the man with chest pain
     * assessed as P1 is not left behind forty P5 coughs. This method used to
     * order by `created_at DESC`: it performed the assessment, printed the
     * priority on every row, and then handed the nurse the arrival order
     * anyway. That is worse than having no queue at all, because it looks
     * like triage while doing the opposite of it.
     *
     * ── Why the sort is in SQL and not in PHP ────────────────────────────
     * The current priority is DERIVED: latest reassessment wins, else latest
     * score, else the acuity recorded at assessment. (This mirrors
     * TriageDeskService::currentPriority() exactly and must keep mirroring
     * it.) Sorting the paginated collection in PHP would sort each PAGE
     * independently — a P1 triaged an hour ago would sit on page two beneath
     * page one's P5s, while every page looked correctly ordered. That is not
     * a lesser fix; it is a more convincing bug. So the derivation is
     * expressed as correlated subqueries and the sort happens across the
     * whole set, before the limit.
     *
     * Ties break on WAITING TIME, oldest first: two P2s are separated by who
     * has waited longer, never by who was typed into the computer last.
     */
    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();

        // Same precedence as currentPriority(), expressed in SQL.
        $derivedPriority = '(SELECT r.new_priority FROM triage_reassessments r
                 WHERE r.triage_assessment_id = triage_records.id
                 ORDER BY r.reassessed_at DESC, r.created_at DESC LIMIT 1)';
        $scoredPriority = '(SELECT s.priority_level FROM triage_scores s
                 WHERE s.triage_assessment_id = triage_records.id
                 ORDER BY s.scored_at DESC, s.created_at DESC LIMIT 1)';

        $current = "COALESCE({$derivedPriority}, {$scoredPriority}, triage_records.acuity_score)";

        // Ranked explicitly, never alphabetically. 'P1_immediate' happens to
        // sort before 'P2_urgent', but an untriaged or unrecognised value must
        // not inherit that luck — it ranks last for urgency yet stays IN the
        // queue, because a patient with no priority is not a patient with no
        // problem.
        $rank = "CASE {$current}"
            . " WHEN '" . TriageAcuity::P1 . "' THEN 1"
            . " WHEN '" . TriageAcuity::P2 . "' THEN 2"
            . " WHEN '" . TriageAcuity::P3 . "' THEN 3"
            . " WHEN '" . TriageAcuity::P4 . "' THEN 4"
            . " WHEN '" . TriageAcuity::P5 . "' THEN 5"
            . ' ELSE 6 END';

        $records = $this->scoped()
            ->with(['visit.patient', 'nurse'])
            ->orderByRaw($rank)
            ->orderBy('triage_records.created_at')
            ->paginate(25)
            ->withQueryString();

        $rows = $records->getCollection()->map(fn (TriageRecord $record) => [
            'record'   => $record,
            'priority' => $this->triage->currentPriority($record),
        ]);

        return view('portals.triage.index', [
            'records'    => $records,
            'rows'       => $rows,
            'facilityId' => $facilityId,
        ]);
    }

    /**
     * The assessment form.
     *
     * The patient arrives by EXACT Health ID — no name search, no partial
     * match, no listing. Same rule as the prescriber desk and the staff desk:
     * this platform does not let a professional enumerate patients.
     */
    public function create(Request $request): View
    {
        $this->facilityId();

        $patient  = null;
        $notFound = false;

        if ($healthId = trim((string) $request->input('health_id'))) {
            // resolveHealthId follows a merge, so a retired Health ID opens the
            // surviving record rather than an empty shell.
            $patient  = Patient::resolveHealthId($healthId);
            $notFound = $patient === null;
        }

        $bands = $patient
            ? TriageBandContext::for($patient->date_of_birth, null)
            : null;

        return view('portals.triage.create', [
            'patient'         => $patient,
            'notFound'        => $notFound,
            'bands'           => $bands,
            'acuityLevels'    => TriageAcuity::LEVELS,
            'consciousness'   => TriageDeskService::CONSCIOUSNESS_LEVELS,
            'pregnancyStatus' => TriageBandContext::PREGNANCY_STATUSES,
            'selfReported'    => $patient ? $this->selfReported($patient->id) : collect(),
        ]);
    }

    /** Record the assessment. */
    public function store(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'patient_id'           => 'required|uuid',
            'presenting_complaint' => 'required|string|max:2000',
            'pain_score'           => 'nullable|integer|min:0|max:10',
            'pregnancy_status'     => ['nullable', 'string', 'in:' . implode(',', TriageBandContext::PREGNANCY_STATUSES)],
            // No default and no "suggested" value: the nurse chooses, or the
            // form does not submit.
            'acuity'               => ['required', 'string', 'in:' . implode(',', TriageAcuity::LEVELS)],

            'temperature'         => 'nullable|numeric|min:20|max:45',
            'pulse_rate'          => 'nullable|integer|min:10|max:300',
            'respiratory_rate'    => 'nullable|integer|min:2|max:99',
            'systolic_bp'         => 'nullable|integer|min:40|max:300',
            'diastolic_bp'        => 'nullable|integer|min:20|max:200',
            'oxygen_saturation'   => 'nullable|integer|min:20|max:100',
            'weight_kg'           => 'nullable|numeric|min:0.3|max:400',
            'height_cm'           => 'nullable|numeric|min:20|max:260',
            'gcs_score'           => 'nullable|integer|min:3|max:15',
            'consciousness_level' => ['nullable', 'string', 'in:' . implode(',', TriageDeskService::CONSCIOUSNESS_LEVELS)],
        ]);

        $patient = Patient::resolveId($validated['patient_id']);

        if ($patient === null) {
            return back()->withInput()->with('error', __('triage.not_found'));
        }

        $visit = $this->triage->openVisitFor($patient->id, $facilityId, auth()->id());

        $record = $this->triage->record([
            'visit'                => $visit,
            'nurse_id'             => (string) auth()->id(),
            'nurse_role'           => $this->actorRole(),
            'presenting_complaint' => $validated['presenting_complaint'],
            'pain_score'           => $validated['pain_score'] ?? null,
            'pregnancy_status'     => $validated['pregnancy_status'] ?? null,
            'acuity'               => $validated['acuity'],
            // Only the fields the nurse filled in. Nothing is merged in from
            // the patient's own readings.
            'vitals'               => $this->vitalsFrom($validated),
        ]);

        $this->context->auditPatientAccess(
            'triage_recorded',
            'TriageRecord',
            $record->id,
            $patient->id,
            ['facility_id' => $facilityId]
        );

        return redirect()->route('portals.triage.show', $record->id)
            ->with('success', __('triage.recorded'));
    }

    /**
     * Read-back: one assessment, its observations, its reassessment history,
     * and the reassessment form.
     */
    public function show(Request $request, string $id): View
    {
        $record = $this->scoped()
            ->with(['visit.patient', 'nurse'])
            ->findOrFail($id);

        $patient = $record->visit?->patient;

        $vitals = $record->triageVitalSigns()->orderByDesc('recorded_at')->get();
        $latest = $vitals->first();

        $bands = TriageBandContext::for($patient?->date_of_birth, $record->pregnancy_status);

        $this->context->auditPatientAccess(
            'triage_record_view',
            'TriageRecord',
            $record->id,
            $patient?->id
        );

        return view('portals.triage.show', [
            'record'          => $record,
            'patient'         => $patient,
            'vitals'          => $vitals,
            'bands'           => $bands,
            // Empty whenever this platform has no bands for this patient — a
            // child, an unknown age, or a pregnancy. The view then prints the
            // refusal instead, never a softened interpretation.
            'interpretation'  => $latest ? $bands->interpret(TriageBandContext::readingsFrom($latest)) : [],
            'priority'        => $this->triage->currentPriority($record),
            'reassessments'   => $record->reassessments()->orderByDesc('reassessed_at')->get(),
            'scores'          => $record->scores()->orderByDesc('scored_at')->get(),
            'acuityLevels'    => TriageAcuity::LEVELS,
            'consciousness'   => TriageDeskService::CONSCIOUSNESS_LEVELS,
            'selfReported'    => $patient ? $this->selfReported($patient->id) : collect(),
        ]);
    }

    /**
     * Re-triage. The original record is never edited — see TriageDeskService.
     */
    public function reassess(Request $request, string $id): RedirectResponse
    {
        $record = $this->scoped()->findOrFail($id);

        $validated = $request->validate([
            'new_priority'       => ['required', 'string', 'in:' . implode(',', TriageAcuity::LEVELS)],
            // Mandatory. A priority that changed for no recorded reason is the
            // one an inquest cannot reconstruct.
            'reason_for_change'  => 'required|string|max:2000',
            'pain_score'         => 'nullable|integer|min:0|max:10',

            'temperature'         => 'nullable|numeric|min:20|max:45',
            'pulse_rate'          => 'nullable|integer|min:10|max:300',
            'respiratory_rate'    => 'nullable|integer|min:2|max:99',
            'systolic_bp'         => 'nullable|integer|min:40|max:300',
            'diastolic_bp'        => 'nullable|integer|min:20|max:200',
            'oxygen_saturation'   => 'nullable|integer|min:20|max:100',
            'weight_kg'           => 'nullable|numeric|min:0.3|max:400',
            'height_cm'           => 'nullable|numeric|min:20|max:260',
            'gcs_score'           => 'nullable|integer|min:3|max:15',
            'consciousness_level' => ['nullable', 'string', 'in:' . implode(',', TriageDeskService::CONSCIOUSNESS_LEVELS)],
        ]);

        $this->triage->reassess($record, [
            'reassessed_by' => (string) auth()->id(),
            'role'          => $this->actorRole(),
            'new_priority'  => $validated['new_priority'],
            'reason'        => $validated['reason_for_change'],
            'pain_score'    => $validated['pain_score'] ?? null,
            'vitals'        => $this->vitalsFrom($validated),
        ]);

        $this->context->auditPatientAccess(
            'triage_reassessed',
            'TriageRecord',
            $record->id,
            $record->visit?->patient_id
        );

        return redirect()->route('portals.triage.show', $record->id)
            ->with('success', __('triage.reassessed'));
    }

    // ── internals ─────────────────────────────────────────────────────────

    /**
     * Triage records at THIS facility only.
     *
     * The join through `visits` is the whole tenancy boundary for this feature:
     * `triage_records` has no facility_id, so anything that forgets this join
     * reads the whole country. Every query in this controller starts here.
     */
    private function scoped()
    {
        $facilityId = $this->facilityId();

        return TriageRecord::query()->whereHas(
            'visit',
            fn ($q) => $q->where('facility_id', $facilityId)
        );
    }

    /**
     * The facility this desk is working for.
     *
     * PortalContextService only — the session's active facility, then the
     * user's primary posting. Never a header, never the body, and deliberately
     * never a "first facility in the table" fallback: a triage written against
     * a facility nobody selected is a record in the wrong hospital.
     */
    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('triage.no_facility_context'));
        }

        return (string) $facilityId;
    }

    /** The role this actor holds at the active facility, for the audit trail. */
    private function actorRole(): ?string
    {
        $user = auth()->user();

        return $user ? StaffCapabilities::roleFor($user, $this->context->facilityId()) : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function vitalsFrom(array $validated): array
    {
        $vitals = [];

        foreach (TriageDeskService::VITAL_FIELDS as $field) {
            $vitals[$field] = $validated[$field] ?? null;
        }

        return $vitals;
    }

    /**
     * The patient's OWN recent readings — displayed as context, never used.
     *
     * Returned separately from everything else in the view data so that no
     * template loop and no future refactor can mistake them for the nurse's
     * observations. Voided rows are excluded; a reading the patient withdrew is
     * not context.
     */
    private function selfReported(string $patientId)
    {
        return PatientRecordedVital::query()
            ->where('patient_id', $patientId)
            ->whereNull('voided_at')
            ->orderByDesc('measured_at')
            ->limit(10)
            ->get();
    }
}
