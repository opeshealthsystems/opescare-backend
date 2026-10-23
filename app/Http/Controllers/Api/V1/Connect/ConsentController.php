<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Http\Controllers\Api\V1\Connect\Concerns\ConnectResponses;
use App\Http\Controllers\Controller;
use App\Models\ConsentGrant;
use App\Models\ConsentRequest;
use App\Models\Patient;
use App\Modules\Governance\Services\ConsentService;
use App\Services\Connect\ConnectPatientDirectory;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * OpesCare Connect — partner consent by Health ID.
 *
 *   POST /api/v1/connect/consents/request-medical-id   consents:write
 *   GET  /api/v1/connect/consents/status/{health_id}   consents:read
 *
 * The platform's request-medical-id had no auth middleware, so its request
 * carried no facility and the grant it produced could never pass a
 * facility-bound consent check. Here the requesting facility is the one bound
 * to the bearer token (and only that), the request goes through the same
 * ConsentService loop the facility desks use — the patient is notified and
 * approves or denies in their OpesCare app/portal — and the resulting grant is
 * exactly what FhirIngestGuard and the record pull check for.
 */
class ConsentController extends Controller
{
    use ConnectResponses;

    /** Scopes a partner may ask a patient for over Connect. */
    public const REQUESTABLE_SCOPES = ['patients:read'];

    /** How long the ASK stands unanswered (the grant length is expires_in_days). */
    public const REQUEST_TTL_HOURS = 72;

    public function __construct(
        private readonly ConnectPatientDirectory $directory,
        private readonly ConsentService $consents,
    ) {}

    public function requestMedicalId(Request $request): JsonResponse
    {
        $facilityId = $this->tokenFacility($request);
        if ($facilityId === null) {
            return $this->facilityRequired();
        }

        $validator = Validator::make($request->all(), [
            'health_id'         => 'required|string|max:30',
            // A stable coded token: ConsentService::verifyAccess joins on it.
            'purpose'           => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_.:-]+$/'],
            'requested_scope'   => 'required|array|min:1|max:10',
            'requested_scope.*' => 'string|in:' . implode(',', self::REQUESTABLE_SCOPES),
            'expires_in_days'   => 'nullable|integer|min:1|max:365',
            'justification'     => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->error('INVALID_PAYLOAD', __('connect_api.invalid_payload'), 422, ['errors' => $validator->errors()]);
        }
        $v = $validator->validated();

        [$patient] = $this->directory->resolveHealthId($request, $v['health_id']);
        if (! $patient) {
            return $this->error('HEALTH_ID_NOT_FOUND', __('connect_api.health_id_not_found'), 404);
        }
        if (! $this->directory->isActive($patient)) {
            return $this->error('HEALTH_ID_SUSPENDED', __('connect_api.health_id_suspended'), 403);
        }

        $scopes = array_values(array_unique($v['requested_scope']));
        $days = (int) ($v['expires_in_days'] ?? 30);

        // Already granted for everything asked: say so, with the grant id the record pull needs.
        $grant = $this->activeGrant($patient, $facilityId);
        if ($grant && array_diff($scopes, (array) $grant->scope) === []) {
            $this->audit($request, 'connect_consent_request_already_granted', $patient, ['purpose' => $v['purpose'], 'grant_id' => (string) $grant->id]);

            return response()->json([
                'status'           => 'success',
                // Generic "id" is the grant id, as in the status endpoint (partners store it as the grant reference).
                'id'               => (string) $grant->id,
                'consent_id'       => $grant->consent_request_id ? (string) $grant->consent_request_id : null,
                'request_id'       => $grant->consent_request_id ? (string) $grant->consent_request_id : null,
                'consent_status'   => 'granted',
                'grant_id'         => (string) $grant->id,
                'scope'            => array_values((array) $grant->scope),
                'grant_expires_at' => $grant->expires_at->toIso8601String(),
                'message'          => __('connect_api.consent_already_granted'),
            ]);
        }

        $this->consents->expireStaleRequests((string) $patient->getKey(), $facilityId);
        $open = $this->openRequest((string) $patient->getKey(), $facilityId);
        if ($open) {
            return $this->pending($open, 200, __('connect_api.consent_already_open'), true);
        }

        try {
            $consentRequest = $this->consents->requestConsent(
                (string) $patient->getKey(),
                $facilityId,
                null,
                $v['purpose'],
                $scopes,
                $days * 1440,
                Carbon::now()->addHours(self::REQUEST_TTL_HOURS),
                $v['justification'] ?? null,
            );
        } catch (QueryException $e) {
            // 23505: a concurrent request from the same facility won the one-open-request index.
            $open = $this->openRequest((string) $patient->getKey(), $facilityId);
            if (($e->errorInfo[0] ?? null) === '23505') {
                return $open
                    ? $this->pending($open, 200, __('connect_api.consent_already_open'), true)
                    : $this->error('CONSENT_REQUEST_CONFLICT', __('connect_api.consent_already_open'), 409);
            }
            throw $e;
        }

        // The request lives in the patient's partition, not the host's demo mode.
        if ((bool) $consentRequest->is_demo !== (bool) $patient->is_demo) {
            $consentRequest->forceFill(['is_demo' => (bool) $patient->is_demo])->saveQuietly();
        }

        $this->audit($request, 'connect_consent_requested', $patient, [
            'purpose'         => $v['purpose'],
            'request_id'      => (string) $consentRequest->id,
            'requested_scope' => $scopes,
            'expires_in_days' => $days,
        ]);

        return $this->pending($consentRequest, 201, __('connect_api.consent_requested'), false);
    }

    /**
     * The calling facility's consent standing with this patient:
     * granted | pending | denied | revoked | expired | none.
     */
    public function status(Request $request, string $healthId): JsonResponse
    {
        $facilityId = $this->tokenFacility($request);
        if ($facilityId === null) {
            return $this->facilityRequired();
        }

        [$patient] = $this->directory->resolveHealthId($request, $healthId);
        if (! $patient) {
            return $this->error('HEALTH_ID_NOT_FOUND', __('connect_api.health_id_not_found'), 404);
        }

        $patientId = (string) $patient->getKey();
        $this->consents->expireStaleRequests($patientId, $facilityId);

        $state = $this->standing($patientId, $facilityId);
        $this->audit($request, 'connect_consent_status_checked', $patient, ['consent_status' => $state['status']]);

        return response()->json(['health_id' => $patient->health_id] + $state + [
            'consent_status' => $state['status'],
            // Partners that read a generic "id" get the grant id when there is one.
            'id'             => $state['grant_id'] ?? $state['request_id'],
        ]);
    }

    // ── internals ──────────────────────────────────────────────────────────

    private function standing(string $patientId, string $facilityId): array
    {
        $grant = $this->activeGrant($patientId, $facilityId);
        if ($grant) {
            return $this->grantState($grant, 'granted');
        }

        $open = $this->openRequest($patientId, $facilityId);
        if ($open) {
            return $this->requestState($open, 'pending');
        }

        $lastGrant = ConsentGrant::withoutGlobalScopes()
            ->where('patient_id', $patientId)->where('facility_id', $facilityId)
            ->latest('updated_at')->first();
        $lastRequest = ConsentRequest::withoutGlobalScopes()
            ->where('patient_id', $patientId)->where('requesting_facility_id', $facilityId)
            ->whereIn('status', [ConsentService::STATUS_DENIED, ConsentService::STATUS_EXPIRED, ConsentService::STATUS_WITHDRAWN])
            ->whereDoesntHave('grant')
            ->latest('updated_at')->first();

        if ($lastGrant && (! $lastRequest || $lastGrant->updated_at >= $lastRequest->updated_at)) {
            return $this->grantState($lastGrant, $lastGrant->status === 'revoked' ? 'revoked' : 'expired');
        }
        if ($lastRequest) {
            return $this->requestState($lastRequest, $lastRequest->status === ConsentService::STATUS_DENIED ? 'denied' : 'expired');
        }

        return ['status' => 'none', 'grant_id' => null, 'request_id' => null, 'scope' => [], 'expires_at' => null];
    }

    /** Open ask, partition-blind (the patient was already resolved inside the token's partition). */
    private function openRequest(string $patientId, string $facilityId): ?ConsentRequest
    {
        return ConsentRequest::withoutGlobalScopes()
            ->where('patient_id', $patientId)->where('requesting_facility_id', $facilityId)
            ->whereIn('status', ConsentService::OPEN_STATUSES)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', Carbon::now()))
            ->latest()->first();
    }

    private function grantState(ConsentGrant $grant, string $status): array
    {
        return [
            'status'     => $status,
            'grant_id'   => (string) $grant->id,
            'request_id' => $grant->consent_request_id ? (string) $grant->consent_request_id : null,
            'scope'      => array_values((array) $grant->scope),
            'expires_at' => optional($grant->expires_at)->toIso8601String(),
        ];
    }

    private function requestState(ConsentRequest $request, string $status): array
    {
        return [
            'status'     => $status,
            'grant_id'   => null,
            'request_id' => (string) $request->id,
            'scope'      => array_values((array) $request->requested_scope),
            'expires_at' => optional($request->expires_at ? Carbon::parse($request->expires_at) : null)->toIso8601String(),
        ];
    }

    private function activeGrant(Patient|string $patient, string $facilityId): ?ConsentGrant
    {
        return ConsentGrant::withoutGlobalScopes()
            ->where('patient_id', $patient instanceof Patient ? $patient->getKey() : $patient)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now())
            ->latest('expires_at')
            ->first();
    }

    private function pending(ConsentRequest $request, int $http, string $message, bool $existing): JsonResponse
    {
        return response()->json([
            'status'          => 'success',
            'consent_id'      => (string) $request->id,
            'request_id'      => (string) $request->id,
            'consent_status'  => 'pending',
            'grant_id'        => null,
            'requested_scope' => array_values((array) $request->requested_scope),
            'expires_in_days' => intdiv((int) $request->duration_minutes, 1440),
            // When the ASK lapses unanswered.
            'expires_at'      => $request->expires_at ? Carbon::parse($request->expires_at)->toIso8601String() : null,
            'existing'        => $existing,
            'message'         => $message,
        ], $http);
    }
}
