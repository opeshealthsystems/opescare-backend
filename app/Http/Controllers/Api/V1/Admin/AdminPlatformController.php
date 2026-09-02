<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemHealthSnapshotResource;
use App\Modules\Admin\Services\FeatureFlagService;
use App\Modules\Admin\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminPlatformController — Platform-wide admin control endpoints.
 *
 * Provides REST API for:
 *  - Feature flag management (enable/disable per facility or globally)
 *  - System health monitoring (real-time and latest snapshot)
 *
 * All changes to feature flags are audited.
 * Routes protected by VerifyIntegrationClient middleware.
 *
 * Endpoints:
 *  GET   /v1/admin/feature-flags                — list all flags (optionally scoped to facility)
 *  GET   /v1/admin/feature-flags/{key}          — check if a specific flag is enabled
 *  POST  /v1/admin/feature-flags/{key}/enable   — enable a flag
 *  POST  /v1/admin/feature-flags/{key}/disable  — disable a flag (emergency kill-switch)
 *  GET   /v1/admin/system-health                — latest captured health snapshot
 *  POST  /v1/admin/system-health/capture        — capture a fresh health snapshot
 */
class AdminPlatformController extends Controller
{
    public function __construct(
        private readonly FeatureFlagService $flags,
        private readonly SystemHealthService $health
    ) {}

    // ── Feature Flags ─────────────────────────────────────────────────────

    /**
     * List all feature flags.
     * ?facility_id= to scope to a specific facility (overrides middleware-derived value).
     * Without ?facility_id, returns global flags.
     */
    public function listFlags(Request $request): JsonResponse
    {
        // Prefer explicit query param so super-admins can inspect any facility
        $facilityId = $request->query('facility_id')
            ?? $request->attributes->get('facility_id');

        $flags = $this->flags->getAllFlags($facilityId);

        return response()->json([
            'facility_id' => $facilityId,
            'data'        => $flags,
        ]);
    }

    /**
     * Check whether a specific flag is enabled.
     * Returns: { key, is_enabled, facility_id }
     */
    public function checkFlag(string $key, Request $request): JsonResponse
    {
        $facilityId = $request->query('facility_id')
            ?? $request->attributes->get('facility_id');

        $isEnabled = $this->flags->isEnabled($key, $facilityId);

        return response()->json([
            'key'         => $key,
            'is_enabled'  => $isEnabled,
            'facility_id' => $facilityId,
        ]);
    }

    /**
     * Enable a feature flag.
     * Body: { actor_id, facility_id? }
     * Omit facility_id to set a global flag.
     */
    public function enableFlag(string $key, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id'    => ['required', 'uuid'],
            'facility_id' => ['nullable', 'uuid'],
        ]);

        $facilityId = $validated['facility_id']
            ?? $request->attributes->get('facility_id');

        $flag = $this->flags->enable($key, $validated['actor_id'], $facilityId);

        return response()->json([
            'message' => __('api.feature_flag_enabled', ['key' => $key]),
            'data'    => $flag,
        ]);
    }

    /**
     * Disable a feature flag (emergency kill-switch).
     * Body: { actor_id, facility_id? }
     */
    public function disableFlag(string $key, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id'    => ['required', 'uuid'],
            'facility_id' => ['nullable', 'uuid'],
        ]);

        $facilityId = $validated['facility_id']
            ?? $request->attributes->get('facility_id');

        $flag = $this->flags->disable($key, $validated['actor_id'], $facilityId);

        return response()->json([
            'message' => __('api.feature_flag_disabled', ['key' => $key]),
            'data'    => $flag,
        ]);
    }

    // ── System Health ─────────────────────────────────────────────────────

    /**
     * Retrieve the latest health snapshot.
     * Returns 404 if no snapshot has been captured yet.
     */
    public function latestHealth(): JsonResponse
    {
        $snapshot = $this->health->getLatestSnapshot();

        if (!$snapshot) {
            return response()->json([
                'message' => __('api.no_health_snapshot'),
                'data'    => null,
            ], 404);
        }

        return response()->json(['data' => SystemHealthSnapshotResource::make($snapshot)]);
    }

    /**
     * Capture a fresh system health snapshot on demand.
     * Also triggered by the scheduler (if configured).
     */
    public function captureHealth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'captured_by' => ['nullable', 'string', 'max:100'],
        ]);

        $snapshot = $this->health->captureSnapshot(
            $validated['captured_by'] ?? 'api'
        );

        $httpStatus = match ($snapshot->status) {
            'critical' => 503,
            'degraded' => 206,
            default    => 201,
        };

        return response()->json([
            'message' => __('api.system_health_status', ['status' => $snapshot->status]),
            'data'    => SystemHealthSnapshotResource::make($snapshot),
        ], $httpStatus);
    }
}
