<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * AnalyticsSnapshot — Analytics & Reporting (Module 19)
 *
 * Pre-computed, privacy-safe aggregate snapshots for dashboards and reports.
 * Snapshots contain ONLY de-identified aggregates — no patient-level data.
 */
class AnalyticsSnapshot extends Model
{
    use HasUuids;

    protected $fillable = [
        'snapshot_type',   // daily|weekly|monthly|quarterly
        'scope_type',      // facility|organization|platform
        'scope_id',
        'period_start',
        'period_end',
        'metrics',         // key => value map of aggregates
        'is_published',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'metrics'      => 'array',
        'is_published' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForScope($query, string $scopeType, string $scopeId)
    {
        return $query->where('scope_type', $scopeType)->where('scope_id', $scopeId);
    }

    public function metric(string $key, mixed $default = null): mixed
    {
        return data_get($this->metrics, $key, $default);
    }

    public function publish(): void
    {
        $this->update(['is_published' => true]);
    }
}
