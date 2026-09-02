<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** DeviceStatusLog — append-only status history for FacilityDevice. */
class DeviceStatusLog extends Model
{
    use HasUuids;

    protected $fillable = ['facility_device_id', 'status', 'note', 'logged_by'];

    public function facilityDevice(): BelongsTo { return $this->belongsTo(FacilityDevice::class); }

    public static function record(string $deviceId, string $status, ?string $note = null, ?string $loggedBy = null): self
    {
        return static::create(['facility_device_id' => $deviceId, 'status' => $status, 'note' => $note, 'logged_by' => $loggedBy]);
    }
}
