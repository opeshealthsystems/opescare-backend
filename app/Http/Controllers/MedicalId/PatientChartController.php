<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Mobile\MobileConsentRequestController;
use App\Models\Patient;
use App\Modules\Governance\Services\ConsentService;
use App\Services\Chart\ChartSummaryBuilder;
use App\Services\Chart\PatientChartAccess;
use App\Services\Chart\PatientChartService;
use App\Services\Portal\DeskFacilityContext;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
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
            'purposes' => MobileConsentRequestController::PURPOSES,
        ]);
    }

    /**
     * Ask the patient for access, from the desk.
     *
     * Before this, a clinician with no basis met "No chart is available" and
     * nothing else: the consent loop was reachable only from the phone app
     * (POST /api/mobile/doctor/consent-requests). This is the same ask, through
     * the same ConsentService, with the same closed vocabulary.
     *
     * The answer NEVER says whether the Health ID exists: an unknown ID, a
     * patient already asked, and a fresh request all return the same message,
     * so the form cannot be used to walk the register. The facility is the
     * session's (DeskFacilityContext), the clinician is the session user.
     */
    public function requestConsent(Request $request, ConsentService $consents): RedirectResponse
    {
        $facilityId  = $this->facilityId();
        $clinicianId = $this->clinicianId();

        $validated = $request->validate([
            'health_id'     => ['required', 'string', 'max:64'],
            'purpose'       => ['required', 'string', 'in:' . implode(',', MobileConsentRequestController::PURPOSES)],
            'justification' => ['nullable', 'string', 'max:1000'],
        ]);

        $healthId = trim($validated['health_id']);
        $patient  = Patient::resolveHealthId($healthId);

        if ($patient !== null && $clinicianId !== null
            && $consents->activeGrantFor($patient->id, $facilityId) === null) {
            $consents->expireStaleRequests($patient->id, $facilityId);

            if ($consents->openRequestFor($patient->id, $facilityId) === null) {
                try {
                    $consents->requestConsent(
                        $patient->id,
                        $facilityId,
                        $clinicianId,
                        $validated['purpose'],
                        MobileConsentRequestController::SCOPES,
                        24 * 60,
                        now()->addHours(72),
                        $validated['justification'] ?? null,
                    );
                } catch (QueryException $e) {
                    // 23505: another clinician here asked in the same instant.
                    // The partial unique index did its job; nothing to add.
                    if (($e->errorInfo[0] ?? null) !== '23505') {
                        throw $e;
                    }
                }
            }
        }

        return redirect()
            ->route('portals.chart.index', ['health_id' => $healthId])
            ->with('success', __('chart.consent_requested'));
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

        if ($tab === 'summary') {
            // Every section gated by its own category; each one shown is its
            // own access-log row, so the patient's log lists what was read.
            $data = app(ChartSummaryBuilder::class)->build($patient, $access, $facilityId);

            foreach ($data['disclosed'] as $category) {
                if (! $access->auditRead($category, 'Patient')) {
                    abort(503, __('chart.audit_unavailable'));
                }
            }
        } else {
            $data = $consentRequired ? [] : $this->section($tab, $patient, $facilityId);
        }

        return view('portals.chart.show', [
            'patient'         => $patient,
            'identity'        => $this->chart->identity($patient),
            'tab'             => $tab,
            'tabs'            => array_keys(PatientChartService::TABS),
            'basis'           => $access->verdict->basis,
            'scope'           => $access->verdict->scope,
            'consentRequired' => $consentRequired,
            'facilityId'      => $facilityId,
            'purposes'        => MobileConsentRequestController::PURPOSES,
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
            // Built by ChartSummaryBuilder in show(); never reached.
            'summary' => [],
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
        // DeskFacilityContext: the session's active facility, else the user's
        // ONE real posting; two postings → /select-facility, as on /facility/*.
        $facilityId = app(DeskFacilityContext::class)->resolve();

        if (! $facilityId) {
            abort(403, __('chart.no_facility_context'));
        }

        return (string) $facilityId;
    }
}
