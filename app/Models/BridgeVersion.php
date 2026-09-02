<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BridgeVersion — Bridge Agent
 *
 * Tracks the installed version of a Bridge Agent over time.
 * Unsupported versions should be blocked from syncing.
 */
class BridgeVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_agent_id',
        'version',           // semver e.g. 2.1.4
        'status',            // current|outdated|deprecated|unsupported
        'installed_at',
        'deprecated_at',
    ];

    protected $casts = [
        'installed_at'  => 'datetime',
        'deprecated_at' => 'datetime',
    ];

    public function bridgeAgent(): BelongsTo
    {
        return $this->belongsTo(BridgeAgent::class);
    }

    public function isSupportedForSync(): bool
    {
        return $this->status !== 'unsupported';
    }

    public function deprecate(): void
    {
        $this->update(['status' => 'deprecated', 'deprecated_at' => now()]);
    }

    public function markUnsupported(): void
    {
        $this->update(['status' => 'unsupported']);
    }

    public function scopeForAgent($query, string $agentId)
    {
        return $query->where('bridge_agent_id', $agentId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', 'current');
    }
}
