<?php

namespace App\Modules\ConsentManagement\Services;

use App\Models\ConsentRequest;
use App\Models\ConsentGrant;
use App\Models\ConsentRevocation;
use App\Models\AuditEvent;
use Exception;
use Illuminate\Support\Facades\DB;

class ConsentManagementService
{
    /**
     * Create a new consent request.
     */
    public function requestConsent(array $data, ?string $actorId = null): ConsentRequest
    {
        DB::beginTransaction();
        try {
            $request = ConsentRequest::create([
                'patient_id' => $data['patient_id'],
                'requesting_facility_id' => $data['facility_id'],
                'requesting_user_id' => $data['provider_id'] ?? null,
                'purpose' => $data['purpose'],
                'requested_scope' => $data['scope'],
                'duration_minutes' => $data['duration_minutes'] ?? 60,
                'status' => 'pending_patient_approval',
            ]);

            AuditEvent::create([
                'actor_id' => $actorId ?? $data['provider_id'] ?? null,
                'facility_id' => $data['facility_id'],
                'patient_id' => $data['patient_id'],
                'action_type' => 'create',
                'resource_type' => 'consent_request',
                'resource_id' => $request->id,
                'reason' => "Consent requested for {$data['purpose']}",
            ]);

            DB::commit();
            return $request;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve a consent request and generate the grant.
     */
    public function grantConsent(string $requestId, string $authorizingActor, ?string $actorId = null): ConsentGrant
    {
        DB::beginTransaction();
        try {
            $request = ConsentRequest::findOrFail($requestId);

            if ($request->status !== 'pending_patient_approval') {
                throw new Exception("Consent request is not in a pending state.");
            }

            $grant = ConsentGrant::create([
                'patient_id' => $request->patient_id,
                'facility_id' => $request->requesting_facility_id,
                'consent_request_id' => $request->id,
                'authorizing_actor' => $authorizingActor,
                'scope' => $request->requested_scope,
                'status' => 'active',
                'expires_at' => now()->addMinutes($request->duration_minutes),
            ]);

            $request->update(['status' => 'approved']);

            AuditEvent::create([
                'actor_id' => $actorId,
                'facility_id' => $request->requesting_facility_id,
                'patient_id' => $request->patient_id,
                'action_type' => 'approve',
                'resource_type' => 'consent_grant',
                'resource_id' => $grant->id,
                'reason' => "Consent granted by {$authorizingActor}",
            ]);

            DB::commit();
            return $grant;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Revoke an active consent grant.
     */
    public function revokeConsent(string $grantId, string $reason, ?string $actorId = null): ConsentRevocation
    {
        DB::beginTransaction();
        try {
            $grant = ConsentGrant::findOrFail($grantId);

            if ($grant->status !== 'active') {
                throw new Exception("Consent grant is not active.");
            }

            $grant->update(['status' => 'revoked']);

            $revocation = ConsentRevocation::create([
                'consent_grant_id' => $grant->id,
                'revoked_by' => $actorId,
                'reason' => $reason,
            ]);

            AuditEvent::create([
                'actor_id' => $actorId,
                'facility_id' => $grant->facility_id,
                'patient_id' => $grant->patient_id,
                'action_type' => 'revoke',
                'resource_type' => 'consent_grant',
                'resource_id' => $grant->id,
                'reason' => $reason,
            ]);

            DB::commit();
            return $revocation;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
