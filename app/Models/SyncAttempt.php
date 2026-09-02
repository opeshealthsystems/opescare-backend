<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SyncAttempt — Offline Sync (Module 24)
 *
 * Records each attempt by a device to push or pull data during sync.
 * Used for diagnosing sync failures and measuring sync performance.
 */
class SyncAttempt extends Model
{
    use HasUuids;

    protected $fillable = [
        'sync_job_id',
        'device_id',
        'user_id',
        'direction',        // push|pull
        'status',           // pending|running|success|failed|conflict
        'records_synced',
        'conflicts_found',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'records_synced'  => 'integer',
        'conflicts_found' => 'integer',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
    ];

    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(SyncJob::class);
    }

    public function durationSeconds(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return (int) $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function hasConflicts(): bool
    {
        return $this->conflicts_found > 0;
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
