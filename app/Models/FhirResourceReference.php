<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * FhirResourceReference — Interoperability Suite (FHIR R4)
 *
 * Tracks the external FHIR server resource ID for an OpesCare record.
 * Enables bidirectional lookups between internal records and FHIR resources.
 *
 * One record per (internal_resource_type, internal_record_id, fhir_resource_type).
 */
class FhirResourceReference extends Model
{
    use HasUuids;

    protected $fillable = [
        'internal_resource_type',  // Patient|Encounter|Observation|etc
        'internal_record_id',
        'fhir_resource_type',      // FHIR R4 resource name
        'fhir_resource_id',        // External FHIR server resource ID
        'fhir_server_url',
        'fhir_version',            // R4|R4B|R5
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForRecord($query, string $resourceType, string $recordId)
    {
        return $query->where('internal_resource_type', $resourceType)
                     ->where('internal_record_id', $recordId);
    }

    public function scopeForFhirType($query, string $fhirType)
    {
        return $query->where('fhir_resource_type', $fhirType);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function markSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    public function fhirUrl(): string
    {
        $base = rtrim($this->fhir_server_url ?? '', '/');
        return "{$base}/{$this->fhir_resource_type}/{$this->fhir_resource_id}";
    }
}
