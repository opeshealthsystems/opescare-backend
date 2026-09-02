<?php
namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;

class ApiUsageAnalyticsService
{
    public function getSummaryForPeriod(string $fromDate, string $toDate): array
    {
        return DB::table('api_usage_logs')
            ->selectRaw('
                integration_client_id,
                COUNT(*) as request_count,
                AVG(response_time_ms) as avg_response_ms,
                SUM(CASE WHEN response_status >= 500 THEN 1 ELSE 0 END) as error_5xx_count,
                SUM(CASE WHEN response_status = 429 THEN 1 ELSE 0 END) as rate_limited_count
            ')
            ->whereDate('logged_at', '>=', $fromDate)
            ->whereDate('logged_at', '<=', $toDate)
            ->groupBy('integration_client_id')
            ->orderByDesc('request_count')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    public function getTopEndpointsForClient(string $clientId, int $limit = 10): array
    {
        return DB::table('api_usage_logs')
            ->selectRaw('endpoint, method, COUNT(*) as hits, ROUND(AVG(response_time_ms), 2) as avg_ms')
            ->where('integration_client_id', $clientId)
            ->groupBy(['endpoint', 'method'])
            ->orderByDesc('hits')
            ->limit($limit)
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();
    }
}
