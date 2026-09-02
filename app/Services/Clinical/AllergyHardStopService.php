<?php

namespace App\Services\Clinical;

use App\Models\AllergyAlertRule;
use App\Models\AllergyRecord;

/**
 * Allergy Hard-Stop Service
 *
 * Checks whether prescribing a drug is BLOCKED for a patient due to a
 * documented allergy with is_hard_stop=true.
 *
 * Hard stops CANNOT be self-overridden. They require a supervisor co-sign
 * (a separate UI flow) or a documented emergency override with an audit trail.
 *
 * Advisory alerts (is_hard_stop=false) continue to be handled by ClinicalDecisionSupportService.
 */
class AllergyHardStopService
{
    /**
     * Check if prescribing a drug is hard-blocked for a patient.
     *
     * @param  string  $patientId
     * @param  string  $drugCode
     * @param  string|null  $facilityId  Check facility-specific rules first, then global
     * @return array  {blocked: bool, rules: AllergyAlertRule[], allergens: string[]}
     */
    public function checkHardStop(string $patientId, string $drugCode, ?string $facilityId = null): array
    {
        // Get patient's documented allergens
        $allergenCodes = AllergyRecord::where('patient_id', $patientId)
            ->pluck('substance')
            ->map(fn($s) => strtoupper(trim($s)))
            ->all();

        if (empty($allergenCodes)) {
            return ['blocked' => false, 'rules' => [], 'allergens' => []];
        }

        // Find hard-stop rules for this drug
        $hardStopRules = AllergyAlertRule::where('drug_code', strtoupper($drugCode))
            ->where('is_active', true)
            ->where('is_hard_stop', true)
            ->where(function ($q) use ($facilityId) {
                $q->whereNull('facility_id');
                if ($facilityId) {
                    $q->orWhere('facility_id', $facilityId);
                }
            })
            ->get();

        $triggeredRules  = [];
        $triggeredAllergens = [];

        foreach ($hardStopRules as $rule) {
            // Direct allergen match
            if (in_array(strtoupper($rule->allergen_code), $allergenCodes, true)) {
                $triggeredRules[]    = $rule;
                $triggeredAllergens[] = $rule->allergen_code;
            }
            // Cross-reactivity match
            if ($rule->cross_reactivity_group &&
                in_array(strtoupper($rule->cross_reactivity_group), $allergenCodes, true)) {
                $triggeredRules[]    = $rule;
                $triggeredAllergens[] = $rule->cross_reactivity_group;
            }
        }

        return [
            'blocked'   => !empty($triggeredRules),
            'rules'     => $triggeredRules,
            'allergens' => array_unique($triggeredAllergens),
        ];
    }

    /**
     * Get all hard-stop rules for a facility (global + facility-specific).
     */
    public function getFacilityHardStops(?string $facilityId = null): \Illuminate\Support\Collection
    {
        return AllergyAlertRule::where('is_active', true)
            ->where('is_hard_stop', true)
            ->where(function ($q) use ($facilityId) {
                $q->whereNull('facility_id');
                if ($facilityId) {
                    $q->orWhere('facility_id', $facilityId);
                }
            })
            ->get();
    }
}
