<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BridgeDevice — Bridge Agent
 *
 * Represents the physical machine or VM running a Bridge Agent instance.
 */
class BridgeDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_agent_id',
        'device_name',
        'hardware_id',
        'os_type',          // windows|linux|macos
        'os_version',
        'status',           // pending|active|suspended|offline
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function bridgeAgent(): BelongsTo
    {
        return $this->belongsTo(BridgeAgent::class);
    }

    public function isOnline(): bool
    {
        return $this->status === 'active'
            && $this->last_seen_at !== null
            && $this->last_seen_at->diffInMinutes(now()) <= 5;
    }

    public function recordHeartbeat(): void
    {
        $this->update(['last_seen_at' => now(), 'status' => 'active']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
