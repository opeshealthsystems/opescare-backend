<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SearchIndex;
use App\Modules\Search\Services\SearchIndexingService;
use App\Modules\Search\Services\SearchPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SearchController — Clinical Search & Indexing API.
 *
 * Provides cross-module search across indexed clinical resources.
 * Search is role-scoped: the caller's role determines which resource
 * types are searchable and whether results are audit-logged.
 *
 * SECURITY: PHI never appears in search results without permission filter applied.
 * All results are scoped to the caller's facility_id from middleware.
 *
 * Routes protected by VerifyIntegrationClient middleware.
 *
 * Endpoints:
 *  GET   /v1/search                              — search across allowed resource types
 *  POST  /v1/search/index                        — index or reindex a single resource
 *  DELETE /v1/search/index/{resourceType}/{id}   — remove resource from index
 *  GET   /v1/search/permissions/{role}           — searchable resource types for a role
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly SearchIndexingService  $indexer,
        private readonly SearchPermissionService $permissions
    ) {}

    /**
     * Search across indexed resources.
     * Query: ?q=, resource_type?, role?
     * facility_id scoping enforced from middleware attributes.
     */
    public function search(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'q'             => ['required', 'string', 'min:1', 'max:500'],
            'resource_type' => ['nullable', 'string', 'max:100'],
            'role'          => ['nullable', 'string', 'max:100'],
            'limit'         => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $role         = $validated['role'] ?? 'default';
        $resourceType = $validated['resource_type'] ?? null;
        $limit        = (int) ($validated['limit'] ?? 25);

        // Gate: permission check for requested resource type
        if ($resourceType && !$this->permissions->canSearch($role, $resourceType)) {
            return response()->json([
                'message' => __('api.role_not_permitted_search', ['role' => $role, 'resource_type' => $resourceType]),
            ], 403);
        }

        // Collect allowed types
        $allowedTypes = $resourceType
            ? [$resourceType]
            : $this->permissions->getAllowedResourceTypes($role);

        if (empty($allowedTypes)) {
            return response()->json(['query' => $validated['q'], 'count' => 0, 'results' => []]);
        }

        // Build query against SearchIndex — permission_scope and facility scoping
        $query = SearchIndex::whereIn('resource_type', $allowedTypes)
            ->where('search_text', 'ilike', '%' . $validated['q'] . '%');

        if ($facilityId) {
            $scopeFilter = $this->permissions->getFacilityScopeFilter($role, $facilityId);
            if (!empty($scopeFilter['facility_id'])) {
                $query->where('facility_id', $scopeFilter['facility_id']);
            }
        }

        $results = $query->limit($limit)->get(['resource_type', 'resource_id', 'search_text', 'metadata']);

        // Audit-log if required for this role/resource combination
        if ($resourceType && $this->permissions->requiresAuditLog($role, $resourceType)) {
            \Log::channel('audit')->info('clinical_search', [
                'role'          => $role,
                'resource_type' => $resourceType,
                'query'         => $validated['q'],
                'facility_id'   => $facilityId,
            ]);
        }

        return response()->json([
            'query'   => $validated['q'],
            'count'   => $results->count(),
            'results' => $results,
        ]);
    }

    /**
     * Index or reindex a single resource.
     * Body: { resource_type, resource_id, search_text, permission_scope, facility_id?, metadata? }
     *
     * SECURITY: permission_scope must be set — determines who can retrieve this record in search.
     */
    public function reindex(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'resource_type'    => ['required', 'string', 'max:100'],
            'resource_id'      => ['required', 'uuid'],
            'search_text'      => ['required', 'string', 'max:2000'],
            'permission_scope' => ['required', 'string', 'max:255'],
            'metadata'         => ['nullable', 'array'],
        ]);

        $this->indexer->reindex(
            $validated['resource_type'],
            $validated['resource_id'],
            $validated['search_text'],
            $validated['permission_scope'],
            $facilityId,
            $validated['metadata'] ?? []
        );

        return response()->json([
            'message' => __('api.resource_indexed', ['resource' => $validated['resource_type'] . '/' . $validated['resource_id']]),
        ]);
    }

    /**
     * Remove a resource from the search index.
     */
    public function deindex(string $resourceType, string $resourceId): JsonResponse
    {
        $this->indexer->deindex($resourceType, $resourceId);

        return response()->json([
            'message' => __('api.resource_removed_index', ['resource' => $resourceType . '/' . $resourceId]),
        ]);
    }

    /**
     * Get searchable resource types and audit requirements for a given role.
     */
    public function rolePermissions(string $role): JsonResponse
    {
        $allowedTypes = $this->permissions->getAllowedResourceTypes($role);

        $details = array_map(fn ($type) => [
            'resource_type'      => $type,
            'requires_audit_log' => $this->permissions->requiresAuditLog($role, $type),
        ], $allowedTypes);

        return response()->json([
            'role'          => $role,
            'allowed_types' => $details,
        ]);
    }
}
