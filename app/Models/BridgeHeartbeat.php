<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BridgeHeartbeat — Bridge Agent
 *
 * Liveness ping from a Bridge Agent. Heartbeats are used to determine
 * whether an agent is online and healthy. Missing heartbeats trigger alerts.
 */
class BridgeHeartbeat extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_agent_id',
        'bridge_device_id',
        'agent_version',
        'status',           // healthy|degraded|error
        'metrics',          // cpu|memory|queue_depth
        'received_at',
    ];

    protected $casts = [
        'metrics'     => 'array',
        'received_at' => 'datetime',
    ];

    public function bridgeAgent(): BelongsTo
    {
        return $this->belongsTo(BridgeAgent::class);
    }

    public function bridgeDevice(): BelongsTo
    {
        return $this->belongsTo(BridgeDevice::class);
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function scopeForAgent($query, string $agentId)
    {
        return $query->where('bridge_agent_id', $agentId);
    }

    public function scopeRecent($query, int $minutes = 10)
    {
        return $query->where('received_at', '>=', now()->subMinutes($minutes));
    }
}
