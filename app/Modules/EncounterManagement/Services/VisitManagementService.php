<?php

namespace App\Modules\EncounterManagement\Services;

use App\Models\Visit;
use App\Models\AuditEvent;
use Exception;
use Illuminate\Support\Facades\DB;

class VisitManagementService
{
    /**
     * Creates a new patient visit (encounter) in the system.
     */
    public function createVisit(array $data, ?string $actorId = null): Visit
    {
        DB::beginTransaction();
        try {
            $visit = Visit::create([
                'patient_id' => $data['patient_id'],
                'facility_id' => $data['facility_id'],
                'provider_id' => $data['provider_id'] ?? null,
                'visit_type' => $data['visit_type'],
                'status' => 'open',
                'started_at' => now(),
            ]);

            AuditEvent::create([
                'actor_id' => $actorId ?? $data['provider_id'] ?? null,
                'facility_id' => $data['facility_id'],
                'patient_id' => $data['patient_id'],
                'encounter_id' => $visit->id,
                'action_type' => 'create',
                'resource_type' => 'visit',
                'resource_id' => $visit->id,
                'reason' => "Checked in patient for {$data['visit_type']} visit.",
            ]);

            DB::commit();
            return $visit;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
