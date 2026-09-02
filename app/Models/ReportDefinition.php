<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * ReportDefinition — Module 19 (Analytics & Reporting)
 *
 * Defines a named analytics report including its type, parameter schema,
 * metric keys, export formats, and schedule.
 */
class ReportDefinition extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'report_type',              // operational|clinical|financial|audit|public_health
        'description',
        'parameters_schema',        // JSON Schema for report parameters
        'metric_keys',              // Array of DashboardMetric keys
        'export_formats',           // Array: csv|pdf|json|excel
        'schedule',                 // daily|weekly|monthly|none
        'requires_facility_scope',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'parameters_schema'       => 'array',
        'metric_keys'             => 'array',
        'export_formats'          => 'array',
        'requires_facility_scope' => 'boolean',
        'is_active'               => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('schedule')
            ->where('schedule', '!=', 'none');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function supportsExport(string $format): bool
    {
        return in_array($format, $this->export_formats ?? []);
    }

    public function isScheduled(): bool
    {
        return $this->schedule !== null && $this->schedule !== 'none';
    }
}
