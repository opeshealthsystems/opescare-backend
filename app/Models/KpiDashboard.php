<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * KpiDashboard — Analytics & KPI
 *
 * Defines a named, role-specific KPI dashboard layout.
 * References MetricDefinition keys in an ordered configuration.
 */
class KpiDashboard extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'target_role',     // facility_admin|hospital_director|super_admin|public_health
        'metric_keys',
        'is_published',
        'layout',
    ];

    protected $casts = [
        'metric_keys'  => 'array',
        'layout'       => 'array',
        'is_published' => 'boolean',
    ];

    public function publish(): void
    {
        $this->update(['is_published' => true]);
    }

    public function unpublish(): void
    {
        $this->update(['is_published' => false]);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('target_role', $role);
    }
}
