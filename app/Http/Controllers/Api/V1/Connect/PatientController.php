<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Http\Controllers\Api\V1\Connect\Concerns\ConnectResponses;
use App\Http\Controllers\Controller;
use App\Models\ConsentGrant;
use App\Models\Patient;
use App\Services\Connect\ConnectPatientDirectory;
use App\Services\Connect\PatientSummaryBuilder;
use App\Services\Identity\HealthIdGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * OpesCare Connect — partner patient endpoints (Bearer RS256, scope-checked by
 * route middleware, environment-partitioned by ConnectPatientDirectory).
 *
 *   POST /api/v1/connect/patients/resolve               health_id:read
 *   GET  /api/v1/connect/patients/verify/{health_id}    health_id:read
 *   GET  /api/v1/connect/patients/{health_id}/summary   patients:read + X-Consent-Grant-Id
 *
 * Design source: the platform's HealthIdResolutionController and
 * RecordController::pullSummary, with two deliberate Finder differences:
 *  - resolve NEVER creates a patient (attach-only, like FHIR ingest); a miss
 *    is 404 PATIENT_NOT_FOUND and the patient registers in the OpesCare app;
 *  - a demographic lookup requires consent_acknowledged BEFORE searching, so
 *    "does this person exist?" is never answered without the patient's say,
 *    and an ambiguous match returns no Health IDs at all.
 */
class PatientController extends Controller
{
    use ConnectResponses;

    public const SUMMARY_SCOPE = 'patients:read';

    public function __construct(
        private readonly ConnectPatientDirectory $directory,
        private readonly HealthIdGeneratorService $healthIds,
    ) {}

    public function resolve(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'health_id'            => 'nullable|string|max:30',
            'first_name'           => 'nullable|string|max:100',
            'last_name'            => 'nullable|string|max:100',
            'full_name'            => 'nullable|string|max:200',
            'date_of_birth'        => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'dob'                  => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'sex'                  => 'nullable|string|in:male,female',
            'phone'                => 'nullable|string|max:30',
            'phone_number'         => 'nullable|string|max:30',
            'country_code'         => 'nullable|string|size:2',
            'purpose'              => 'required|string|max:100',
            'external_reference'   => 'nullable|string|max:200',
            'consent_acknowledged' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return $this->error('INVALID_PAYLOAD', __('connect_api.invalid_payload'), 422, ['errors' => $validator->errors()]);
        }
        $v = $validator->validated();

        $healthId  = isset($v['health_id']) ? strtoupper(trim($v['health_id'])) : null;
        $firstName = $v['first_name'] ?? null;
        $lastName  = $v['last_name'] ?? null;
        $fullName  = $v['full_name'] ?? null;
        $dob       = $v['date_of_birth'] ?? $v['dob'] ?? null;
        $phone     = $v['phone_number'] ?? $v['phone'] ?? null;
        $purpose   = $v['purpose'];

        $hasNames = (filled($firstName) && filled($lastName)) || filled($fullName);
        $hasDemographics = $hasNames && filled($dob);

        if (! filled($healthId) && ! $hasDemographics) {
            return $this->error('MISSING_LOOKUP_CRITERIA', __('connect_api.missing_lookup_criteria'), 422);
        }

        // 1. Health ID: exact key, alias, merge — inside the caller's partition.
        if (filled($healthId)) {
            [$patient, $viaAlias] = $this->directory->resolveHealthId($request, $healthId);
            if ($patient) {
                return $this->found($request, $patient, 1.0, $viaAlias ? 'health_id_alias' : 'health_id', $purpose);
            }
            if (! $hasDemographics) {
                $this->audit($request, 'connect_resolve_miss', null, ['purpose' => $purpose, 'method' => 'health_id']);

                return $this->error('HEALTH_ID_NOT_FOUND', __('connect_api.health_id_not_found'), 404);
            }
        }

        // 2. Demographics: only with the patient's agreement, asked BEFORE searching.
        if (! (bool) ($v['consent_acknowledged'] ?? false)) {
            return response()->json([
                'status'      => 'consent_required',
                'error_code'  => 'CONSENT_REQUIRED',
                'message'     => __('connect_api.consent_required_lookup'),
                'next_action' => 'obtain_patient_consent_then_retry',
            ], 422);
        }

        $matches = $this->directory->matchDemographics(
            $request,
            filled($firstName) && filled($lastName) ? $firstName : null,
            filled($firstName) && filled($lastName) ? $lastName : null,
            $fullName,
            $dob,
            $v['sex'] ?? null,
            $phone,
        );

        if ($matches === []) {
            $this->audit($request, 'connect_resolve_miss', null, ['purpose' => $purpose, 'method' => 'demographics']);

            return $this->error('PATIENT_NOT_FOUND', __('connect_api.patient_not_found'), 404, [
                'next_action' => 'patient_registers_on_opescare',
            ]);
        }

        // A unique best match (e.g. the phone agrees on only one) is a match; a tie is a human's call.
        if (count($matches) > 1 && $matches[0]['confidence'] <= $matches[1]['confidence']) {
            $this->audit($request, 'connect_resolve_ambiguous', null, ['purpose' => $purpose, 'candidates' => count($matches)]);

            return $this->error('AMBIGUOUS_MATCH', __('connect_api.ambiguous_match'), 409, [
                'status'      => 'ambiguous',
                'next_action' => 'provide_health_id_or_phone',
            ]);
        }

        return $this->found($request, $matches[0]['patient'], $matches[0]['confidence'], $matches[0]['method'], $purpose);
    }

    public function verify(Request $request, string $healthId): JsonResponse
    {
        $healthId = strtoupper(trim($healthId));

        if (! $this->healthIds->isValid($healthId)) {
            return response()->json([
                'status'     => 'invalid',
                'error_code' => 'HEALTH_ID_INVALID_FORMAT',
                'message'    => __('connect_api.health_id_invalid'),
            ], 422);
        }

        [$patient, $viaAlias] = $this->directory->resolveHealthId($request, $healthId);

        if (! $patient) {
            $this->audit($request, 'connect_verify_miss', null, ['method' => 'health_id']);

            return $this->error('HEALTH_ID_NOT_FOUND', __('connect_api.health_id_not_found'), 404);
        }

        $summary = $this->directory->summary($patient);
        $this->audit($request, 'health_id_verified', $patient, ['via_alias' => $viaAlias]);

        return response()->json([
            'status'             => 'valid',
            'health_id'          => $patient->health_id,
            'active'             => $summary['active'],
            'verified'           => $summary['verified'],
            'resolved_via_alias' => $viaAlias,
            'queried_health_id'  => $viaAlias ? $healthId : null,
            'patient'            => $summary,
        ]);
    }

    /**
     * The consented record. Every refusal is 403 CONSENT_REQUIRED (with a
     * `reason` for another facility's grant or a too-narrow scope, so a partner
     * closes its local grant) and none of them says whether the Health ID exists: the grant is checked before the
     * patient, so this endpoint is not a second existence oracle.
     */
    public function summary(Request $request, string $healthId): JsonResponse
    {
        $facilityId = $this->tokenFacility($request);
        if ($facilityId === null) {
            return $this->facilityRequired();
        }

        $grantId = (string) $request->header('X-Consent-Grant-Id', '');
        if (! Str::isUuid($grantId)) {
            return $this->error('CONSENT_REQUIRED', __('connect_api.consent_required'), 403);
        }

        $grant = ConsentGrant::withoutGlobalScopes()->whereKey($grantId)->first();
        if (! $grant) {
            return $this->error('CONSENT_REQUIRED', __('connect_api.consent_required'), 403);
        }
        if ((string) $grant->facility_id !== $facilityId) {
            return $this->error('CONSENT_REQUIRED', __('connect_api.consent_facility'), 403, ['reason' => 'facility_mismatch']);
        }

        [$patient] = $this->directory->resolveHealthId($request, $healthId);
        if (! $patient || (string) $patient->getKey() !== (string) $grant->patient_id) {
            return $this->error('CONSENT_REQUIRED', __('connect_api.consent_required'), 403);
        }

        if ($grant->status !== 'active' || ! $grant->expires_at || $grant->expires_at->isPast()) {
            return $this->error('CONSENT_REQUIRED', __('connect_api.consent_required'), 403, [
                'consent_status' => $grant->status === 'active' ? 'expired' : (string) $grant->status,
            ]);
        }

        $scopes = array_values((array) $grant->scope);
        if (! in_array(self::SUMMARY_SCOPE, $scopes, true) && ! in_array('*', $scopes, true)) {
            return $this->error('CONSENT_REQUIRED', __('connect_api.consent_scope'), 403, ['reason' => 'scope_insufficient']);
        }

        $sections = app(PatientSummaryBuilder::class)->build($patient);
        $purpose = mb_substr((string) $request->header('X-Purpose-Of-Use', 'treatment'), 0, 100) ?: 'treatment';

        $this->audit($request, 'connect_record_pulled', $patient, ['purpose' => $purpose, 'grant_id' => (string) $grant->id]);

        $identity = $this->directory->summary($patient);

        try {
            $year = $patient->date_of_birth?->year;
        } catch (\Throwable) {
            $year = null;
        }

        return response()->json([
            'status'               => 'success',
            'health_id'            => $patient->health_id,
            'summary_generated_at' => Carbon::now()->toIso8601String(),
            'verification_status'  => $identity['verification_status'],
            'consent'              => [
                'grant_id'   => (string) $grant->id,
                'scope'      => $scopes,
                'expires_at' => $grant->expires_at->toIso8601String(),
            ],
            'demographics' => [
                'display_name'  => trim($patient->first_name . ' ' . $patient->last_name),
                'sex'           => $identity['sex'],
                'year_of_birth' => $year,
                'country_code'  => $identity['country_code'],
            ],
            'sections' => $sections,
        ]);
    }

    // ── internals ──────────────────────────────────────────────────────────

    private function found(Request $request, Patient $patient, float $confidence, string $method, string $purpose): JsonResponse
    {
        $this->audit($request, 'health_id_resolved', $patient, ['purpose' => $purpose, 'method' => $method, 'confidence' => $confidence]);

        return response()->json([
            'status'      => 'found',
            'health_id'   => $patient->health_id,
            'match'       => ['confidence' => $confidence, 'method' => $method],
            'patient'     => $this->directory->summary($patient),
            'next_action' => 'use_health_id',
        ]);
    }
}
