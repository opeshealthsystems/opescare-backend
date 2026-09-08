<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\PreconsultStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PreconsultSubmission;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Preconsult\PreconsultQuestionnaireService;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mobile Provider API — READING a patient's pre-consult questionnaire.
 *
 * Its own controller rather than an eighth section of MobileDoctorChartController,
 * for one reason: everything it serves is PATIENT-REPORTED and the chart is
 * not. Folding self-reported symptom text in beside coded diagnoses is how a
 * sentence a patient typed becomes a finding somebody prescribes against. Here
 * it is a separate endpoint, a separate audit data category
 * (`patient_reported_intake`), and every single answer carries its provenance
 * block — the same structural line App\Enums\VitalProvenance draws.
 *
 * The access rule is the platform's, unchanged and not re-implemented:
 * App\Support\ClinicalRelationshipGate decides, exactly as it does for the
 * chart. The clinician and facility come from `$request->attributes` (stamped
 * by AuthenticateMobileProvider from the bearer token) and from nowhere else.
 *
 *   unknown / isolated patient          → 404
 *   no clinical relationship            → 403 NO_CLINICAL_RELATIONSHIP
 *   relationship covers preview only    → 403 CONSENT_REQUIRED
 *   audit could not be written          → 503, and nothing is disclosed
 *
 * FACILITY SCOPE. Only submissions given for an appointment at the TOKEN'S OWN
 * facility are returned. An intake is written for the visit it prepares; a
 * clinician elsewhere reading it would be receiving a disclosure the patient
 * made to somebody else.
 *
 * DRAFTS ARE NEVER SHOWN. A draft is a patient mid-sentence
 * (PreconsultStatus::isVisibleToClinician). Reading half an answer is worse
 * than reading none.
 *
 * NOTHING IS FABRICATED. A patient with no submission returns
 * `submissions: []` — never an empty form rendered as if answered, and never
 * "no known allergies" inferred from an unanswered allergy question.
 */
class MobileDoctorPreconsultController extends Controller
{
    private const DATA_CATEGORY = 'patient_reported_intake';

    private const RESOURCE_TYPE = 'PreconsultSubmission';

    /** How many submissions are ever returned in one read. */
    private const LIMIT = 10;

    public function __construct(private readonly PreconsultQuestionnaireService $service)
    {
    }

    /**
     * GET /api/mobile/doctor/patients/{patient}/preconsult
     *
     * The patient's submitted pre-consult questionnaires at this facility,
     * newest appointment first.
     */
    public function index(Request $request, string $patient): JsonResponse
    {
        $this->applyRequestLocale($request);

        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        $patientModel = $this->resolvePatient($patient);

        if ($patientModel === null) {
            return response()->json(['message' => __('preconsult.errors.patient_not_found')], 404);
        }

        $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $patientModel->id);

        if (! $verdict->holds()) {
            $this->auditDenial($patientModel->id, $clinicianId, $facilityId);

            return response()->json([
                'message'         => __('preconsult.errors.no_clinical_relationship'),
                'error_code'      => 'NO_CLINICAL_RELATIONSHIP',
                'required_action' => 'request_consent',
            ], 403);
        }

        // Self-reported history is history, not safety, so it waits behind the
        // patient's consent exactly as the problem list and encounters do —
        // it is not part of the identity/allergies/medicines preview.
        if (! $verdict->coversFullRecord()) {
            $this->auditDenial($patientModel->id, $clinicianId, $facilityId);

            return response()->json([
                'message'         => __('preconsult.errors.consent_required'),
                'error_code'      => 'CONSENT_REQUIRED',
                'required_action' => 'request_consent',
            ], 403);
        }

        if (! $this->auditRead($patientModel->id, $clinicianId, $facilityId, $verdict)) {
            // No trail, no disclosure.
            return response()->json(['message' => __('preconsult.errors.audit_unavailable')], 503);
        }

        $submissions = PreconsultSubmission::query()
            ->where('patient_id', $patientModel->id)
            ->where('facility_id', $facilityId)
            ->where('status', PreconsultStatus::Submitted->value)
            ->with('answers')
            ->orderByDesc('submitted_at')
            ->limit(self::LIMIT)
            ->get();

        return response()->json([
            'data' => [
                'patient_id' => $patientModel->id,
                // Repeated at the top of the payload as well as on every
                // answer, so a client that renders only the header still says
                // out loud where this came from.
                'provenance' => [
                    'value'       => \App\Models\PreconsultAnswer::PROVENANCE,
                    'label'       => __('preconsult.provenance.label'),
                    'description' => __('preconsult.provenance.description'),
                ],
                'clinician_notice' => __('preconsult.clinician_notice'),
                'submissions'      => $submissions
                    ->map(fn (PreconsultSubmission $s) => $this->service->present($s))
                    ->values(),
            ],
            'meta' => [
                'count' => $submissions->count(),
            ],
        ]);
    }

    // ── Internals ────────────────────────────────────────────────────────────

    /**
     * Exact patients.id or exact Health ID. No LIKE, no prefix, no name —
     * patient enumeration is forbidden platform-wide. Follows a merge, and
     * keeps the IsDemoRecord global scope so an isolated record reads as 404.
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

    private function auditRead(string $patientId, ?string $clinicianId, ?string $facilityId, ClinicalRelationshipGate $verdict): bool
    {
        if (! $clinicianId) {
            return false;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: $verdict->purpose(),
                dataCategory: self::DATA_CATEGORY,
                resourceType: self::RESOURCE_TYPE,
                resourceId: null,
                accessType: 'read',
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Pre-consult read refused: access log could not be written.', [
                'patient_id'   => $patientId,
                'clinician_id' => $clinicianId,
                'error'        => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** Best-effort: a refusal disclosed nothing, so a failed log must not 500 a correct 403. */
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
                dataCategory: self::DATA_CATEGORY,
                resourceType: self::RESOURCE_TYPE,
                resourceId: null,
                accessType: 'denied',
            );
        } catch (\Throwable $e) {
            Log::warning('Pre-consult denial was not access-logged.', [
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

    /** Platform rule: the facility comes only from the request attributes. */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function applyRequestLocale(Request $request): void
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header !== '' && str_starts_with(strtolower(ltrim($header)), 'fr')) {
            App::setLocale('fr');
        }
    }
}
