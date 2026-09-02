<?php

namespace App\Modules\CareMap\Services;

use App\Models\CareFacilityInsurance;

class InsuranceNetworkSearchService
{
    /**
     * Get insurance network accepted facilities
     */
    public function getNetworkFacilities($insuranceName)
    {
        $insurancePlans = CareFacilityInsurance::where('insurance_name', 'like', "%{$insuranceName}%")
            ->where('status', 'active')
            ->with('facility')
            ->get();

        return $insurancePlans->map(function ($plan) {
            $facility = $plan->facility;
            $facility->matched_insurance = $plan;
            return $facility;
        });
    }
}
