<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AdverseReactionNote;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\VaccinationSchedule;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Immunisation\ImmunisationRecordService;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mobile Provider API — the clinician IMMUNISATION write path.
 *
 * In Cameroon the child vaccination card is the most-carried paper health
 * record there is. This fork already STORED immunisation records and could
 * only read them: the patient could see their card, and nobody could write a
 * dose onto it. This controller is the write side.
 *
 * Every route sits behind `auth.provider` + `capability:clinical_chart`, so:
 *
 *     clinician  ← $request->attributes 'provider_user_id'
 *     facility   ← $request->attributes 'provider_facility_id'
 *
 * Both are stamped by AuthenticateMobileProvider from the bearer token.
 * Neither is ever read from the body, query string, header or session, and
 * there is no parameter in this file that lets a caller name either one. A
 * `facility_id` or `administered_by_id` posted in the body is simply not
 * looked at — which is the one thing the platform's own partner-API
 * immunisation controller got wrong: it accepts `administered_by_id` straight
 * off the request body, so a partner may attribute a dose to any user in the
 * system.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * A DOSE IS A CLINICAL EVENT, SO IT IS IMMUTABLE
 * ─────────────────────────────────────────────────────────────────────────
 *
 * There is no update route and no destroy route in this file. A dose written
 * in error is VOIDED — status moves to `entered_in_error` with a reason, the
 * row stays, and the retraction is itself an event. That is the same discipline
 * PatientRecordedVitalsController@void follows, and it lands correctly on the
 * patient's own card for free: MobilePatientController@getImmunizations already
 * filters `status != entered_in_error`, so a voided dose stops reading as
 * administered the moment it is voided, with no change to that controller.
 *
 * Only the facility that recorded a dose may void it. Correcting another
 * facility's entry is not a correction, it is an overwrite of somebody else's
 * record — so it is 403, and the honest remedy is to record the truth as a new
 * dose or raise it with them.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * NOTHING IS INVENTED
 * ─────────────────────────────────────────────────────────────────────────
 *
 *   • A field that was not recorded is ABSENT — null. Never zero, never a
 *     default. `dose_number` is not assumed to be 1; `route` and `site` are
 *     not guessed.
 *
 *   • `adverse_reactions` is `[]` when this dose has no reported reaction and
 *     the relation was loaded. It never renders as "none reported" — an
 *     absence of a report is not a report of absence, and the AEFI channel is
 *     a safety signal that must not be softened by a UI default.
 *
 *   • HISTORICAL doses (`is_historical: true` — a paper card being
 *     transcribed, the commonest case here) are stored with
 *     `administered_by_id = NULL`, because the clinician typing the card is
 *     NOT the clinician who gave the vaccine and the record must not claim
 *     they were. Who transcribed it lives in the access log, where it belongs.
 *     `verification_status` stays `unverified` for those, and `verified` only
 *     for a dose this facility gave itself.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * NO PATIENT ENUMERATION
 * ─────────────────────────────────────────────────────────────────────────
 *
 * A patient is resolved by EXACT id or EXACT Health ID only — no LIKE, no
 * prefix, no name — and every chart-scoped action then runs the same
 * ClinicalRelationshipGate the chart runs. Holding a patient id is not
 * authorisation.
 *
 * Every read and every write is access-logged against the patient, and — as on
 * the chart — a disclosure that cannot be recorded does not happen (503).
 */
class MobileDoctorImmunizationController extends Controller
{
    public function __construct(private readonly ImmunisationRecordService $doses)
    {
    }

    /**
     * What a dose may be at the moment it is WRITTEN.
     *
     * `not_done` is a real clinical record, not an absence: a vaccine refused,
     * contraindicated or out of stock is a fact the next clinician needs, and
     * losing it is how a child gets counted as covered. `entered_in_error` is
     * absent from this list on purpose — nothing may be born retracted; it is
     * reached only through void().
     */
    private const WRITABLE_STATUSES = ['completed', 'not_done'];

    /** Routes of administration recognised by the EPI record. */
    private const ROUTES = ['IM', 'SC', 'ID', 'oral', 'intranasal'];

    private const SEVERITIES = ['mild', 'moderate', 'severe', 'life_threatening'];

    private const ONSET_TIMINGS = ['immediate', 'within_1h', 'within_24h', 'delayed'];

    private const OUTCOMES = ['resolved', 'recovering', 'not_resolved', 'fatal', 'unknown'];

    /** Hard ceilings on anything this controller returns or accepts in bulk. */
    private const LIST_LIMIT       = 200;
    private const SCHEDULE_MAX     = 40;

    // ══════════════════════════════════════════════════════════════════
    // Reading
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/doctor/patients/{id}/immunizations?include_voided=
     *
     * The patient's vaccination card as this platform holds it — every dose,
     * from EVERY facility, because a card that stopped at the facility door
     * would not be a card. That breadth is why the ClinicalRelationshipGate
     * runs first: the record is the patient's, and it opens only to a clinician
     * who may already open their chart.
     *
     * Voided doses are excluded by default, exactly as the patient's own card
     * excludes them. `include_voided=1` shows them, flagged — a clinician
     * checking why a dose vanished needs to see that it was retracted rather
     * than conclude it was never given.
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $ctx = $this->patientContext($request, $id);

        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$patient] = $ctx;

        $validated = $request->validate([
            'include_voided' => ['nullable', 'boolean'],
        ]);

        if (! $this->audit($patient->id, $request, 'immunization', 'read', null)) {
            return $this->auditUnavailable();
        }

        $includeVoided = (bool) ($validated['include_voided'] ?? false);

        $rows = ImmunizationRecord::query()
            ->where('patient_id', $patient->id)
            ->when(! $includeVoided, fn ($q) => $q->where('status', '!=', 'entered_in_error'))
            ->with(['facility:id,name', 'adverseReactions'])
            ->orderByDesc('administered_at')
            ->limit(self::LIST_LIMIT)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (ImmunizationRecord $r) => $this->serializeRecord($r))->values(),
            'meta' => [
                'patient_id'     => $patient->id,
                'count'          => $rows->count(),
                'include_voided' => $includeVoided,
            ],
        ], 200);
    }

    /**
     * GET /api/mobile/doctor/immunizations/{id}
     *
     * One dose in full, with any reported adverse reactions. Resolved by id,
     * then gated on the PATIENT it belongs to — not on the facility that gave
     * it, so a clinician legitimately holding the chart can read a dose given
     * elsewhere. A dose whose patient this clinician may not open answers 404,
     * not 403: confirming the id exists would already say this patient was
     * vaccinated.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $facilityId || ! $clinicianId) {
            return $this->facilityRequired();
        }

        $record = ImmunizationRecord::query()
            ->whereKey($id)
            ->with(['facility:id,name', 'adverseReactions'])
            ->first();

        if (! $record) {
            return $this->recordNotFound();
        }

        $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $record->patient_id);

        if (! $verdict->holds() || ! $verdict->coversFullRecord()) {
            $this->auditDenial($record->patient_id, $clinicianId, $facilityId);

            return $this->recordNotFound();
        }

        if (! $this->audit($record->patient_id, $request, 'immunization', 'read', $record->id)) {
            return $this->auditUnavailable();
        }

        return response()->json([
            'data' => $this->serializeRecord($record, withDetail: true),
        ], 200);
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/vaccination-schedule
     *
     * The forward course: what is due, when, and what has already been met.
     * `vaccination_schedules` holds no facility of its own — a course belongs
     * to the child, not to a clinic — so it is scoped to the patient and gated
     * exactly like the chart.
     */
    public function patientSchedule(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $ctx = $this->patientContext($request, $id);

        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$patient] = $ctx;

        if (! $this->audit($patient->id, $request, 'immunization_schedule', 'read', null)) {
            return $this->auditUnavailable();
        }

        $rows = VaccinationSchedule::query()
            ->where('patient_id', $patient->id)
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderBy('vaccine_code')
            ->orderBy('dose_number')
            ->limit(self::LIST_LIMIT)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (VaccinationSchedule $s) => $this->serializeSchedule($s))->values(),
            'meta' => [
                'patient_id' => $patient->id,
                'count'      => $rows->count(),
            ],
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════
    // Writing
    // ══════════════════════════════════════════════════════════════════

    /**
     * POST /api/mobile/doctor/patients/{id}/immunizations
     *
     * Record a dose. The actual gap this whole file closes.
     *
     * Body: vaccine_code* vaccine_name* vaccine_system? lot_number?
     *       manufacturer? administered_at? dose_number? dose_sequence?
     *       route? site? dose_quantity? dose_unit? expiry_date?
     *       status? not_done_reason? is_historical? encounter_id?
     *       source_document_id? confirm_duplicate?
     *
     * `facility_id` and `administered_by_id` are NOT accepted — they come from
     * the token, and for a historical transcription the administering clinician
     * is NULL rather than a guess.
     */
    public function store(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $ctx = $this->patientContext($request, $id);

        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$patient, $clinicianId, $facilityId] = $ctx;

        $validated = $request->validate([
            'vaccine_code'       => ['required', 'string', 'max:50'],
            'vaccine_name'       => ['required', 'string', 'max:200'],
            'vaccine_system'     => ['nullable', 'string', 'max:50'],
            'lot_number'         => ['nullable', 'string', 'max:100'],
            'manufacturer'       => ['nullable', 'string', 'max:200'],
            'administered_at'    => ['nullable', 'date'],
            'dose_number'        => ['nullable', 'integer', 'min:1', 'max:20'],
            'dose_sequence'      => ['nullable', 'string', 'max:50'],
            'route'              => ['nullable', Rule::in(self::ROUTES)],
            'site'               => ['nullable', 'string', 'max:100'],
            'dose_quantity'      => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'dose_unit'          => ['nullable', 'string', 'max:20'],
            'expiry_date'        => ['nullable', 'date'],
            'status'             => ['nullable', Rule::in(self::WRITABLE_STATUSES)],
            'not_done_reason'    => ['nullable', 'string', 'max:2000'],
            'is_historical'      => ['nullable', 'boolean'],
            'encounter_id'       => ['nullable', 'uuid'],
            'source_document_id' => ['nullable', 'uuid'],
            'confirm_duplicate'  => ['nullable', 'boolean'],
        ]);

        $status       = $validated['status'] ?? 'completed';
        $isHistorical = (bool) ($validated['is_historical'] ?? false);

        // A dose that was NOT given must say why. "not_done" with no reason is
        // an empty cell in the card that the next clinician cannot act on.
        if ($status === 'not_done' && trim((string) ($validated['not_done_reason'] ?? '')) === '') {
            return response()->json([
                'message'    => __('doctor_immunizations.not_done_reason_required'),
                'error_code' => 'NOT_DONE_REASON_REQUIRED',
            ], 422);
        }

        $administeredAt = isset($validated['administered_at'])
            ? Carbon::parse($validated['administered_at'])
            : Carbon::now();

        // A vaccine cannot have been given in the future. This is the one
        // sanity bound worth enforcing: everything else about a paper card may
        // legitimately be old, partial or unknown.
        if ($administeredAt->isFuture()) {
            return response()->json([
                'message'    => __('doctor_immunizations.administered_in_future'),
                'error_code' => 'ADMINISTERED_IN_FUTURE',
            ], 422);
        }

        // Duplicate suppression. The migration ships an index on
        // (patient_id, vaccine_code, administered_at, lot_number) for exactly
        // this; it is not UNIQUE, because a genuine same-day repeat exists, so
        // the check lives here and is overridable with confirm_duplicate. A
        // busy clinic double-tapping "record" must not silently inflate a
        // child's coverage.
        if (! ($validated['confirm_duplicate'] ?? false)) {
            $existing = $this->doses->findDuplicate(
                $patient->id,
                $validated['vaccine_code'],
                $administeredAt,
                isset($validated['dose_number']) ? (int) $validated['dose_number'] : null,
            );

            if ($existing) {
                return response()->json([
                    'message'       => __('doctor_immunizations.duplicate_dose'),
                    'error_code'    => 'DUPLICATE_DOSE',
                    'existing_id'   => $existing->id,
                    'existing_dose' => $this->serializeRecord($existing),
                    // The client re-posts with this set once a human has looked.
                    'override_with' => 'confirm_duplicate',
                ], 409);
            }
        }

        if (! $this->audit($patient->id, $request, 'immunization', 'create', null)) {
            return $this->auditUnavailable();
        }

        // The write itself is shared with the web desk (ImmunisationRecordService).
        $record = $this->doses->record($patient, $facilityId, $clinicianId, $validated, $status, $isHistorical, $administeredAt);

        $record->load(['facility:id,name', 'adverseReactions']);

        return response()->json([
            'data' => $this->serializeRecord($record, withDetail: true),
        ], 201);
    }

    /**
     * POST /api/mobile/doctor/immunizations/{id}/void
     *
     * Retract a dose recorded in error. This is an AMENDMENT, not a deletion:
     * the row stays, status becomes `entered_in_error`, and the reason is
     * kept — so the card shows that something was taken back rather than
     * quietly losing a line.
     *
     * Body: reason*
     *
     * Two rules that are not negotiable:
     *   • Only the RECORDING facility may void. Editing another facility's
     *     entry is an overwrite of their record, not a correction — 403.
     *   • A voided dose cannot be voided again, and there is no un-void. The
     *     truth is re-entered as a NEW dose.
     *
     * Schema note: `immunization_records` has no dedicated void columns, and
     * this port adds no migration. The reason is written to `not_done_reason`,
     * which is the table's "why this row is not a completed administration"
     * column — it already serves `not_done`. The status disambiguates the two
     * readings, and the serializer surfaces it as `void_reason` when the status
     * is `entered_in_error` so no screen can render a retraction as a refusal.
     */
    public function void(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $facilityId || ! $clinicianId) {
            return $this->facilityRequired();
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $record = ImmunizationRecord::query()->whereKey($id)->first();

        if (! $record) {
            return $this->recordNotFound();
        }

        $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $record->patient_id);

        if (! $verdict->holds() || ! $verdict->coversFullRecord()) {
            $this->auditDenial($record->patient_id, $clinicianId, $facilityId);

            return $this->recordNotFound();
        }

        // 403, not 404: this clinician can already see the dose on the chart.
        // The refusal is about authorship, and saying so is the only way they
        // learn the right remedy.
        if ($record->facility_id !== $facilityId) {
            return response()->json([
                'message'    => __('doctor_immunizations.not_recording_facility'),
                'error_code' => 'NOT_RECORDING_FACILITY',
            ], 403);
        }

        if ($record->status === 'entered_in_error') {
            return response()->json([
                'message'    => __('doctor_immunizations.already_voided'),
                'error_code' => 'ALREADY_VOIDED',
            ], 409);
        }

        if (! $this->audit($record->patient_id, $request, 'immunization', 'update', $record->id)) {
            return $this->auditUnavailable();
        }

        // A closed course line reopens inside the shared service.
        $this->doses->void($record, $validated['reason']);

        $record->refresh()->load(['facility:id,name', 'adverseReactions']);

        return response()->json([
            'data'    => $this->serializeRecord($record, withDetail: true),
            'message' => __('doctor_immunizations.voided'),
        ], 200);
    }

    /**
     * POST /api/mobile/doctor/immunizations/{id}/adverse-reactions
     *
     * Report an AEFI — an adverse event following immunisation.
     *
     * Body: severity* description* onset_timing? onset_at? action_taken?
     *       outcome? reported_to_authority? authority_report_reference?
     *
     * `reported_by_id` is NOT accepted; the reporter is the token's clinician.
     *
     * This is deliberately NOT restricted to the facility that gave the dose.
     * A reaction presents wherever the patient goes — often at a different
     * clinic, days later — and a safety signal that can only be filed by the
     * facility with the least interest in filing it is not a safety channel.
     * Any clinician who may open this patient's chart may append one.
     *
     * A reaction is appended, never replaced: the notes accumulate, because two
     * clinicians describing the same event differently is itself information.
     * A reaction cannot be attached to a voided dose — there was no dose.
     */
    public function reportAdverseReaction(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $facilityId || ! $clinicianId) {
            return $this->facilityRequired();
        }

        $validated = $request->validate([
            'severity'                   => ['required', Rule::in(self::SEVERITIES)],
            'description'                => ['required', 'string', 'max:8000'],
            'onset_timing'               => ['nullable', Rule::in(self::ONSET_TIMINGS)],
            'onset_at'                   => ['nullable', 'date'],
            'action_taken'               => ['nullable', 'string', 'max:2000'],
            'outcome'                    => ['nullable', Rule::in(self::OUTCOMES)],
            'reported_to_authority'      => ['nullable', 'boolean'],
            'authority_report_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $record = ImmunizationRecord::query()->whereKey($id)->first();

        if (! $record) {
            return $this->recordNotFound();
        }

        $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $record->patient_id);

        if (! $verdict->holds() || ! $verdict->coversFullRecord()) {
            $this->auditDenial($record->patient_id, $clinicianId, $facilityId);

            return $this->recordNotFound();
        }

        if ($record->status === 'entered_in_error') {
            return response()->json([
                'message'    => __('doctor_immunizations.reaction_on_voided_dose'),
                'error_code' => 'DOSE_VOIDED',
            ], 409);
        }

        if (! $this->audit($record->patient_id, $request, 'immunization_adverse_reaction', 'create', $record->id)) {
            return $this->auditUnavailable();
        }

        $reaction = AdverseReactionNote::create([
            'immunization_record_id' => $record->id,
            'patient_id'             => $record->patient_id,
            // From the token, never the body.
            'reported_by_id'         => $clinicianId,
            'severity'               => $validated['severity'],
            'description'            => $validated['description'],
            'onset_timing'           => $validated['onset_timing'] ?? null,
            'onset_at'               => $validated['onset_at'] ?? null,
            'action_taken'           => $validated['action_taken'] ?? null,
            // Not defaulted to 'unknown': an outcome nobody recorded is absent,
            // and 'unknown' is a clinician's positive statement that they
            // looked and could not tell.
            'outcome'                => $validated['outcome'] ?? null,
            'reported_to_authority'  => (bool) ($validated['reported_to_authority'] ?? false),
            'authority_report_reference' => $validated['authority_report_reference'] ?? null,
        ]);

        // A severe or life-threatening AEFI is a public-health event. Nothing
        // in this fork routes MINSANTE notifications yet, so it is logged
        // loudly and the response says plainly that reporting to the authority
        // is still a human step — rather than letting a green tick imply it
        // was done.
        $notifiable = in_array($validated['severity'], ['severe', 'life_threatening'], true);

        if ($notifiable) {
            Log::warning('Severe AEFI recorded.', [
                'immunization_record_id' => $record->id,
                'severity'               => $validated['severity'],
                'facility_id'            => $facilityId,
            ]);
        }

        return response()->json([
            'data'   => $this->serializeReaction($reaction),
            'notice' => $notifiable && ! $reaction->reported_to_authority
                ? __('doctor_immunizations.authority_report_pending')
                : null,
        ], 201);
    }

    /**
     * POST /api/mobile/doctor/patients/{id}/vaccination-schedule
     *
     * Lay out a course of vaccines for this patient — the EPI card's forward
     * half. Idempotent per (vaccine_code, dose_number): re-posting a line
     * updates its dates rather than stacking a second copy, because a course
     * posted twice is how a schedule becomes unreadable.
     *
     * A line already `completed` is NOT touched. Rewriting the due date of a
     * dose that has already been given would move a fact, and the given dose is
     * the fact.
     *
     * Body: schedules[]* each { vaccine_code* vaccine_name* dose_number*
     *       dose_sequence? due_date? earliest_date? latest_date? status? notes? }
     */
    public function scheduleVaccines(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $ctx = $this->patientContext($request, $id);

        if ($ctx instanceof JsonResponse) {
            return $ctx;
        }

        [$patient, $clinicianId] = $ctx;

        $validated = $request->validate([
            'schedules'                 => ['required', 'array', 'min:1', 'max:' . self::SCHEDULE_MAX],
            'schedules.*.vaccine_code'  => ['required', 'string', 'max:50'],
            'schedules.*.vaccine_name'  => ['required', 'string', 'max:200'],
            'schedules.*.dose_number'   => ['required', 'integer', 'min:1', 'max:20'],
            'schedules.*.dose_sequence' => ['nullable', 'string', 'max:50'],
            'schedules.*.due_date'      => ['nullable', 'date'],
            'schedules.*.earliest_date' => ['nullable', 'date'],
            'schedules.*.latest_date'   => ['nullable', 'date'],
            'schedules.*.status'        => ['nullable', Rule::in(['due', 'deferred', 'skipped'])],
            'schedules.*.notes'         => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $this->audit($patient->id, $request, 'immunization_schedule', 'create', null)) {
            return $this->auditUnavailable();
        }

        $written = DB::transaction(function () use ($validated, $patient) {
            $out = [];

            foreach ($validated['schedules'] as $line) {
                $existing = VaccinationSchedule::query()
                    ->where('patient_id', $patient->id)
                    ->where('vaccine_code', $line['vaccine_code'])
                    ->where('dose_number', $line['dose_number'])
                    ->first();

                // Already given. The course does not get to rewrite history.
                if ($existing && $existing->status === 'completed') {
                    $out[] = $existing;

                    continue;
                }

                $attributes = [
                    'vaccine_name'  => $line['vaccine_name'],
                    'dose_sequence' => $line['dose_sequence'] ?? null,
                    'due_date'      => $line['due_date'] ?? null,
                    'earliest_date' => $line['earliest_date'] ?? null,
                    'latest_date'   => $line['latest_date'] ?? null,
                    'status'        => $line['status'] ?? 'due',
                    'notes'         => $line['notes'] ?? null,
                ];

                if ($existing) {
                    $existing->fill($attributes)->save();
                    $out[] = $existing;

                    continue;
                }

                $out[] = VaccinationSchedule::create($attributes + [
                    'patient_id'   => $patient->id,
                    'vaccine_code' => $line['vaccine_code'],
                    'dose_number'  => $line['dose_number'],
                ]);
            }

            return $out;
        });

        return response()->json([
            'data' => array_map(fn (VaccinationSchedule $s) => $this->serializeSchedule($s), $written),
            'meta' => [
                'patient_id'  => $patient->id,
                'count'       => count($written),
                'recorded_by' => $clinicianId,
            ],
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════════
    // Serialization
    // ══════════════════════════════════════════════════════════════════

    /**
     * @return array<string,mixed>
     */
    private function serializeRecord(ImmunizationRecord $r, bool $withDetail = false): array
    {
        $isVoided = $r->status === 'entered_in_error';

        $row = [
            'id'                  => $r->id,
            'patient_id'          => $r->patient_id,
            'facility_id'         => $r->facility_id,
            'facility_name'       => $r->relationLoaded('facility') ? $r->facility?->name : null,
            // Clinical terms, carried through exactly as recorded. A vaccine
            // name is not UI copy and is never translated.
            'vaccine_code'        => $r->vaccine_code,
            'vaccine_system'      => $r->vaccine_system,
            'vaccine_name'        => $r->vaccine_name,
            'lot_number'          => $r->lot_number,
            'manufacturer'        => $r->manufacturer,
            'administered_at'     => $this->iso($r->administered_at),
            'dose_number'         => $r->dose_number,
            'dose_sequence'       => $r->dose_sequence,
            'route'               => $r->route,
            'site'                => $r->site,
            'status'              => (string) $r->status,
            'is_voided'           => $isVoided,
            // The same column carries both readings; the status says which one
            // this is, so a retraction can never render as a refusal.
            'void_reason'         => $isVoided ? $r->not_done_reason : null,
            'not_done_reason'     => $r->status === 'not_done' ? $r->not_done_reason : null,
            'verification_status' => $r->verification_status,
            'is_historical'       => (bool) $r->is_historical,
            // Null when the relation was not loaded — "we did not ask" — and
            // [] only when we asked and there were none. The client must be
            // able to tell those apart before it prints anything about safety.
            'adverse_reaction_count' => $r->relationLoaded('adverseReactions')
                ? $r->adverseReactions->count()
                : null,
        ];

        if (! $withDetail) {
            return $row;
        }

        return $row + [
            // Null for a transcribed card: unknown, not the transcriber.
            'administered_by_id' => $r->administered_by_id,
            'encounter_id'       => $r->encounter_id,
            'dose_quantity'      => $r->dose_quantity,
            'dose_unit'          => $r->dose_unit,
            'expiry_date'        => $r->expiry_date?->toDateString(),
            'source_document_id' => $r->source_document_id,
            'adverse_reactions'  => $r->relationLoaded('adverseReactions')
                ? $r->adverseReactions->map(fn (AdverseReactionNote $a) => $this->serializeReaction($a))->values()
                : null,
            'created_at'         => $this->iso($r->created_at),
            'updated_at'         => $this->iso($r->updated_at),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeSchedule(VaccinationSchedule $s): array
    {
        return [
            'id'            => $s->id,
            'patient_id'    => $s->patient_id,
            'vaccine_code'  => $s->vaccine_code,
            'vaccine_name'  => $s->vaccine_name,
            'dose_number'   => $s->dose_number,
            'dose_sequence' => $s->dose_sequence,
            'due_date'      => $s->due_date?->toDateString(),
            'earliest_date' => $s->earliest_date?->toDateString(),
            'latest_date'   => $s->latest_date?->toDateString(),
            'status'        => (string) $s->status,
            // Derived from the date on read, so a course does not depend on a
            // nightly job having run to tell the truth about being late.
            'is_overdue'    => $s->isOverdue(),
            'completed_by_immunization_id' => $s->completed_by_immunization_id,
            'notes'         => $s->notes,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeReaction(AdverseReactionNote $a): array
    {
        return [
            'id'                    => $a->id,
            'severity'              => $a->severity,
            'description'           => $a->description,
            'onset_timing'          => $a->onset_timing,
            'onset_at'              => $this->iso($a->onset_at),
            'action_taken'          => $a->action_taken,
            // Absent stays absent. Never 'unknown', never 'none'.
            'outcome'               => $a->outcome,
            'reported_to_authority' => (bool) $a->reported_to_authority,
            'authority_report_reference' => $a->authority_report_reference,
            'reported_by_id'        => $a->reported_by_id,
            'reported_at'           => $this->iso($a->created_at),
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════════

    /**
     * Resolve the patient AND the clinician's right to act on them, exactly as
     * the chart and the referral surface do. Returns a JsonResponse on refusal.
     *
     * Writing onto a vaccination card is a write on the chart, and PREVIEW-scope
     * access (identity, allergies, medicines — granted because the patient
     * asked that their record stay behind consent) is not enough to add a
     * clinical event to it, so this requires the full-record basis rather than
     * merely a relationship.
     *
     * @return array{0: Patient, 1: string, 2: string, 3: ClinicalRelationshipGate}|JsonResponse
     */
    private function patientContext(Request $request, string $id): array|JsonResponse
    {
        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if (! $facilityId || ! $clinicianId) {
            return $this->facilityRequired();
        }

        $patient = $this->resolvePatient($id);

        if (! $patient) {
            return response()->json([
                'message'    => __('doctor_immunizations.patient_not_found'),
                'error_code' => 'NOT_FOUND',
            ], 404);
        }

        $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $patient->id);

        if (! $verdict->holds()) {
            $this->auditDenial($patient->id, $clinicianId, $facilityId);

            return response()->json([
                'message'         => __('doctor_immunizations.no_clinical_relationship'),
                'error_code'      => 'NO_CLINICAL_RELATIONSHIP',
                'required_action' => 'request_consent',
            ], 403);
        }

        if (! $verdict->coversFullRecord()) {
            $this->auditDenial($patient->id, $clinicianId, $facilityId);

            return response()->json([
                'message'         => __('doctor_immunizations.consent_required'),
                'error_code'      => 'CONSENT_REQUIRED',
                'required_action' => 'request_consent',
            ], 403);
        }

        return [$patient, $clinicianId, $facilityId, $verdict];
    }

    /**
     * ONE patient by EXACT id or EXACT Health ID — the same resolution the
     * chart uses. No LIKE, no prefix, no name: this cannot be walked.
     */
    private function resolvePatient(string $id): ?Patient
    {
        $key = trim($id);

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        return Str::isUuid($key)
            ? Patient::resolveId($key)
            : Patient::resolveHealthId($key);
    }

    /**
     * Record the act against the patient. Returns false when it could not be
     * written, which every caller turns into a 503 rather than proceeding: a
     * dose written with no trail is exactly the event the audit exists for.
     */
    private function audit(string $patientId, Request $request, string $dataCategory, string $accessType, ?string $resourceId): bool
    {
        $clinicianId = $this->clinicianId($request);

        if (! $clinicianId) {
            return false;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $this->facilityId($request),
                purpose: 'treatment:immunization',
                dataCategory: $dataCategory,
                resourceType: 'ImmunizationRecord',
                resourceId: $resourceId,
                accessType: $accessType,
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Clinician immunisation action refused: access log could not be written.', [
                'patient_id'   => $patientId,
                'clinician_id' => $clinicianId,
                'access_type'  => $accessType,
                'error'        => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** Best-effort: a refusal disclosed nothing, so it must not become a 500. */
    private function auditDenial(string $patientId, ?string $clinicianId, ?string $facilityId): void
    {
        if (! $clinicianId) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: ClinicalRelationshipGate::NO_RELATIONSHIP_PURPOSE,
                dataCategory: 'immunization',
                resourceType: 'ImmunizationRecord',
                resourceId: null,
                accessType: 'denied',
            );
        } catch (\Throwable $e) {
            Log::warning('Clinician immunisation denial was not access-logged.', [
                'patient_id'   => $patientId,
                'clinician_id' => $clinicianId,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /** The clinician's user id — stamped by AuthenticateMobileProvider. */
    private function clinicianId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_user_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * The facility this session speaks for. Only ever from the request
     * attributes the auth middleware set — never a header, body, query string
     * or session, and never a fallback default.
     */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /** Honour Accept-Language for this response only. */
    private function applyRequestLocale(Request $request): void
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header !== '' && str_starts_with(strtolower(ltrim($header)), 'fr')) {
            App::setLocale('fr');
        }
    }

    private function facilityRequired(): JsonResponse
    {
        return response()->json([
            'message'    => __('doctor_immunizations.facility_required'),
            'error_code' => 'IDENTITY_UNRESOLVABLE',
        ], 401);
    }

    private function recordNotFound(): JsonResponse
    {
        return response()->json([
            'message'    => __('doctor_immunizations.record_not_found'),
            'error_code' => 'NOT_FOUND',
        ], 404);
    }

    private function auditUnavailable(): JsonResponse
    {
        return response()->json([
            'message'    => __('doctor_immunizations.audit_unavailable'),
            'error_code' => 'AUDIT_UNAVAILABLE',
        ], 503);
    }

    private function iso(mixed $value): ?string
    {
        return $value instanceof \DateTimeInterface ? Carbon::instance($value)->toIso8601String() : null;
    }
}
