<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProviderDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'device_name',
        'device_fingerprint',
        'platform',
        'push_token',
        'push_active',
        'status',
        'registered_at',
        'last_seen_at',
    ];

    protected $casts = [
        'push_active'   => 'boolean',
        'registered_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked', 'push_active' => false]);
    }

    public function touchSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
