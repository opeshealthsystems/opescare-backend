<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BridgeConflict — Bridge Agent
 *
 * A data conflict detected during a Bridge sync job.
 * Conflicts require human review before the external data can be
 * applied to OpesCare records.
 *
 * Security: Patient data in external_data/internal_data is subject to
 * the same access controls as any other PHI.
 */
class BridgeConflict extends Model
{
    use HasUuids;

    protected $fillable = [
        'bridge_sync_job_id',
        'resource_type',
        'external_id',
        'internal_record_id',
        'external_data',
        'internal_data',
        'conflict_type',     // duplicate|version_mismatch|invalid_data
        'status',            // open|resolved|rejected
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'external_data' => 'array',
        'internal_data' => 'array',
        'resolved_at'   => 'datetime',
    ];

    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(BridgeSyncJob::class);
    }

    public function resolve(string $resolvedBy): void
    {
        $this->update(['status' => 'resolved', 'resolved_by' => $resolvedBy, 'resolved_at' => now()]);
    }

    public function reject(string $resolvedBy): void
    {
        $this->update(['status' => 'rejected', 'resolved_by' => $resolvedBy, 'resolved_at' => now()]);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
