<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** DeviceRevocation — records a device revocation event (lost/stolen/compromised). */
class DeviceRevocation extends Model
{
    use HasUuids;

    protected $fillable = ['facility_device_id', 'reason', 'revoked_by', 'notes'];

    public function facilityDevice(): BelongsTo { return $this->belongsTo(FacilityDevice::class); }
}
