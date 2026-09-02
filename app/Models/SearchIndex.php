<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * SearchIndex — Global Search (Module 38)
 *
 * Denormalised search index that powers permission-aware full-text search
 * across all OpesCare resource types (Patient, Facility, Medicine, Lab, etc.).
 *
 * Security constraints:
 * - search_text must NEVER contain raw identifiers that would expose patient
 *   data to users who do not have permission to see the underlying record.
 * - Every query against this table MUST be filtered by facility_id and/or
 *   organization_id to enforce tenancy boundaries.
 * - Sensitive searches (patient records) must be logged in search_logs.
 */
class SearchIndex extends Model
{
    use HasUuids;

    protected $fillable = [
        'resource_type',    // Patient|Facility|Medicine|Lab|Practitioner|etc
        'resource_id',
        'facility_id',
        'organization_id',
        'search_text',      // denormalized searchable content
        'metadata',         // extra facets for filtering
        'is_active',
        'indexed_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'is_active'  => 'boolean',
        'indexed_at' => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForResourceType($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('search_text', 'ilike', '%' . $term . '%');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Upsert the index entry for a resource.
     * Keeps search_indices table in sync when records are updated.
     */
    public static function reindex(
        string $resourceType,
        string $resourceId,
        string $searchText,
        ?string $facilityId = null,
        ?string $organizationId = null,
        array $metadata = []
    ): self {
        return static::updateOrCreate(
            ['resource_type' => $resourceType, 'resource_id' => $resourceId],
            [
                'facility_id'     => $facilityId,
                'organization_id' => $organizationId,
                'search_text'     => $searchText,
                'metadata'        => $metadata,
                'is_active'       => true,
                'indexed_at'      => now(),
            ]
        );
    }
}
