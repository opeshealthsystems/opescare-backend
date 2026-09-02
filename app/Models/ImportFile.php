<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImportFile — Module 11 (Data Import / Migration)
 *
 * Stores metadata about the uploaded import file (CSV/Excel).
 * The file itself is stored privately; only the path reference is kept.
 */
class ImportFile extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_job_id',
        'original_name',
        'stored_path',
        'mime_type',
        'file_size',
        'extension',
        'detected_columns',
        'detected_rows',
        'detected_headers',
    ];

    protected $casts = [
        'detected_headers' => 'array',
    ];

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function formattedSize(): string
    {
        $size = $this->file_size ?? 0;
        if ($size < 1024) return $size . ' B';
        if ($size < 1048576) return round($size / 1024, 1) . ' KB';
        return round($size / 1048576, 1) . ' MB';
    }
}
