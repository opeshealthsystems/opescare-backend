<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BridgeSyncJob — Bridge Agent
 *
 * A single sync run on a BridgeConnector (push or pull direction).
 */
class BridgeSyncJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_connector_id',
        'direction',          // push|pull
        'status',             // pending|running|completed|failed|partial
        'records_total',
        'records_synced',
        'records_failed',
        'records_skipped',
        'error_summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'records_total'   => 'integer',
        'records_synced'  => 'integer',
        'records_failed'  => 'integer',
        'records_skipped' => 'integer',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
    ];

    public function bridgeConnector(): BelongsTo
    {
        return $this->belongsTo(BridgeConnector::class);
    }

    public function syncRecords(): HasMany
    {
        return $this->hasMany(BridgeSyncRecord::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(BridgeConflict::class);
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'partial', 'failed'], true);
    }

    public function durationSeconds(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return (int) $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }
}
