<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImportDuplicateCandidate — Module 11 (Data Import / Migration)
 *
 * Flagged potential duplicates between an import row and an existing record.
 * Resolution must be made by a human — no automatic merging allowed.
 *
 * Security constraint: "Do not allow imports to overwrite records silently."
 */
class ImportDuplicateCandidate extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_job_id',
        'import_row_id',
        'existing_model_type',
        'existing_model_id',
        'similarity_score',
        'matching_fields',
        'resolution',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'similarity_score' => 'decimal:4',
        'matching_fields'  => 'array',
        'resolved_at'      => 'datetime',
    ];

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ImportRow::class);
    }

    public function isPending(): bool { return $this->resolution === 'pending'; }

    public function resolve(string $resolution, string $resolvedBy): void
    {
        $this->update([
            'resolution'  => $resolution,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ]);
    }
}
