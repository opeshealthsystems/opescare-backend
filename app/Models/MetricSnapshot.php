<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MetricSnapshot — pre-computed metric value for a specific period.
 *
 * @property string $id
 * @property string $metric_definition_id
 * @property string|null $facility_id  null = platform-wide
 * @property \Illuminate\Support\Carbon $period_date
 * @property string $period_granularity  daily|weekly|monthly
 * @property float|null $value
 * @property float|null $previous_value
 * @property float|null $change_pct
 * @property string $status  normal|warning|critical
 * @property int|null $sample_count
 * @property array|null $breakdown
 * @property \Illuminate\Support\Carbon $computed_at
 */
class MetricSnapshot extends Model
{
    use HasUuids;

    protected $fillable = [
        'metric_definition_id',
        'facility_id',
        'period_date',
        'period_granularity',
        'value',
        'previous_value',
        'change_pct',
        'status',
        'sample_count',
        'breakdown',
        'computed_at',
    ];

    protected $casts = [
        'period_date'  => 'date',
        'value'        => 'decimal:4',
        'previous_value' => 'decimal:4',
        'change_pct'   => 'decimal:2',
        'breakdown'    => 'array',
        'computed_at'  => 'datetime',
    ];

    public function metricDefinition(): BelongsTo
    {
        return $this->belongsTo(MetricDefinition::class);
    }

    public function scopeForFacility($query, ?string $facilityId)
    {
        if ($facilityId === null) {
            return $query->whereNull('facility_id');
        }
        return $query->where('facility_id', $facilityId);
    }

    public function scopeForPeriod($query, \Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        return $query->whereBetween('period_date', [$from->toDateString(), $to->toDateString()]);
    }

    public function scopeGranularity($query, string $granularity)
    {
        return $query->where('period_granularity', $granularity);
    }

    public function isAboveTarget(): bool
    {
        if ($this->value === null || $this->metricDefinition->target_value === null) {
            return false;
        }
        return $this->value >= $this->metricDefinition->target_value;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'critical' => 'badge--danger',
            'warning'  => 'badge--warning',
            default    => 'badge--success',
        };
    }

    public function formattedValue(): string
    {
        $def = $this->metricDefinition;
        if ($this->value === null) {
            return '—';
        }

        return match ($def->display_format ?? 'number') {
            'percentage' => number_format($this->value, 1) . '%',
            'currency'   => number_format($this->value, 2),
            'duration'   => $this->value . ' min',
            default      => number_format($this->value),
        };
    }
}
