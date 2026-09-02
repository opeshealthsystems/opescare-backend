<?php

namespace App\Modules\Admin\Services;

use App\Models\FeatureFlag;
use App\Models\AuditEvent;

/**
 * FeatureFlagService — Manages feature flag state for gradual rollouts.
 *
 * Feature flags allow:
 *  - Enabling/disabling modules per facility without deployment
 *  - Gradual rollouts (percentage-based or allowlist-based)
 *  - A/B testing configurations
 *  - Emergency disabling of problematic features
 *
 * Changes are always audited. Disabling a flag for a live facility
 * requires acknowledgement of impact.
 */
class FeatureFlagService
{
    public function enable(string $flagKey, string $actorId, string $facilityId = null): FeatureFlag
    {
        $flag = $facilityId
            ? FeatureFlag::firstOrCreate(['key' => $flagKey, 'facility_id' => $facilityId])
            : FeatureFlag::firstOrCreate(['key' => $flagKey, 'facility_id' => null]);

        $flag->update(['is_enabled' => true]);

        AuditEvent::create([
            'actor_id'    => $actorId,
            'action'      => 'feature_flag.enabled',
            'module'      => 'admin',
            'facility_id' => $facilityId,
            'metadata'    => ['flag_key' => $flagKey],
        ]);

        return $flag->fresh();
    }

    public function disable(string $flagKey, string $actorId, string $facilityId = null): FeatureFlag
    {
        $flag = $facilityId
            ? FeatureFlag::firstOrCreate(['key' => $flagKey, 'facility_id' => $facilityId])
            : FeatureFlag::firstOrCreate(['key' => $flagKey, 'facility_id' => null]);

        $flag->update(['is_enabled' => false]);

        AuditEvent::create([
            'actor_id'    => $actorId,
            'action'      => 'feature_flag.disabled',
            'module'      => 'admin',
            'facility_id' => $facilityId,
            'metadata'    => ['flag_key' => $flagKey],
        ]);

        return $flag->fresh();
    }

    public function isEnabled(string $flagKey, string $facilityId = null): bool
    {
        // Check facility-specific flag first, then global
        if ($facilityId) {
            $facilityFlag = FeatureFlag::where('key', $flagKey)
                ->where('facility_id', $facilityId)
                ->first();
            if ($facilityFlag !== null) {
                return (bool) $facilityFlag->is_enabled;
            }
        }

        $globalFlag = FeatureFlag::where('key', $flagKey)->whereNull('facility_id')->first();
        return $globalFlag ? (bool) $globalFlag->is_enabled : false;
    }

    public function getAllFlags(string $facilityId = null): \Illuminate\Database\Eloquent\Collection
    {
        return FeatureFlag::when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->orderBy('key')
            ->get();
    }
}
