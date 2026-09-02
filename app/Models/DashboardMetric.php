<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * DashboardMetric — Module 19 (Analytics & Reporting)
 *
 * Defines a named metric used in dashboards and reports.
 * Metrics are computed by the analytics aggregation service
 * and stored in MetricSnapshot for historical tracking.
 */
class DashboardMetric extends Model
{
    use HasUuids;

    protected $fillable = [
        'metric_key',
        'metric_name',
        'metric_category',      // operational|clinical|financial|quality
        'aggregation_method',   // count|sum|average|rate|ratio|custom
        'data_source',
        'filters',
        'display_format',       // number|percentage|currency|duration
        'unit',
        'is_active',
    ];

    protected $casts = [
        'filters'   => 'array',
        'is_active' => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('metric_category', $category);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function formatValue(float $value): string
    {
        return match($this->display_format) {
            'percentage' => number_format($value, 1) . '%',
            'currency'   => number_format($value, 2),
            'duration'   => $this->formatDuration((int) $value),
            default      => number_format($value, 0),
        };
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes}m";
        }
        $hours = (int) ($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
    }
}
