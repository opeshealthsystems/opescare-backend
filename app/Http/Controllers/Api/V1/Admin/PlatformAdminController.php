<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceWindowResource;
use App\Http\Resources\ModuleToggleResource;
use App\Models\MaintenanceWindow;
use App\Modules\Admin\Services\PlatformAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PlatformAdminController — Master Control Center REST API.
 *
 * Exposes platform settings, module toggles, maintenance windows,
 * system health, and admin action log via VerifyIntegrationClient-protected routes.
 *
 * All writes are audited via AdminActionLog (handled inside PlatformAdminService).
 *
 * Endpoints:
 *  GET   /v1/admin/platform/settings                      — all settings grouped
 *  PUT   /v1/admin/platform/settings/{key}                — update a setting
 *  POST  /v1/admin/platform/settings/seed                 — seed default settings
 *
 *  GET   /v1/admin/platform/modules                       — all module toggles
 *  POST  /v1/admin/platform/modules/{key}/toggle          — enable/disable module
 *  POST  /v1/admin/platform/modules/seed                  — seed default modules
 *
 *  GET   /v1/admin/platform/maintenance                   — list maintenance windows
 *  POST  /v1/admin/platform/maintenance                   — create maintenance window
 *  PATCH /v1/admin/platform/maintenance/{id}/toggle       — activate / deactivate
 *
 *  GET   /v1/admin/platform/health                        — live system health check
 *  GET   /v1/admin/platform/action-log                    — recent admin actions
 */
class PlatformAdminController extends Controller
{
    public function __construct(private readonly PlatformAdminService $service) {}

    // ── Platform Settings ─────────────────────────────────────────────────

    public function allSettings(): JsonResponse
    {
        return response()->json(['data' => $this->service->allSettings()]);
    }

    /**
     * Update a platform setting.
     * Body: { value, actor_id }
     */
    public function updateSetting(string $key, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value'    => ['required'],
            'actor_id' => ['required', 'uuid'],
        ]);

        $setting = $this->service->updateSetting(
            $key,
            $validated['value'],
            $validated['actor_id'],
            $request->ip()
        );

        return response()->json(['message' => __('api.setting_updated', ['key' => $key]), 'data' => $setting]);
    }

    /**
     * Seed default platform settings (idempotent — uses firstOrCreate).
     * Body: { actor_id }
     */
    public function seedSettings(Request $request): JsonResponse
    {
        $validated = $request->validate(['actor_id' => ['required', 'uuid']]);
        $this->service->seedDefaultSettings($validated['actor_id']);
        return response()->json(['message' => __('api.default_settings_seeded')]);
    }

    // ── Module Toggles ────────────────────────────────────────────────────

    public function allModules(): JsonResponse
    {
        return response()->json(['data' => $this->service->allModuleToggles()]);
    }

    /**
     * Toggle a module on or off.
     * Body: { enabled: bool, actor_id, reason? (required when disabling) }
     */
    public function toggleModule(string $key, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled'  => ['required', 'boolean'],
            'actor_id' => ['required', 'uuid'],
            'reason'   => ['nullable', 'string', 'max:500'],
        ]);

        if (!$validated['enabled'] && empty($validated['reason'])) {
            return response()->json(['message' => __('api.reason_required_disable_module')], 422);
        }

        try {
            $toggle = $this->service->toggleModule(
                $key,
                $validated['enabled'],
                $validated['actor_id'],
                $validated['reason'] ?? null,
                $request->ip()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => __('api.module_not_found', ['key' => $key])], 404);
        }

        $state = $toggle->enabled ? 'enabled' : 'disabled';
        return response()->json(['message' => __('api.module_state', ['key' => $key, 'state' => $state]), 'data' => ModuleToggleResource::make($toggle)]);
    }

    /**
     * Seed default module toggles (idempotent).
     * Body: { actor_id }
     */
    public function seedModules(Request $request): JsonResponse
    {
        $validated = $request->validate(['actor_id' => ['required', 'uuid']]);
        $this->service->seedDefaultModules($validated['actor_id']);
        return response()->json(['message' => __('api.default_modules_seeded')]);
    }

    // ── Maintenance Windows ───────────────────────────────────────────────

    public function listMaintenance(): JsonResponse
    {
        return response()->json(['data' => $this->service->listMaintenanceWindows()]);
    }

    /**
     * Create a maintenance window.
     * Body: { title, message?, starts_at, ends_at?, is_active?, allow_emergency_access?, actor_id }
     */
    public function createMaintenance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'                  => ['required', 'string', 'max:255'],
            'message'                => ['nullable', 'string', 'max:2000'],
            'starts_at'              => ['required', 'date'],
            'ends_at'                => ['nullable', 'date', 'after:starts_at'],
            'is_active'              => ['nullable', 'boolean'],
            'allow_emergency_access' => ['nullable', 'boolean'],
            'actor_id'               => ['required', 'uuid'],
        ]);

        $window = $this->service->createMaintenanceWindow(
            $validated,
            $validated['actor_id'],
            $request->ip()
        );

        return response()->json(['message' => __('api.maintenance_window_created'), 'data' => $window], 201);
    }

    /**
     * Activate or deactivate a maintenance window.
     * Body: { active: bool, actor_id }
     * Note: activating flushes the maintenance cache immediately.
     */
    public function toggleMaintenance(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'active'   => ['required', 'boolean'],
            'actor_id' => ['required', 'uuid'],
        ]);

        try {
            $window = $this->service->toggleMaintenance(
                $id,
                $validated['active'],
                $validated['actor_id'],
                $request->ip()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => __('api.maintenance_window_not_found')], 404);
        }

        $state = $window->is_active ? 'activated' : 'deactivated';
        return response()->json(['message' => __('api.maintenance_window_state', ['state' => $state]), 'data' => MaintenanceWindowResource::make($window)]);
    }

    // ── System Health & Admin Log ─────────────────────────────────────────

    /**
     * Live system health check — DB, storage, queue, failed jobs, maintenance state.
     */
    public function health(): JsonResponse
    {
        $health = $this->service->systemHealth();

        // Return 503 if database is down — callers need to know
        $hasError = collect($health)->contains(fn ($v) => is_array($v) && ($v['status'] ?? '') === 'error');

        return response()->json(['data' => $health], $hasError ? 503 : 200);
    }

    /**
     * Recent admin action log (last 50 by default).
     * ?limit=N (max 200)
     */
    public function actionLog(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 50), 200);
        return response()->json(['data' => $this->service->recentActions($limit)]);
    }
}
