<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApiPayloadFieldMap — Documents how a DataDictionaryEntry appears in API requests/responses.
 *
 * PHI fields that appear in API responses MUST set is_redacted_in_logs = true
 * to prevent sensitive data from appearing in structured logs.
 */
class ApiPayloadFieldMap extends Model
{
    use HasUuids;

    protected $fillable = [
        'data_dictionary_entry_id', 'api_version', 'endpoint_pattern',
        'json_key', 'direction', 'is_required_in_payload', 'is_redacted_in_logs',
    ];

    protected $casts = [
        'is_required_in_payload' => 'boolean',
        'is_redacted_in_logs'    => 'boolean',
    ];

    public function dictionaryEntry(): BelongsTo
    {
        return $this->belongsTo(DataDictionaryEntry::class, 'data_dictionary_entry_id');
    }

    public function scopeForEndpoint($query, string $pattern)
    {
        return $query->where('endpoint_pattern', $pattern);
    }

    public function scopeRedacted($query)
    {
        return $query->where('is_redacted_in_logs', true);
    }
}
