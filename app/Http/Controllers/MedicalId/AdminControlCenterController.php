<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\PlatformAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AdminControlCenterController extends Controller
{
    private function demoActorId(): string
    {
        return session('auth_email') ?: 'super-admin';
    }

    // ── Dashboard ─────────────────────────────────────────────────

    public function index(PlatformAdminService $svc): View
    {
        // Ensure defaults seeded
        $svc->seedDefaultSettings($this->demoActorId());
        $svc->seedDefaultFlags($this->demoActorId());
        $svc->seedDefaultModules($this->demoActorId());

        $health  = $svc->systemHealth();
        $actions = $svc->recentActions(10);

        return view('portals.admin.control_center.index', compact('health', 'actions'));
    }

    // ── Platform Settings ─────────────────────────────────────────

    public function settings(PlatformAdminService $svc): View
    {
        $groups = $svc->allSettings();
        return view('portals.admin.control_center.settings', compact('groups'));
    }

    public function settingsUpdate(Request $request, PlatformAdminService $svc): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'key'   => 'required|string',
            'value' => 'required|string|max:500',
        ]);

        try {
            $svc->updateSetting($request->key, $request->value, $this->demoActorId(), $request->ip());
            return redirect()->route('portals.admin.cc.settings')->with('success', __('flash.setting_updated'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Feature Flags ─────────────────────────────────────────────

    public function featureFlags(PlatformAdminService $svc): View
    {
        $flags = $svc->allFeatureFlags();
        return view('portals.admin.control_center.feature_flags', compact('flags'));
    }

    public function featureFlagToggle(Request $request, string $key, PlatformAdminService $svc): \Illuminate\Http\RedirectResponse
    {
        try {
            $svc->toggleFeatureFlag($key, (bool) $request->input('enabled', false), $this->demoActorId(), $request->ip());
            return redirect()->route('portals.admin.cc.feature_flags')->with('success', __('flash.feature_flag_updated'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Module Toggles ────────────────────────────────────────────

    public function modules(PlatformAdminService $svc): View
    {
        $modules = $svc->allModuleToggles();
        return view('portals.admin.control_center.modules', compact('modules'));
    }

    public function moduleToggle(Request $request, string $key, PlatformAdminService $svc): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:300']);

        try {
            $svc->toggleModule($key, (bool) $request->input('enabled', false), $this->demoActorId(), $request->reason, $request->ip());
            return redirect()->route('portals.admin.cc.modules')->with('success', __('flash.module_toggle_updated'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── Maintenance ───────────────────────────────────────────────

    public function maintenance(PlatformAdminService $svc): View
    {
        $windows = $svc->listMaintenanceWindows();
        return view('portals.admin.control_center.maintenance', compact('windows'));
    }

    public function maintenanceStore(Request $request, PlatformAdminService $svc): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'title'      => 'required|string|max:200',
            'message'    => 'nullable|string|max:1000',
            'starts_at'  => 'required|date',
            'ends_at'    => 'nullable|date|after:starts_at',
            'is_active'  => 'nullable|boolean',
        ]);

        try {
            $svc->createMaintenanceWindow($request->all(), $this->demoActorId(), $request->ip());
            return redirect()->route('portals.admin.cc.maintenance')->with('success', __('flash.maintenance_window_created'));
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function maintenanceToggle(Request $request, string $id, PlatformAdminService $svc): \Illuminate\Http\RedirectResponse
    {
        try {
            $svc->toggleMaintenance($id, (bool) $request->input('active', false), $this->demoActorId(), $request->ip());
            return redirect()->route('portals.admin.cc.maintenance')->with('success', __('flash.maintenance_window_updated'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ── System Health ─────────────────────────────────────────────

    public function systemHealth(PlatformAdminService $svc): View
    {
        $health = $svc->systemHealth();
        return view('portals.admin.control_center.system_health', compact('health'));
    }

    // ── Admin Log ─────────────────────────────────────────────────

    public function auditLog(PlatformAdminService $svc): View
    {
        $logs = $svc->recentActions(100);
        return view('portals.admin.control_center.audit_log', compact('logs'));
    }
}
