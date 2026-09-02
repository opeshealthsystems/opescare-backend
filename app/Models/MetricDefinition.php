<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MetricDefinition — catalog entry for a named KPI metric.
 *
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property string $category  volume|quality|efficiency|financial|safety
 * @property string $unit
 * @property string $aggregation  count|sum|avg|min|max|rate
 * @property string $granularity  hourly|daily|weekly|monthly
 * @property string $scope  platform|facility|role
 * @property bool $is_active
 * @property array|null $computation_config
 * @property string $display_format  number|percentage|currency|duration
 * @property float|null $target_value
 * @property float|null $warning_threshold
 * @property float|null $critical_threshold
 */
class MetricDefinition extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'category',
        'unit',
        'aggregation',
        'granularity',
        'scope',
        'is_active',
        'computation_config',
        'display_format',
        'target_value',
        'warning_threshold',
        'critical_threshold',
        'created_by',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'computation_config' => 'array',
        'target_value'       => 'decimal:2',
        'warning_threshold'  => 'decimal:2',
        'critical_threshold' => 'decimal:2',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(MetricSnapshot::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
