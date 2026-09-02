<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PushDeviceToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'device_fingerprint',
        'platform',
        'push_token',
        'is_active',
        'registered_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'registered_at' => 'datetime',
        'revoked_at'    => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function revoke(): void
    {
        $this->update(['is_active' => false, 'revoked_at' => now()]);
    }
}
