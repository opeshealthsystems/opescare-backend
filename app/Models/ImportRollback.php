<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImportRollback — Module 34 (Data Import / Migration)
 *
 * Tracks rollback operations for import jobs.
 * When an import is rolled back, affected records are soft-deleted or
 * reverted to their prior state. The rollback log provides an audit trail.
 *
 * Security: Do not allow imports to overwrite records silently. (constraint)
 */
class ImportRollback extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_job_id',
        'initiated_by',
        'reason',
        'status',
        'rows_rolled_back',
        'rollback_log',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function markStarted(): void
    {
        $this->update([
            'status'     => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(int $rowsRolledBack, string $log = ''): void
    {
        $this->update([
            'status'           => 'completed',
            'rows_rolled_back' => $rowsRolledBack,
            'rollback_log'     => $log,
            'completed_at'     => now(),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'    => 'badge badge--warning',
            'processing' => 'badge badge--info',
            'completed'  => 'badge badge--success',
            'failed'     => 'badge badge--danger',
            default      => 'badge badge--neutral',
        };
    }
}
