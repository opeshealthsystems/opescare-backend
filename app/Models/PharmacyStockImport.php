<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A pharmacy's stock spreadsheet between upload and commit — and, after it,
 * the audit record of what went in. See the migration for retention rules.
 */
class PharmacyStockImport extends Model
{
    use HasUuids;

    public const MODES = ['delivery', 'stocktake'];

    protected $fillable = [
        'facility_id', 'uploaded_by', 'status', 'mode', 'file_format', 'original_name',
        'header_row', 'row_count', 'headers', 'rows', 'mapping', 'results',
        'imported_count', 'error_count', 'goods_receipt_id', 'failure', 'committed_at',
    ];

    protected $casts = [
        'headers'      => 'array',
        'rows'         => 'array',
        'mapping'      => 'array',
        'results'      => 'array',
        'committed_at' => 'datetime',
    ];

    public function isOpen(): bool
    {
        return in_array($this->status, ['mapping', 'preview'], true);
    }
}
