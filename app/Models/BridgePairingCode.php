<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BridgePairingCode — Bridge Agent
 *
 * One-time code used to pair a Bridge Agent with OpesCare.
 * Expires after a short TTL and becomes invalid once used.
 */
class BridgePairingCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_agent_id',
        'code',
        'status',           // pending|used|expired
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function bridgeAgent(): BelongsTo
    {
        return $this->belongsTo(BridgeAgent::class);
    }

    public function isValid(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    public function consume(): void
    {
        $this->update(['status' => 'used', 'used_at' => now()]);
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'pending')->where('expires_at', '>', now());
    }
}
