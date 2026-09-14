<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Chart\PatientChartAccess;
use App\Services\Chart\PatientChartService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The web patient chart — READ-ONLY, one patient, nine tabs.
 *
 * `clinical_chart` was granted to every clinical role and gated nothing,
 * because no web screen existed: a doctor at a portal could not open the
 * record of the patient in front of them. This is that screen.
 *
 * ── WHO MAY OPEN IT ────────────────────────────────────────────────────────
 *
 *   capability:clinical_chart      the role, at the ACTIVE facility, may read
 *                                  charts at all — 403 `role_forbidden`
 *                                  (routes/chart.php).
 *   ClinicalRelationshipGate       THIS clinician may open THIS patient —
 *                                  through App\Services\Chart\PatientChartAccess,
 *                                  which documents what the gate decides and
 *                                  how the web answers. No basis → 404.
 *
 * The clinician is the session user; the facility is PortalContextService's
 * answer (the session's active facility, then the primary posting) and is
 * never read from the request.
 *
 * ── FINDING A PATIENT ──────────────────────────────────────────────────────
 *
 * Exact Health ID only, via Patient::resolveHealthId. No name, no LIKE, no
 * list. A Health ID nobody holds and a Health ID this clinician has no basis
 * to open answer identically.
 *
 * ── EVERY TAB IS ITS OWN DISCLOSURE ────────────────────────────────────────
 *
 * One tab per request, one access_logs row per tab, carrying the data
 * category actually shown. A tab that cannot be logged is not shown (503).
 */
class PatientChartController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly PatientChartService $chart,
    ) {
    }

    /**
     * The lookup form. A submitted Health ID resolves to the record's uuid
     * and redirects; a miss re-renders the form with the same message the
     * gate's refusal will later show, so the two are indistinguishable.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $facilityId = $this->facilityId();

        $healthId = trim((string) $request->query('health_id', ''));
        $notFound = false;

        if ($healthId !== '') {
            $patient = Patient::resolveHealthId($healthId);

            if ($patient !== null) {
                $access = PatientChartAccess::evaluate($patient, $this->clinicianId(), $facilityId);

                if ($access->holds()) {
                    return redirect()->route('portals.chart.show', $patient->id);
                }

                $access->auditDenial('patient_identity', 'Patient');
            }

            $notFound = true;
        }

        return view('portals.chart.index', [
            'healthId' => $healthId,
            'notFound' => $notFound,
        ]);
    }

    /** One tab of one chart. */
    public function show(Request $request, string $id): View
    {
        $facilityId  = $this->facilityId();
        $clinicianId = $this->clinicianId();

        $tab = (string) $request->query('tab', 'summary');

        if (! array_key_exists($tab, PatientChartService::TABS)) {
            $tab = 'summary';
        }

        [$dataCategory, $resourceType] = PatientChartService::TABS[$tab];

        // Exact uuid, following a merge. The Health-ID form redirects here
        // with the uuid, so a stale bookmark to a retired record still opens
        // the survivor.
        $patient = Str::isUuid($id) ? Patient::resolveId($id) : null;

        if ($patient === null) {
            abort(404);
        }

        $access = PatientChartAccess::evaluate($patient, $clinicianId, $facilityId);

        if (! $access->holds()) {
            // Never confirm existence. Logged as a refusal, as the gate's
            // other callers do.
            $access->auditDenial($dataCategory, $resourceType);
            abort(404);
        }

        $consentRequired = ! $access->covers($dataCategory);

        if ($consentRequired) {
            // The patient keeps their record behind consent and this facility
            // holds no grant: the summary still opens (identity, allergies,
            // medicines — the safety minimum), this tab does not.
            $access->auditDenial($dataCategory, $resourceType);
        } elseif (! $access->auditRead($dataCategory, $resourceType)) {
            abort(503, __('chart.audit_unavailable'));
        }

        $data = $consentRequired ? [] : $this->section($tab, $patient, $facilityId);

        return view('portals.chart.show', [
            'patient'         => $patient,
            'identity'        => $this->chart->identity($patient),
            'tab'             => $tab,
            'tabs'            => array_keys(PatientChartService::TABS),
            'basis'           => $access->verdict->basis,
            'scope'           => $access->verdict->scope,
            'consentRequired' => $consentRequired,
            'facilityId'      => $facilityId,
        ] + $data);
    }

    // ── internals ─────────────────────────────────────────────────────────

    /**
     * The data for one tab. The summary always carries allergies — an
     * allergy list that fails inside an aggregate renders as "no known
     * allergies", so it is read on its own and shown first.
     *
     * @return array<string,mixed>
     */
    private function section(string $tab, Patient $patient, string $facilityId): array
    {
        return match ($tab) {
            'summary' => [
                'allergies' => $this->chart->allergies($patient),
                'problems'  => $this->chart->problems($patient),
                'medicines' => $this->chart->currentMedicines($patient),
            ],
            'encounters' => ['encounters' => $this->chart->encounters($patient)],
            'vitals' => $this->chart->facilityVitals($patient) + [
                'selfReported' => $this->chart->selfReportedVitals($patient),
            ],
            'diagnoses'     => ['diagnoses' => $this->chart->diagnoses($patient)],
            'labs'          => ['labs' => $this->chart->labResults($patient)],
            'immunizations' => ['immunizations' => $this->chart->immunizations($patient)],
            'preconsult'    => ['submissions' => $this->chart->preconsult($patient, $facilityId)],
            'triage'        => ['triage' => $this->chart->triageHistory($patient, $facilityId)],
            'documents'     => ['documents' => $this->chart->documents($patient)],
        };
    }

    private function clinicianId(): ?string
    {
        $id = auth()->id();

        return $id ? (string) $id : null;
    }

    /**
     * PortalContextService only — never a header, the body, the query string
     * or a "first facility" fallback. Without a facility there is no posting
     * to judge, so the desk is closed rather than opened for everyone.
     */
    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('chart.no_facility_context'));
        }

        return (string) $facilityId;
    }
}
