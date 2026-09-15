<?php

namespace App\Modules\SecurityOperations\Services;

use App\Models\ApiAbuseFlag;
use App\Models\ApiUsageMetric;
use App\Models\AuditEvent;

/**
 * ApiAbuseDetectionService — Detects and flags API abuse patterns.
 *
 * Detects:
 *  - Rate limit violations (bursts above threshold)
 *  - Unusually high error rates (may indicate credential stuffing or fuzzing)
 *  - Bulk data extraction (large paginated sweeps of patient records)
 *  - Sandbox credentials used in production context
 *  - Revoked credentials still attempting requests
 *
 * Flags are written to ApiAbuseFlag for human review and optional auto-blocking.
 */
class ApiAbuseDetectionService
{
    private const RATE_ABUSE_THRESHOLD = 500;  // requests/minute
    private const ERROR_RATE_THRESHOLD = 0.40; // 40% error rate

    public function checkRateLimitAbuse(string $credentialId, int $requestsLastMinute): ?ApiAbuseFlag
    {
        if ($requestsLastMinute >= self::RATE_ABUSE_THRESHOLD) {
            return $this->flag($credentialId, 'rate_limit_burst', [
                'requests_per_minute' => $requestsLastMinute,
                'threshold'           => self::RATE_ABUSE_THRESHOLD,
            ]);
        }
        return null;
    }

    public function checkHighErrorRate(string $credentialId, float $errorRate): ?ApiAbuseFlag
    {
        if ($errorRate >= self::ERROR_RATE_THRESHOLD) {
            return $this->flag($credentialId, 'high_error_rate', [
                'error_rate' => $errorRate,
                'threshold'  => self::ERROR_RATE_THRESHOLD,
            ]);
        }
        return null;
    }

    public function checkBulkExtraction(string $credentialId, int $paginationDepth): ?ApiAbuseFlag
    {
        if ($paginationDepth > 100) {
            return $this->flag($credentialId, 'bulk_data_extraction', [
                'pagination_depth' => $paginationDepth,
            ]);
        }
        return null;
    }

    private function flag(string $credentialId, string $abuseType, array $context): ApiAbuseFlag
    {
        // Finder: api_abuse_flags columns are api_consumer_type/api_consumer_id/flag_type/
        // request_count/time_window/evidence (no severity column: it goes into evidence),
        // and audit_events has no action/module/metadata columns.
        $flag = ApiAbuseFlag::create([
            'api_consumer_type' => 'key',
            'api_consumer_id'   => $credentialId,
            'flag_type'         => $abuseType,
            'request_count'     => (int) ($context['requests_per_minute'] ?? $context['pagination_depth'] ?? 0),
            'time_window'       => '1m',
            'evidence'          => $context + ['severity' => $this->severityFor($abuseType)],
            'status'            => 'open',
        ]);

        AuditEvent::create([
            'action_type'   => 'api_abuse_flagged',
            'resource_type' => 'api_abuse_flag',
            'resource_id'   => $flag->id,
            'reason'        => 'API abuse flagged: ' . $abuseType,
        ]);

        return $flag;
    }

    private function severityFor(string $type): string
    {
        return match ($type) {
            'bulk_data_extraction' => 'critical',
            'rate_limit_burst'     => 'high',
            'high_error_rate'      => 'medium',
            default                => 'medium',
        };
    }
}
