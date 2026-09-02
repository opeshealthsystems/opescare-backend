<?php

namespace App\Modules\WardManagement\Services;

use App\Models\DischargePlan;
use App\Models\Admission;

/**
 * DischargePlanningService — Module 19 (Ward / Admission / Bed Management)
 *
 * Manages discharge planning workflow from draft to approval.
 * A discharge plan must be approved before a patient can be discharged.
 */
class DischargePlanningService
{
    /**
     * Create a draft discharge plan.
     */
    public function createDraft(array $data): DischargePlan
    {
        return DischargePlan::create(array_merge($data, ['status' => 'draft']));
    }

    /**
     * Mark discharge plan as ready for review.
     */
    public function markReady(DischargePlan $plan): void
    {
        if ($plan->status !== 'draft') {
            throw new \RuntimeException('Only draft plans can be marked ready.');
        }
        $plan->update(['status' => 'ready']);
    }

    /**
     * Approve a discharge plan.
     */
    public function approve(DischargePlan $plan, string $approvedBy): void
    {
        if (! in_array($plan->status, ['ready', 'draft'])) {
            throw new \RuntimeException('Only draft or ready plans can be approved.');
        }
        $plan->approve($approvedBy);
    }

    /**
     * Check if a given admission has an approved discharge plan.
     */
    public function hasApprovedPlan(Admission $admission): bool
    {
        return DischargePlan::where('admission_id', $admission->id)
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Get the latest discharge plan for an admission.
     */
    public function latestPlan(Admission $admission): ?DischargePlan
    {
        return DischargePlan::where('admission_id', $admission->id)
            ->latest()
            ->first();
    }
}
