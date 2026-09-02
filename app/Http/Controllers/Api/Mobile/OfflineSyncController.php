<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\LocalCachePolicy;
use App\Models\User;
use App\Modules\Offline\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class OfflineSyncController extends Controller
{
    public function createPolicy(Request $request, SyncService $service): JsonResponse
    {
        $validated = $request->validate([
            'facility_id' => ['nullable', 'uuid'],
            'device_id' => ['required', 'string', 'max:120'],
            'allowed_scopes' => ['required', 'array', 'min:1'],
            'allowed_scopes.*' => ['string'],
            'emergency_access' => ['nullable', 'boolean'],
        ]);

        // The subject and the actor are the authenticated patient, taken from
        // the middleware attribute — never from request input. Both used to be
        // caller-supplied, which let any patient register an encrypted
        // offline-cache policy naming ANOTHER patient's record, and attribute
        // the audit trail to whoever they liked. Same rule the platform already
        // enforces for facility_id: identity comes from the token, not the body.
        $patientId = $request->attributes->get('patient_id');

        if (! $patientId) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        $validated['patient_id'] = $patientId;

        try {
            $policy = $service->createLocalCachePolicy($validated, $this->actorUserId($patientId));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $this->serializePolicy($policy)], 201);
    }

    public function queue(LocalCachePolicy $policy, Request $request, SyncService $service): JsonResponse
    {
        $validated = $request->validate([
            'payload' => ['required', 'array'],
        ]);

        $patientId = $request->attributes->get('patient_id');

        if (! $patientId) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        // The policy is route-model-bound, so without this check any patient
        // could push an encrypted payload into ANY other patient's cache policy
        // just by knowing (or guessing) its id. 404 rather than 403 so the
        // endpoint does not confirm that a foreign policy exists.
        if ($policy->patient_id !== $patientId) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        try {
            // Actor is the authenticated patient, not a caller-supplied id —
            // otherwise the audit trail can be attributed to anyone.
            $queue = $service->queueEncryptedPayload($policy, $validated['payload'], $this->actorUserId($patientId));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $queue->id,
                'local_cache_policy_id' => $queue->local_cache_policy_id,
                'status' => $queue->status,
                'scopes' => $queue->scopes,
                'payload_hash' => $queue->payload_hash,
                'retry_count' => $queue->retry_count,
            ],
        ], 201);
    }

    /**
     * The audit actor for a patient-initiated action.
     *
     * local_cache_policies.created_by is a foreign key to `users`, and a
     * Patient id is not a User id — passing the patient through directly
     * violates the constraint. Resolve the linked portal account instead, and
     * fall back to null (the column is nullable) for a patient who has none,
     * rather than writing an id that cannot be traced.
     */
    private function actorUserId(string $patientId): ?string
    {
        return User::where('patient_id', $patientId)->value('id');
    }

    private function serializePolicy(LocalCachePolicy $policy): array
    {
        return [
            'id' => $policy->id,
            'patient_id' => $policy->patient_id,
            'facility_id' => $policy->facility_id,
            'device_id' => $policy->device_id,
            'allowed_scopes' => $policy->allowed_scopes,
            'encryption_required' => $policy->encryption_required,
            'encryption_policy' => $policy->encryption_policy,
            'emergency_access' => $policy->emergency_access,
            'review_required' => $policy->review_required,
            'status' => $policy->status,
            'expires_at' => optional($policy->expires_at)->toISOString(),
        ];
    }
}
