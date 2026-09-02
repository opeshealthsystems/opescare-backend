<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** DeviceAssignment — tracks who/where a FacilityDevice is assigned to. */
class DeviceAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_device_id', 'assigned_to_type', 'assigned_to_id',
        'assigned_at', 'returned_at', 'assigned_by',
    ];

    protected $casts = ['assigned_at' => 'datetime', 'returned_at' => 'datetime'];

    public function facilityDevice(): BelongsTo { return $this->belongsTo(FacilityDevice::class); }

    public function returnDevice(): void { $this->update(['returned_at' => now()]); }

    public function isActive(): bool { return $this->returned_at === null; }
}
