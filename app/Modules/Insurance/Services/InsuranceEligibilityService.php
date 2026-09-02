<?php

namespace App\Modules\Insurance\Services;

use App\Models\EligibilityCheck;
use App\Models\PatientInsurancePolicy;

class InsuranceEligibilityService
{
    /**
     * Perform a manual eligibility check on a patient policy.
     */
    public function checkEligibility(
        string $policyId,
        string $actorId,
        string $status,
        ?string $notes = null,
        string $source = 'manual'
    ): EligibilityCheck {
        $policy = PatientInsurancePolicy::findOrFail($policyId);

        $check = EligibilityCheck::create([
            'patient_insurance_policy_id' => $policy->id,
            'checked_by' => $actorId,
            'status' => $status,
            'response_notes' => $notes,
            'source' => $source,
            'checked_at' => now(),
        ]);

        // Update policy status to match eligibility result
        if ($status === 'eligible') {
            $policy->update(['status' => 'active']);
        } elseif ($status === 'not_eligible') {
            $policy->update(['status' => 'inactive']);
        } elseif ($status === 'expired') {
            $policy->update(['status' => 'expired']);
        }

        return $check;
    }

    /**
     * Get latest eligibility check for a policy.
     */
    public function getLatest(string $policyId): ?EligibilityCheck
    {
        return EligibilityCheck::where('patient_insurance_policy_id', $policyId)
            ->orderByDesc('checked_at')
            ->first();
    }
}
