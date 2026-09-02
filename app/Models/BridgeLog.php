<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BridgeLog — Bridge Agent
 *
 * Structured log entry sent by a Bridge Agent to OpesCare.
 * Append-only; logs must never be updated or deleted.
 */
class BridgeLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_agent_id',
        'level',        // debug|info|warning|error|critical
        'component',    // connector|sync|auth|mapping
        'message',
        'context',
        'logged_at',
    ];

    protected $casts = [
        'context'   => 'array',
        'logged_at' => 'datetime',
    ];

    public function bridgeAgent(): BelongsTo
    {
        return $this->belongsTo(BridgeAgent::class);
    }

    public function scopeForAgent($query, string $agentId)
    {
        return $query->where('bridge_agent_id', $agentId);
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['error', 'critical']);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('logged_at', '>=', now()->subHours($hours));
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('BridgeLog records are append-only.');
    }

    public function delete(): ?bool
    {
        throw new \LogicException('BridgeLog records are append-only.');
    }
}
