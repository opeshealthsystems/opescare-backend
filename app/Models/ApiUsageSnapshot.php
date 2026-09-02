<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;

class ApiUsageSnapshot extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'endpoint_group',
        'period_date',
        'environment',
        'request_count',
        'error_count',
        'rate_limited_count',
        'p95_latency_ms',
        'last_request_at',
    ];

    protected $casts = [
        'period_date'     => 'date',
        'last_request_at' => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForClient($query, string $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForEnvironment($query, string $env)
    {
        return $query->where('environment', $env);
    }

    public function scopeInPeriod($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('period_date', [$from->toDateString(), $to->toDateString()]);
    }

    // ── Static Helpers ────────────────────────────────────────────────────────

    /**
     * Increment today's snapshot for a given client + endpoint group.
     * Idempotent — creates the row if it doesn't exist.
     */
    public static function incrementFor(
        string $clientId,
        string $endpointGroup,
        string $environment = 'production',
        bool   $isError = false,
        bool   $isRateLimited = false,
        ?int   $latencyMs = null
    ): void {
        $today = now()->toDateString();

        $snapshot = static::firstOrCreate(
            [
                'client_id'      => $clientId,
                'endpoint_group' => $endpointGroup,
                'period_date'    => $today,
                'environment'    => $environment,
            ],
            ['request_count' => 0, 'error_count' => 0, 'rate_limited_count' => 0]
        );

        $increments = ['request_count' => 1];
        if ($isError) {
            $increments['error_count'] = 1;
        }
        if ($isRateLimited) {
            $increments['rate_limited_count'] = 1;
        }

        $snapshot->increment('request_count');
        if ($isError) {
            $snapshot->increment('error_count');
        }
        if ($isRateLimited) {
            $snapshot->increment('rate_limited_count');
        }

        $updates = ['last_request_at' => now()];
        if ($latencyMs !== null) {
            // Store simple running p95 approximation (replace if higher, else keep max observed)
            $updates['p95_latency_ms'] = max($snapshot->p95_latency_ms ?? 0, $latencyMs);
        }
        $snapshot->update($updates);
    }

    /**
     * Summarise the last N days for a client, grouped by endpoint_group.
     */
    public static function summaryForClient(string $clientId, int $days = 30): array
    {
        $from = now()->subDays($days)->startOfDay();

        return static::forClient($clientId)
            ->inPeriod($from, now())
            ->get()
            ->groupBy('endpoint_group')
            ->map(fn ($rows) => [
                'total_requests'      => $rows->sum('request_count'),
                'total_errors'        => $rows->sum('error_count'),
                'total_rate_limited'  => $rows->sum('rate_limited_count'),
                'last_request_at'     => $rows->max('last_request_at'),
            ])
            ->toArray();
    }

    /**
     * Daily breakdown for a client for the last N days.
     */
    public static function dailyTrendForClient(string $clientId, int $days = 30): array
    {
        $from = now()->subDays($days)->startOfDay();

        return static::forClient($clientId)
            ->inPeriod($from, now())
            ->orderBy('period_date')
            ->get(['period_date', 'endpoint_group', 'request_count', 'error_count'])
            ->toArray();
    }
}
