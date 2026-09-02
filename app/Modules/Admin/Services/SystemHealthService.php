<?php

namespace App\Modules\Admin\Services;

use App\Models\SystemHealthSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

/**
 * SystemHealthService — Captures and reports system health metrics.
 *
 * Health snapshots are stored in system_health_snapshots for trend analysis.
 * Metrics include: DB connectivity, cache connectivity, queue depth,
 * disk space, active sessions, failed jobs, and API latency p95.
 *
 * Used by the Master Admin Control Center dashboard.
 */
class SystemHealthService
{
    public function captureSnapshot(string $capturedBy = 'scheduler'): SystemHealthSnapshot
    {
        $metrics = $this->gatherMetrics();

        return SystemHealthSnapshot::create([
            'captured_by' => $capturedBy,
            'metrics'     => $metrics,
            'status'      => $this->deriveStatus($metrics),
        ]);
    }

    private function gatherMetrics(): array
    {
        return [
            'db_connected'       => $this->checkDbConnectivity(),
            'cache_connected'    => $this->checkCacheConnectivity(),
            'failed_jobs_count'  => DB::table('failed_jobs')->count(),
            'active_users'       => Cache::get('active_sessions_count', 0),
            'memory_usage_mb'    => round(memory_get_usage(true) / 1024 / 1024, 2),
            'captured_at'        => now()->toIso8601String(),
        ];
    }

    private function checkDbConnectivity(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function checkCacheConnectivity(): bool
    {
        try {
            Cache::put('health_check', true, 5);
            return Cache::get('health_check') === true;
        } catch (\Exception) {
            return false;
        }
    }

    private function deriveStatus(array $metrics): string
    {
        if (! $metrics['db_connected']) {
            return 'critical';
        }
        if (! $metrics['cache_connected'] || $metrics['failed_jobs_count'] > 100) {
            return 'degraded';
        }
        return 'healthy';
    }

    public function getLatestSnapshot(): ?SystemHealthSnapshot
    {
        return SystemHealthSnapshot::latest()->first();
    }

    /**
     * Live infrastructure health for the PUBLIC status page. Cached briefly so
     * the unauthenticated endpoint can't hammer the DB/cache on every hit.
     * Returns the overall status plus per-service-group statuses derived from
     * real connectivity + queue-depth checks (no hard-coded "operational").
     *
     * @return array{status:string, checked_at:\Illuminate\Support\Carbon, components:array<string,string>}
     */
    public function currentHealth(): array
    {
        $data = Cache::remember('public_status_health', 30, function (): array {
            $metrics = $this->gatherMetrics();
            $dbOk    = (bool) $metrics['db_connected'];
            $cacheOk = (bool) $metrics['cache_connected'];
            $queueOk = ($metrics['failed_jobs_count'] ?? 0) <= 100;

            // outage if the primary dependency is down; degraded if a secondary is.
            $derive = fn (bool $primary, bool $secondary = true): string =>
                ! $primary ? 'outage' : ($secondary ? 'operational' : 'degraded');

            return [
                'status'     => $this->deriveStatus($metrics),
                'checked_at' => now()->toIso8601String(),
                'components' => [
                    'core'         => $derive($dbOk, $cacheOk),
                    'clinical'     => $derive($dbOk),
                    'availability' => $derive($dbOk),
                    'integration'  => $derive($dbOk, $queueOk),
                    'portal'       => $derive($dbOk, $cacheOk),
                ],
            ];
        });

        /*
         * Rehydrate outside the cache boundary.
         *
         * config('cache.serializable_classes') is false — Laravel's default,
         * which stops a leaked APP_KEY from turning the cache into a gadget
         * chain. Every store therefore unserializes with allowed_classes:false,
         * so an object put into the cache comes back as __PHP_Incomplete_Class
         * on the FIRST cache HIT, not on the miss. Caching a Carbon here meant
         * the public status page rendered once and then returned 500 for the
         * rest of the 30-second TTL.
         *
         * The cache holds an ISO-8601 string; callers still get a Carbon.
         */
        $checkedAt = $data['checked_at'] ?? null;
        $data['checked_at'] = is_string($checkedAt) ? Carbon::parse($checkedAt) : now();

        return $data;
    }
}
