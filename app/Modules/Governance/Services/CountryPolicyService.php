<?php

namespace App\Modules\Governance\Services;

use App\Models\CountryPolicy;
use Carbon\Carbon;

class CountryPolicyService
{
    public function getSettings(string $countryCode): array
    {
        $now = Carbon::now();

        // Query active, published policy for the country code
        $policy = CountryPolicy::where('country_code', strtoupper($countryCode))
            ->where('status', 'published')
            ->where('effective_from', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $now);
            })
            ->first();

        if ($policy) {
            return $policy->settings_json;
        }

        // Return fallback defaults if no policy is registered
        return [
            'age_of_consent' => 18,
            'consent_required_for_treatment' => true,
            'emergency_access_review_required' => true,
            'small_cell_threshold' => 5,
            'retention_period_years' => 10,
        ];
    }

    public function publishPolicy(
        string $countryCode,
        string $name,
        string $version,
        array $settings
    ): CountryPolicy {
        $now = Carbon::now();
        $code = strtoupper($countryCode);

        // Retire any active policy version for the same country code
        CountryPolicy::where('country_code', $code)
            ->where('status', 'published')
            ->update([
                'status' => 'retired',
                'effective_to' => $now,
            ]);

        $policy = new CountryPolicy();
        $policy->country_code = $code;
        $policy->name = $name;
        $policy->version = $version;
        $policy->effective_from = $now;
        $policy->settings_json = $settings;
        $policy->status = 'published';
        $policy->save();

        return $policy;
    }
}
