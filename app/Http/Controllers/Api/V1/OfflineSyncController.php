<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SyncConflictResource;
use App\Modules\Offline\Services\ConflictResolutionService;
use App\Modules\Offline\Services\OfflinePolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OfflineSyncController — Offline Cache Policy & Conflict Resolution API.
 *
 * Supports mobile/desktop clients that operate in offline-first mode.
 * Provides:
 *  - Offline caching policies per scope (what data can be cached, how many records)
 *  - Sync conflict resolution (clinical conflicts MUST use manual_merge)
 *
 * SAFETY RULE (encoded in every conflict response):
 * Clinical conflicts (encounters, prescriptions, lab results, vital signs)
 * MUST be resolved via manual_merge. Automatic resolution (server_wins /
 * client_wins) is NEVER allowed for clinical records.
 *
 * Routes protected by VerifyIntegrationClient middleware.
 *
 * Endpoints:
 *  GET   /v1/offline/policy/{scope}                — caching policy for a scope
 *  GET   /v1/offline/policy/facility/{facilityId}  — all allowed scopes for a facility
 *  GET   /v1/offline/conflicts/device/{deviceId}   — unresolved conflicts for a device
 *  GET   /v1/offline/conflicts/clinical             — clinical conflicts pending review
 *  POST  /v1/offline/conflicts/{id}/resolve        — resolve a conflict
 */
class OfflineSyncController extends Controller
{
    public function __construct(
        private readonly OfflinePolicyService      $policies,
        private readonly ConflictResolutionService $conflicts
    ) {}

    /**
     * Get the offline caching policy for a scope (e.g., 'patients', 'prescriptions').
     * Returns whether caching is allowed and max record count.
     */
    public function policy(string $scope, Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id')
            ?? $request->query('facility_id');

        $policy = $this->policies->getPolicyFor($scope, $facilityId);

        if (!$policy) {
            return response()->json([
                'scope'            => $scope,
                'caching_allowed'  => false,
                'max_records'      => 0,
                'message'          => __('api.no_caching_policy_scope', ['scope' => $scope]),
            ]);
        }

        return response()->json([
            'scope'           => $scope,
            'caching_allowed' => $this->policies->isCachingAllowed($scope, $facilityId),
            'max_records'     => $this->policies->getMaxRecords($scope, $facilityId),
            'policy'          => $policy,
        ]);
    }

    /**
     * Get all offline-allowed scopes for a facility.
     * Used by mobile apps on first sync to know what they can cache.
     */
    public function facilityScopes(string $facilityId, Request $request): JsonResponse
    {
        // Enforce: facility_id from middleware must match requested facilityId
        $middlewareFacilityId = $request->attributes->get('facility_id');
        if ($middlewareFacilityId && $middlewareFacilityId !== $facilityId) {
            return response()->json(['message' => __('api.forbidden_facility_mismatch')], 403);
        }

        $scopes = $this->policies->getAllowedScopesForFacility($facilityId);

        return response()->json([
            'facility_id'    => $facilityId,
            'allowed_scopes' => $scopes,
        ]);
    }

    /**
     * Get all unresolved sync conflicts for a device.
     */
    public function deviceConflicts(string $deviceId): JsonResponse
    {
        $conflicts = $this->conflicts->getUnresolvedForDevice($deviceId);

        return response()->json([
            'device_id'        => $deviceId,
            'count'            => $conflicts->count(),
            'safety_notice'    => 'Clinical conflicts must be reviewed by a clinician before resolution.',
            'conflicts'        => SyncConflictResource::collection($conflicts),
        ]);
    }

    /**
     * Get all clinical conflicts pending human review.
     * Used by clinical governance dashboards.
     */
    public function clinicalConflicts(): JsonResponse
    {
        $conflicts = $this->conflicts->getClinicalConflictsPendingReview();

        return response()->json([
            'count'          => $conflicts->count(),
            'safety_notice'  => 'Clinical conflicts (encounters, prescriptions, lab results, vital signs) require manual_merge strategy. Auto-resolution is not permitted.',
            'conflicts'      => SyncConflictResource::collection($conflicts),
        ]);
    }

    /**
     * Resolve a sync conflict.
     *
     * Body: { strategy: server_wins|client_wins|manual_merge, resolved_by, merged_payload? }
     * Clinical resource conflicts require strategy=manual_merge.
     */
    public function resolve(string $conflictId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'strategy'       => ['required', 'string', 'in:server_wins,client_wins,manual_merge'],
            'resolved_by'    => ['required', 'uuid'],
            'merged_payload' => ['nullable', 'array'],
        ]);

        try {
            $resolved = $this->conflicts->resolve(
                $conflictId,
                $validated['strategy'],
                $validated['resolved_by'],
                $validated['merged_payload'] ?? null
            );
        } catch (\DomainException $e) {
            return response()->json([
                'message'       => $e->getMessage(),
                'safety_notice' => 'Clinical conflicts must use manual_merge strategy with a merged_payload.',
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => __('api.conflict_not_found')], 404);
        }

        return response()->json([
            'message' => __('api.conflict_resolved'),
            'data'    => SyncConflictResource::make($resolved),
        ]);
    }
}
