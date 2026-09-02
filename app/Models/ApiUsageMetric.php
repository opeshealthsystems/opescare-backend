<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApiUsageMetric — Connect Suite / Developer Portal
 *
 * Pre-aggregated API usage counters per credential, endpoint, and time period.
 * Used for rate-limit enforcement, billing, and abuse detection dashboards.
 */
class ApiUsageMetric extends Model
{
    use HasUuids;

    protected $fillable = [
        'api_credential_id',
        'endpoint',
        'method',           // GET|POST|PUT|DELETE
        'request_count',
        'error_count',
        'avg_response_ms',
        'period',           // hourly|daily|monthly
        'period_start',
    ];

    protected $casts = [
        'request_count'  => 'integer',
        'error_count'    => 'integer',
        'avg_response_ms' => 'float',
        'period_start'   => 'datetime',
    ];

    public function apiCredential(): BelongsTo
    {
        return $this->belongsTo(ApiCredential::class);
    }

    public function errorRate(): float
    {
        if ($this->request_count === 0) {
            return 0.0;
        }
        return round(($this->error_count / $this->request_count) * 100, 2);
    }

    public function scopeForCredential($query, string $credentialId)
    {
        return $query->where('api_credential_id', $credentialId);
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }
}
