<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * SearchLog — Module 14 (Global Search)
 *
 * Audit trail for all searches, especially sensitive ones (patient, Health ID).
 * Append-only — never update or delete entries.
 */
class SearchLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'actor_id', 'actor_type', 'facility_id',
        'query_text', 'search_target', 'results_count',
        'is_sensitive', 'ip_address', 'searched_at',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'searched_at'  => 'datetime',
    ];

    public static function record(
        string $queryText,
        string $searchTarget,
        int $resultsCount,
        ?string $actorId = null,
        string $actorType = 'user',
        ?string $facilityId = null,
        bool $isSensitive = false,
        ?string $ipAddress = null
    ): self {
        return static::create([
            'actor_id'      => $actorId,
            'actor_type'    => $actorType,
            'facility_id'   => $facilityId,
            'query_text'    => $queryText,
            'search_target' => $searchTarget,
            'results_count' => $resultsCount,
            'is_sensitive'  => $isSensitive,
            'ip_address'    => $ipAddress,
            'searched_at'   => now(),
        ]);
    }
}
