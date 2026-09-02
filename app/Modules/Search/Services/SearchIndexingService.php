<?php

namespace App\Modules\Search\Services;

use App\Models\SearchIndex;
use App\Models\SearchPermissionFilter;

/**
 * SearchIndexingService — Builds and maintains the search index.
 *
 * SECURITY: PHI must NEVER appear in search_text without permission filter applied.
 * All indexed records must carry a permission_scope so the search service
 * can enforce access control before returning results.
 *
 * Indexed resource types: patient, facility, provider, drug, lab_test, document
 */
class SearchIndexingService
{
    /**
     * Reindex a single resource into the search index.
     * Enforces permission_scope — every entry must declare who can see it.
     */
    public function reindex(
        string $resourceType,
        string $resourceId,
        string $searchText,
        string $permissionScope,
        string $facilityId = null,
        array $metadata = []
    ): SearchIndex {
        return SearchIndex::reindex(
            $resourceType,
            $resourceId,
            $searchText,
            $permissionScope,
            $facilityId,
            $metadata
        );
    }

    /**
     * Remove a resource from the search index (e.g. on deletion or revocation).
     */
    public function deindex(string $resourceType, string $resourceId): void
    {
        SearchIndex::where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->delete();
    }

    /**
     * Bulk reindex all records for a given resource type.
     * Used during initial setup or after schema changes.
     */
    public function bulkReindexType(string $resourceType, callable $recordProvider): int
    {
        $count = 0;
        foreach ($recordProvider() as $entry) {
            $this->reindex(
                $resourceType,
                $entry['resource_id'],
                $entry['search_text'],
                $entry['permission_scope'],
                $entry['facility_id'] ?? null,
                $entry['metadata'] ?? []
            );
            $count++;
        }
        return $count;
    }
}
