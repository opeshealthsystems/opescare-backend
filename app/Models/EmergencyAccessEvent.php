<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyAccessEvent extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'provider_id',
        'reason',
        'records_viewed',
        // The break-glass GRANT fields (migration 2026_09_05_214500). Without
        // these a grant cannot be written through the model at all, so every
        // caller has to reach past mass assignment to set them.
        'expires_at',
        'revoked_at',
        'notified_at',
    ];

    protected $casts = [
        'records_viewed' => 'array',
        // Cast, not string. These three are compared to `now()` on every read
        // taken under an override — EmergencyAccessService::grantIsActive()
        // and grantSecondsRemaining() — and an uncast timestamptz comes back
        // as a raw string each caller then has to Carbon::parse() by hand.
        'expires_at'     => 'datetime',
        'revoked_at'     => 'datetime',
        'notified_at'    => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
