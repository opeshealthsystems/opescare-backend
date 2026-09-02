<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BridgeConnector — Bridge Agent
 *
 * Configuration for a data source connector (CSV watcher, HL7 listener,
 * FHIR endpoint, local DB, external API) attached to a Bridge Agent.
 *
 * Security: config JSON may contain credentials and is encrypted at
 * the application layer before storage.
 */
class BridgeConnector extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_agent_id',
        'connector_type',   // csv|hl7|fhir|db|api
        'name',
        'config',           // encrypted connection params
        'status',           // active|paused|error
        'last_run_at',
    ];

    protected $casts = [
        'config'      => 'encrypted:array',
        'last_run_at' => 'datetime',
    ];

    public function bridgeAgent(): BelongsTo
    {
        return $this->belongsTo(BridgeAgent::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(BridgeMapping::class);
    }

    public function syncJobs(): HasMany
    {
        return $this->hasMany(BridgeSyncJob::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
