<?php

namespace App\Modules\Admin\Services;

use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\AdminActionLog;
use App\Models\FeatureFlag;
use App\Models\MaintenanceWindow;
use App\Models\ModuleToggle;
use App\Models\PlatformSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class PlatformAdminService
{
    // ── Platform Settings ─────────────────────────────────────────

    public function allSettings(): array
    {
        return PlatformSetting::orderBy('group')->orderBy('key')->get()->groupBy('group')->toArray();
    }

    public function updateSetting(string $key, mixed $value, string $actorId, ?string $ip = null): PlatformSetting
    {
        $setting = PlatformSetting::where('key', $key)->first();
        $before  = $setting?->toArray();

        $setting = PlatformSetting::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'updated_by' => $actorId]
        );

        $this->log($actorId, 'setting_updated', 'platform_setting', $setting->id, $before, $setting->fresh()->toArray(), $ip);

        return $setting->fresh();
    }

    public function seedDefaultSettings(string $actorId): void
    {
        $defaults = [
            ['key' => 'platform.name',              'group' => 'general',  'value' => 'OpesCare',    'value_type' => 'string',  'description' => 'Platform display name',            'is_public' => true],
            ['key' => 'platform.default_language',  'group' => 'general',  'value' => 'en',          'value_type' => 'string',  'description' => 'Default platform language',         'is_public' => true],
            ['key' => 'platform.maintenance_mode',  'group' => 'general',  'value' => 'false',       'value_type' => 'boolean', 'description' => 'Put platform in maintenance mode',  'is_public' => true],
            ['key' => 'platform.max_upload_mb',     'group' => 'security', 'value' => '25',          'value_type' => 'integer', 'description' => 'Max file upload size in MB',        'is_public' => false],
            ['key' => 'platform.session_timeout_m', 'group' => 'security', 'value' => '60',          'value_type' => 'integer', 'description' => 'Session timeout in minutes',        'is_public' => false],
            ['key' => 'platform.audit_retention_d', 'group' => 'security', 'value' => '365',         'value_type' => 'integer', 'description' => 'Audit log retention in days',       'is_public' => false],
            ['key' => 'billing.currency',           'group' => 'billing',  'value' => 'XOF',         'value_type' => 'string',  'description' => 'Default billing currency',          'is_public' => true],
            ['key' => 'billing.tax_rate_pct',       'group' => 'billing',  'value' => '0',           'value_type' => 'integer', 'description' => 'Default VAT/tax rate %',            'is_public' => false],
        ];

        foreach ($defaults as $def) {
            PlatformSetting::firstOrCreate(['key' => $def['key']], array_merge($def, ['updated_by' => $actorId]));
        }
    }

    // ── Feature Flags ─────────────────────────────────────────────

    public function allFeatureFlags(): \Illuminate\Database\Eloquent\Collection
    {
        return FeatureFlag::orderBy('key')->get();
    }

    public function toggleFeatureFlag(string $key, bool $enabled, string $actorId, ?string $ip = null): FeatureFlag
    {
        $flag   = FeatureFlag::where('key', $key)->firstOrFail();
        $before = $flag->toArray();
        $flag->forceFill(['enabled' => $enabled, 'updated_by' => $actorId])->save();
        $this->log($actorId, 'feature_flag_toggled', 'feature_flag', $flag->id, $before, $flag->fresh()->toArray(), $ip);
        return $flag->fresh();
    }

    public function seedDefaultFlags(string $actorId): void
    {
        $flags = [
            ['key' => 'feature.telemedicine',        'label' => 'Telemedicine',             'enabled' => false],
            ['key' => 'feature.offline_sync',        'label' => 'Offline Mode & Sync',      'enabled' => false],
            ['key' => 'feature.cdss',                'label' => 'Clinical Decision Support','enabled' => false],
            ['key' => 'feature.ward_management',     'label' => 'Ward & Bed Management',    'enabled' => false],
            ['key' => 'feature.saas_billing',        'label' => 'SaaS Subscription Billing','enabled' => false],
            ['key' => 'feature.data_import',         'label' => 'Data Import Wizard',       'enabled' => true],
            ['key' => 'feature.analytics',           'label' => 'Analytics Dashboard',      'enabled' => true],
            ['key' => 'feature.global_search',       'label' => 'Global Search',            'enabled' => true],
        ];

        foreach ($flags as $f) {
            FeatureFlag::firstOrCreate(['key' => $f['key']], array_merge($f, ['scope' => 'global', 'updated_by' => $actorId]));
        }
    }

    // ── Module Toggles ────────────────────────────────────────────

    public function allModuleToggles(): \Illuminate\Database\Eloquent\Collection
    {
        return ModuleToggle::orderBy('module_key')->get();
    }

    public function toggleModule(string $key, bool $enabled, string $actorId, ?string $reason = null, ?string $ip = null): ModuleToggle
    {
        $toggle = ModuleToggle::where('module_key', $key)->where('scope', 'global')->firstOrFail();
        $before = $toggle->toArray();
        $toggle->forceFill([
            'enabled'        => $enabled,
            'disable_reason' => $enabled ? null : $reason,
            'updated_by'     => $actorId,
        ])->save();
        $this->log($actorId, 'module_toggled', 'module_toggle', $toggle->id, $before, $toggle->fresh()->toArray(), $ip);
        return $toggle->fresh();
    }

    public function seedDefaultModules(string $actorId): void
    {
        $modules = [
            ['module_key' => 'appointments',   'module_label' => 'Appointments & Booking'],
            ['module_key' => 'queue',          'module_label' => 'Queue & Patient Flow'],
            ['module_key' => 'billing',        'module_label' => 'Billing & Payments'],
            ['module_key' => 'insurance',      'module_label' => 'Insurance Claims'],
            ['module_key' => 'visits',         'module_label' => 'Patient Visits'],
            ['module_key' => 'support',        'module_label' => 'Support & Helpdesk'],
            ['module_key' => 'data_import',    'module_label' => 'Data Import'],
            ['module_key' => 'hr_staff',       'module_label' => 'HR & Staff Management'],
            ['module_key' => 'inventory',      'module_label' => 'Inventory & Supply Chain'],
            ['module_key' => 'analytics',      'module_label' => 'Analytics & Reporting'],
            ['module_key' => 'triage',         'module_label' => 'Triage & Emergency'],
            ['module_key' => 'search',         'module_label' => 'Global Search'],
            ['module_key' => 'attachments',    'module_label' => 'File Storage & Attachments'],
        ];

        foreach ($modules as $m) {
            ModuleToggle::firstOrCreate(
                ['module_key' => $m['module_key'], 'scope' => 'global'],
                array_merge($m, ['enabled' => true, 'scope' => 'global', 'updated_by' => $actorId])
            );
        }
    }

    // ── Maintenance ───────────────────────────────────────────────

    public function listMaintenanceWindows(): \Illuminate\Database\Eloquent\Collection
    {
        return MaintenanceWindow::orderByDesc('starts_at')->limit(20)->get();
    }

    public function createMaintenanceWindow(array $data, string $actorId, ?string $ip = null): MaintenanceWindow
    {
        $window = MaintenanceWindow::create([
            'title'                  => $data['title'],
            'message'                => $data['message'] ?? null,
            'starts_at'              => $data['starts_at'],
            'ends_at'                => $data['ends_at'] ?? null,
            'is_active'              => (bool) ($data['is_active'] ?? false),
            'allow_emergency_access' => (bool) ($data['allow_emergency_access'] ?? true),
            'created_by'             => $actorId,
        ]);

        // Flush cache so the new window is picked up immediately if active
        CheckMaintenanceMode::flushCache();

        $this->log($actorId, 'maintenance_created', 'maintenance_window', $window->id, null, $window->toArray(), $ip);
        return $window;
    }

    public function toggleMaintenance(string $id, bool $active, string $actorId, ?string $ip = null): MaintenanceWindow
    {
        $window = MaintenanceWindow::findOrFail($id);
        $before = $window->toArray();
        $window->forceFill(['is_active' => $active])->save();

        // Flush cache immediately — enforcement must reflect the toggle within milliseconds
        CheckMaintenanceMode::flushCache();

        $this->log($actorId, $active ? 'maintenance_activated' : 'maintenance_deactivated', 'maintenance_window', $id, $before, $window->fresh()->toArray(), $ip);
        return $window->fresh();
    }

    // ── System Health ─────────────────────────────────────────────

    public function systemHealth(): array
    {
        $dbOk       = $this->checkDb();
        $storageOk  = $this->checkStorage();
        $queueOk    = $this->checkQueue();
        $failedJobs = $this->failedJobCount();
        $maintenanceActive = MaintenanceWindow::where('is_active', true)->exists();

        return [
            'database'    => ['status' => $dbOk  ? 'ok' : 'error',   'label' => 'Database', 'message' => $dbOk ? 'Connection OK' : 'Cannot connect to database'],
            'storage'     => ['status' => $storageOk ? 'ok' : 'error','label' => 'Storage', 'message' => $storageOk ? 'Disk write OK' : 'Cannot write to disk'],
            'queue'       => ['status' => $queueOk ? 'ok' : 'warning','label' => 'Queue', 'message' => $queueOk ? 'Queue manager available' : 'Queue manager unavailable'],
            'failed_jobs' => ['count' => $failedJobs, 'status' => $failedJobs > 0 ? 'warning' : 'ok', 'label' => 'Failed Jobs', 'message' => $failedJobs > 0 ? "{$failedJobs} job(s) in failed_jobs table" : 'No failed jobs'],
            'maintenance' => ['active' => $maintenanceActive, 'status' => $maintenanceActive ? 'warning' : 'ok', 'label' => 'Maintenance Mode', 'message' => $maintenanceActive ? 'Platform is in maintenance mode' : 'Platform operational'],
        ];
    }

    private function checkDb(): bool
    {
        try { DB::connection()->getPdo(); return true; } catch (\Exception) { return false; }
    }

    private function checkStorage(): bool
    {
        try { Storage::disk('local')->put('health_check.tmp', 'ok'); Storage::disk('local')->delete('health_check.tmp'); return true; } catch (\Exception) { return false; }
    }

    private function checkQueue(): bool
    {
        try { return class_exists('\Illuminate\Queue\QueueManager'); } catch (\Exception) { return false; }
    }

    private function failedJobCount(): int
    {
        try { return DB::table('failed_jobs')->count(); } catch (\Exception) { return 0; }
    }

    // ── Admin Log ─────────────────────────────────────────────────

    public function recentActions(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AdminActionLog::orderByDesc('occurred_at')->limit($limit)->get();
    }

    private function log(string $actorId, string $action, string $resourceType, string $resourceId, ?array $before, ?array $after, ?string $ip): void
    {
        AdminActionLog::create([
            'actor_id'      => $actorId,
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'before'        => $before,
            'after'         => $after,
            'ip_address'    => $ip,
            'occurred_at'   => now(),
        ]);
    }
}
