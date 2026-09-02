<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ImportRow — Module 11 (Data Import / Migration)
 *
 * Represents a single row from an import file after parsing.
 * Tracks validation status and the resulting created model.
 *
 * Security: "Do not allow imports to overwrite records silently."
 * The result_model_id links to exactly what was created — any update
 * requires explicit user approval during the duplicate review step.
 */
class ImportRow extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_job_id',
        'import_batch_id',
        'row_number',
        'raw_data',
        'mapped_data',
        'status',
        'result_model_type',
        'result_model_id',
    ];

    protected $casts = [
        'raw_data'    => 'array',
        'mapped_data' => 'array',
    ];

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(ImportRowError::class);
    }

    public function duplicateCandidates(): HasMany
    {
        return $this->hasMany(ImportDuplicateCandidate::class);
    }

    public function isValid(): bool { return $this->status === 'valid'; }
    public function isImported(): bool { return $this->status === 'imported'; }
    public function isDuplicate(): bool { return $this->status === 'duplicate'; }
}
