<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FieldDefinition — Context-specific validation and metadata for a DataDictionaryEntry.
 *
 * A single dictionary entry may have different validation rules in different contexts:
 * - API payloads may require stricter formats
 * - Import templates may allow looser formats with transformation hints
 * - UI forms may have different UX constraints
 */
class FieldDefinition extends Model
{
    use HasUuids;

    protected $fillable = [
        'data_dictionary_entry_id', 'context', 'validation_rules',
        'min_length', 'max_length', 'example_value', 'notes',
    ];

    protected $casts = [
        'min_length' => 'integer',
        'max_length' => 'integer',
    ];

    public function dictionaryEntry(): BelongsTo
    {
        return $this->belongsTo(DataDictionaryEntry::class, 'data_dictionary_entry_id');
    }
}
