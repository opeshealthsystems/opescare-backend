<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImportPreview — Module 11 (Data Import / Migration)
 *
 * Summary statistics shown to the user before they approve the import.
 * The approval is recorded here, providing an audit trail that a human
 * explicitly reviewed the import before execution.
 */
class ImportPreview extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_job_id',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'duplicate_candidates',
        'records_to_create',
        'records_to_update',
        'approved_for_import',
        'approved_by',
        'approved_at',
        'preview_sample',
    ];

    protected $casts = [
        'approved_for_import' => 'boolean',
        'approved_at'         => 'datetime',
        'preview_sample'      => 'array',
    ];

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function approve(string $approvedBy): void
    {
        $this->update([
            'approved_for_import' => true,
            'approved_by'         => $approvedBy,
            'approved_at'         => now(),
        ]);
    }

    public function successRate(): float
    {
        if ($this->total_rows === 0) return 0.0;
        return round(($this->valid_rows / $this->total_rows) * 100, 1);
    }
}
