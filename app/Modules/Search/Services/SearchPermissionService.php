<?php

namespace App\Modules\Search\Services;

use App\Models\SearchPermissionFilter;

/**
 * SearchPermissionService — Enforces per-role search scope restrictions.
 *
 * Before returning search results, every query must pass through this service.
 * Results outside the requester's permission scope are suppressed entirely —
 * not just hidden in the UI, but excluded from query results.
 *
 * Required per OPESCARE_STRATEGIC_MATURITY §3 and SearchPermissionFilter model.
 */
class SearchPermissionService
{
    /**
     * Returns the allowed resource types for a given role.
     * Empty array = no search allowed.
     */
    public function getAllowedResourceTypes(string $role): array
    {
        return SearchPermissionFilter::where('role', $role)
            ->where('is_active', true)
            ->pluck('resource_type')
            ->toArray();
    }

    /**
     * Check if a role can search a specific resource type.
     */
    public function canSearch(string $role, string $resourceType): bool
    {
        return SearchPermissionFilter::canSearch($role, $resourceType);
    }

    /**
     * Returns whether a search action requires an audit log entry.
     */
    public function requiresAuditLog(string $role, string $resourceType): bool
    {
        return SearchPermissionFilter::requiresAudit($role, $resourceType);
    }

    /**
     * Returns facility-scoped permission filters for a role.
     * Used to restrict searches to records within a user's authorized facilities.
     */
    public function getFacilityScopeFilter(string $role, string $facilityId): array
    {
        $filter = SearchPermissionFilter::where('role', $role)
            ->where('resource_type', 'patient')
            ->first();

        if (! $filter || ! $filter->facility_scoped) {
            return [];
        }

        return ['facility_id' => $facilityId];
    }
}
