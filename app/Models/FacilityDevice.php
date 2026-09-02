<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FacilityDevice — Facility Hardware & Device Strategy
 *
 * A physical device (tablet, kiosk, scanner, printer) registered to a facility.
 */
class FacilityDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'device_type', 'name', 'serial_number', 'model',
        'status', 'last_seen_at',
    ];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function assignments(): HasMany { return $this->hasMany(DeviceAssignment::class); }
    public function statusLogs(): HasMany { return $this->hasMany(DeviceStatusLog::class); }
    public function revocations(): HasMany { return $this->hasMany(DeviceRevocation::class); }

    public function isActive(): bool { return $this->status === 'active'; }

    public function retire(): void { $this->update(['status' => 'retired']); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeForFacility($query, string $facilityId) { return $query->where('facility_id', $facilityId); }
}
