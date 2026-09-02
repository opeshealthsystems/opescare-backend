<?php

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RegionHealthService
{
    public function isDatabaseHealthy(): bool
    {
        $ttl = config('regions.health_check_ttl', 30);

        return Cache::remember('health.database', $ttl, function () {
            try {
                DB::select('SELECT 1');
                return true;
            } catch (Throwable $e) {
                Log::critical('Database health check failed', [
                    'error'  => $e->getMessage(),
                    'region' => config('regions.current_region'),
                ]);
                return false;
            }
        });
    }

    public function isRedisHealthy(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (Throwable $e) {
            Log::error('Redis health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getHealthStatus(): array
    {
        return [
            'database'  => $this->isDatabaseHealthy(),
            'redis'     => $this->isRedisHealthy(),
            'region'    => config('regions.current_region'),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function alertFailover(string $component): void
    {
        $webhook = config('regions.failover_webhook');

        if (empty($webhook)) {
            return;
        }

        try {
            Http::timeout(5)->post($webhook, [
                'component' => $component,
                'region'    => config('regions.current_region'),
                'timestamp' => now()->toIso8601String(),
                'severity'  => 'CRITICAL',
            ]);
        } catch (Throwable $e) {
            Log::error('Failover webhook delivery failed', [
                'webhook'   => $webhook,
                'component' => $component,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
