<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * ExternalIdentifier — Interoperability Suite
 *
 * Generic cross-system identifier store. Maps OpesCare records to identifiers
 * used by external systems (FHIR, national registries, EHR systems, etc.).
 *
 * Follows FHIR R4 Identifier data type:
 * - system: namespace URI (e.g. http://hl7.org/fhir/sid/us-npi)
 * - value: the identifier value in that namespace
 * - use: official|temp|secondary|old
 */
class ExternalIdentifier extends Model
{
    use HasUuids;

    protected $fillable = [
        'resource_type',     // Patient|Facility|Practitioner|Organization|etc
        'resource_id',
        'system',            // Identifier system URI or custom URN
        'value',             // Identifier value
        'use',               // official|temp|secondary|old
        'type',              // MR|NI|PRN|DL|etc
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end'   => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForRecord($query, string $resourceType, string $resourceId)
    {
        return $query->where('resource_type', $resourceType)
                     ->where('resource_id', $resourceId);
    }

    public function scopeForSystem($query, string $system)
    {
        return $query->where('system', $system);
    }

    public function scopeOfficial($query)
    {
        return $query->where('use', 'official');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        if ($this->period_end !== null && $this->period_end->isPast()) {
            return false;
        }
        return $this->use !== 'old';
    }

    /**
     * Lookup an identifier for a record in a given system.
     */
    public static function lookup(string $resourceType, string $resourceId, string $system): ?self
    {
        return static::where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('system', $system)
            ->first();
    }
}
