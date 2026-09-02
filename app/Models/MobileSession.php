<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MobileSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'device_fingerprint',
        'platform',
        'app_version',
        'access_token_hash',
        'last_seen_at',
        'expires_at',
        'revoked_at',
        'revoke_reason',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'expires_at'   => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }
        return true;
    }

    public function touchSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
