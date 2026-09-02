<?php

namespace App\Modules\AccessControl\Services;

use App\Models\EmergencyAccessEvent;
use App\Models\EmergencyReviewCase;
use App\Models\AuditEvent;
use Exception;
use Illuminate\Support\Facades\DB;

class EmergencyAccessService
{
    /**
     * Creates an emergency "break-glass" access event.
     * The provider must supply a reason. This automatically triggers a review case.
     */
    public function logEmergencyAccess(array $data): EmergencyAccessEvent
    {
        if (empty($data['reason'])) {
            throw new Exception("A reason is mandatory for emergency break-glass access.");
        }

        DB::beginTransaction();

        try {
            $event = EmergencyAccessEvent::create([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'provider_id' => $data['provider_id'],
                'reason' => $data['reason'],
                'records_viewed' => $data['records_viewed'] ?? [],
            ]);

            // Automatically create a compliance review case
            EmergencyReviewCase::create([
                'emergency_access_event_id' => $event->id,
                'status' => 'pending',
            ]);

            // Log the mandatory audit event specifically tagged as an emergency override
            AuditEvent::create([
                'actor_id' => $data['provider_id'],
                'facility_id' => $data['facility_id'],
                'patient_id' => $data['patient_id'],
                'action_type' => 'read',
                'resource_type' => 'emergency_profile',
                'resource_id' => $event->id,
                'emergency_override' => true,
                'reason' => "Emergency Break-Glass Access: {$data['reason']}",
            ]);

            DB::commit();

            return $event;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
