<?php
namespace App\Services\PatientEngagement;

use App\Models\CarePlan;
use App\Models\CarePlanGoal;

class CarePlanService
{
    public function createPlan(
        string  $patientId,
        string  $providerId,
        string  $facilityId,
        string  $title,
        string  $description,
        string  $startDate,
        ?string $endDate = null,
    ): CarePlan {
        return CarePlan::create([
            'patient_id'  => $patientId,
            'facility_id' => $facilityId,
            'created_by'  => $providerId,
            'title'       => $title,
            'description' => $description,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'status'      => 'active',
        ]);
    }

    public function addGoal(
        string  $planId,
        string  $title,
        ?string $targetDate  = null,
        string  $category    = 'clinical',
        ?string $description = null,
    ): CarePlanGoal {
        return CarePlanGoal::create([
            'care_plan_id' => $planId,
            'goal_text'    => $title,
            'target_date'  => $targetDate,
            'status'       => 'pending',
            'notes'        => $description,
        ]);
    }
}
