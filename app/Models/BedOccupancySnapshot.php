<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BedOccupancySnapshot — Ward Management (Module 22)
 *
 * Periodic snapshot of bed occupancy per facility/ward.
 * Used for analytics, capacity planning, and public health reporting.
 */
class BedOccupancySnapshot extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'ward_id',
        'total_beds',
        'occupied_beds',
        'available_beds',
        'reserved_beds',
        'occupancy_rate',
        'captured_at',
    ];

    protected $casts = [
        'total_beds'     => 'integer',
        'occupied_beds'  => 'integer',
        'available_beds' => 'integer',
        'reserved_beds'  => 'integer',
        'occupancy_rate' => 'float',
        'captured_at'    => 'datetime',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function isCriticalCapacity(float $threshold = 90.0): bool
    {
        return $this->occupancy_rate >= $threshold;
    }

    public function formattedRate(): string
    {
        return number_format($this->occupancy_rate, 1) . '%';
    }

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('captured_at');
    }
}
