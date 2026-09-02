<?php

namespace App\Modules\Offline\Services;

use App\Models\OfflineCachePolicy;
use App\Models\LocalCachePolicy;

/**
 * OfflinePolicyService — Determines what data can be cached offline per scope.
 *
 * SECURITY RULE: Full EMR MUST NEVER be cached offline by default.
 * Each resource type has a specific policy governing whether and how much
 * can be stored on device for offline use.
 *
 * Policies are defined in OfflineCachePolicy and can be overridden per facility.
 * Devices that do not have an offline policy must default to NO caching.
 */
class OfflinePolicyService
{
    /**
     * Returns the effective cache policy for a scope.
     * Returns null if no policy exists (default: no caching allowed).
     */
    public function getPolicyFor(string $scope, string $facilityId = null): ?OfflineCachePolicy
    {
        return OfflineCachePolicy::policyFor($scope, $facilityId);
    }

    /**
     * Checks if offline caching is allowed for a given scope.
     * Defaults to false (deny) when no policy exists.
     */
    public function isCachingAllowed(string $scope, string $facilityId = null): bool
    {
        $policy = $this->getPolicyFor($scope, $facilityId);
        return $policy !== null && $policy->isEnabled();
    }

    /**
     * Returns the maximum number of records allowed to be cached for this scope.
     */
    public function getMaxRecords(string $scope, string $facilityId = null): int
    {
        $policy = $this->getPolicyFor($scope, $facilityId);
        if (! $policy || ! $policy->isEnabled()) {
            return 0;
        }
        return $policy->max_records;
    }

    /**
     * Returns all scopes allowed for offline caching for a facility.
     */
    public function getAllowedScopesForFacility(string $facilityId): array
    {
        return OfflineCachePolicy::where('is_cacheable', true)
            ->where(function ($q) use ($facilityId) {
                $q->where('facility_id', $facilityId)->orWhereNull('facility_id');
            })
            ->where('max_records', '>', 0)
            ->pluck('scope')
            ->toArray();
    }
}
