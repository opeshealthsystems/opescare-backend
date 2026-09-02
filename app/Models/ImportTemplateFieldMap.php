<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImportTemplateFieldMap — Maps a DataDictionaryEntry to a CSV import template column.
 *
 * Provides the canonical mapping between CSV column headers and internal field definitions,
 * enabling import validation to reference the data dictionary directly.
 */
class ImportTemplateFieldMap extends Model
{
    use HasUuids;

    protected $fillable = [
        'data_dictionary_entry_id', 'import_type', 'csv_column_header',
        'column_position', 'is_required_in_template', 'default_value', 'transformation_hint',
    ];

    protected $casts = [
        'column_position'        => 'integer',
        'is_required_in_template' => 'boolean',
    ];

    public function dictionaryEntry(): BelongsTo
    {
        return $this->belongsTo(DataDictionaryEntry::class, 'data_dictionary_entry_id');
    }

    public function scopeForImportType($query, string $type)
    {
        return $query->where('import_type', $type)->orderBy('column_position');
    }
}
