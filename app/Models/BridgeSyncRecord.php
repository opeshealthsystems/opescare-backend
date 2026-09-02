<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BridgeSyncRecord — Bridge Agent
 *
 * Per-record outcome for a BridgeSyncJob.
 * Idempotent: if the same external_id is synced again with the same outcome
 * the record is updated; otherwise a new record is created for the new job.
 */
class BridgeSyncRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_sync_job_id',
        'external_id',
        'resource_type',
        'outcome',           // created|updated|skipped|failed|conflict
        'internal_record_id',
        'error_message',
    ];

    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(BridgeSyncJob::class);
    }

    public function isSuccessful(): bool
    {
        return in_array($this->outcome, ['created', 'updated'], true);
    }

    public function scopeFailed($query)
    {
        return $query->where('outcome', 'failed');
    }

    public function scopeConflicts($query)
    {
        return $query->where('outcome', 'conflict');
    }
}
