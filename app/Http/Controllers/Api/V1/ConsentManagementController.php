<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsentGrantResource;
use App\Http\Resources\ConsentRequestResource;
use App\Http\Resources\ConsentRevocationResource;
use App\Modules\ConsentManagement\Services\ConsentManagementService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ConsentManagementController — Clinical Consent API.
 *
 * Manages explicit patient consent for clinical procedures, data access,
 * and cross-facility sharing. This is distinct from legal document acceptance
 * (handled by LegalDocumentController).
 *
 * Consent lifecycle:
 *   request → pending_patient_approval → grant (approved) → revoke
 *
 * All operations are audited per GDPR / HIPAA consent tracking requirements.
 * Routes protected by VerifyIntegrationClient middleware.
 *
 * Endpoints:
 *  POST  /v1/consents/request          — create a new consent request
 *  POST  /v1/consents/{id}/grant       — authorise/approve a pending request
 *  POST  /v1/consents/grants/{id}/revoke — revoke an active grant
 */
class ConsentManagementController extends Controller
{
    public function __construct(
        private readonly ConsentManagementService $service,
        private readonly DocumentIssuanceService  $issuance
    ) {}

    /**
     * Create a consent request for a patient.
     *
     * Body: { patient_id, provider_id, purpose, scope, duration_minutes?, actor_id? }
     * facility_id from middleware attributes.
     *
     * scope examples: read_medical_records, share_with_specialist, emergency_disclosure
     */
    public function request(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved_id')], 422);
        }

        $validated = $request->validate([
            'patient_id'       => ['required', 'uuid', 'exists:patients,id'],
            'provider_id'      => ['nullable', 'uuid', 'exists:users,id'],
            'purpose'          => ['required', 'string', 'max:500'],
            'scope'            => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'], // max 7 days
            'actor_id'         => ['nullable', 'uuid'],
        ]);

        $consentRequest = $this->service->requestConsent(
            array_merge($validated, ['facility_id' => $facilityId]),
            $validated['actor_id'] ?? null
        );

        try {
            $requestId = is_array($consentRequest) ? ($consentRequest['id'] ?? null) : ($consentRequest->id ?? null);
            $this->issuance->issueFromModel(
                'CNS',
                'Consent Form — ' . $validated['scope'],
                ['consent_request_id' => $requestId, 'patient_id' => $validated['patient_id'], 'purpose' => $validated['purpose'], 'scope' => $validated['scope'], 'duration_minutes' => $validated['duration_minutes'] ?? null],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['actor_id'] ?? null
            );
        } catch (\Throwable) {}

        return response()->json([
            'message' => __('api.consent_request_created_pending'),
            'data'    => ConsentRequestResource::make($consentRequest),
        ], 201);
    }

    /**
     * Approve a pending consent request — creates a ConsentGrant.
     *
     * Body: { authorizing_actor, actor_id? }
     * authorizing_actor: the patient or their legal representative
     */
    public function grant(string $requestId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'authorizing_actor' => ['required', 'string', 'max:255'],
            'actor_id'          => ['nullable', 'uuid'],
        ]);

        try {
            $grant = $this->service->grantConsent(
                $requestId,
                $validated['authorizing_actor'],
                $validated['actor_id'] ?? null
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.consent_granted'),
            'data'    => ConsentGrantResource::make($grant),
        ], 201);
    }

    /**
     * Revoke an active consent grant.
     *
     * Body: { reason (min:10), actor_id? }
     * reason is required for audit trail — consent revocation must be documented.
     */
    public function revoke(string $grantId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason'   => ['required', 'string', 'min:10', 'max:1000'],
            'actor_id' => ['nullable', 'uuid'],
        ]);

        try {
            $revocation = $this->service->revokeConsent(
                $grantId,
                $validated['reason'],
                $validated['actor_id'] ?? null
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.consent_revoked'),
            'data'    => ConsentRevocationResource::make($revocation),
        ]);
    }
}
