<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ModuleFieldMap — Links a DataDictionaryEntry to its use within a specific module/model.
 *
 * Tracks which Eloquent model and column physically implements a dictionary field,
 * enabling the data governance team to audit field usage across modules.
 */
class ModuleFieldMap extends Model
{
    use HasUuids;

    protected $fillable = [
        'data_dictionary_entry_id', 'module', 'model_class',
        'column_name', 'usage_context', 'is_indexed',
    ];

    protected $casts = [
        'is_indexed' => 'boolean',
    ];

    public function dictionaryEntry(): BelongsTo
    {
        return $this->belongsTo(DataDictionaryEntry::class, 'data_dictionary_entry_id');
    }

    public function scopeForModule($query, string $module) { return $query->where('module', $module); }
}
