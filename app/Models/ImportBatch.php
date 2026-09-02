<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImportBatch — Module 34 (Data Import / Migration)
 *
 * Represents a chunk of rows within an import job.
 * Large imports are split into batches for resilience and progress tracking.
 */
class ImportBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_job_id',
        'batch_number',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'status',
        'started_at',
        'completed_at',
        'error_summary',
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

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function successRate(): float
    {
        if ($this->total_rows === 0) {
            return 0.0;
        }
        return round(($this->successful_rows / $this->total_rows) * 100, 2);
    }

    public function markStarted(): void
    {
        $this->update([
            'status'     => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(int $successCount, int $failCount): void
    {
        $this->update([
            'status'          => 'completed',
            'successful_rows' => $successCount,
            'failed_rows'     => $failCount,
            'processed_rows'  => $successCount + $failCount,
            'completed_at'    => now(),
        ]);
    }
}
