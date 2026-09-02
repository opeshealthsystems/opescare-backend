<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FhirMappingVersion — Interoperability Suite (FHIR R4)
 *
 * Immutable snapshot of a FHIR mapping at a given version.
 * Enables rollback and audit of mapping changes over time.
 */
class FhirMappingVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'fhir_mapping_id',
        'version_number',
        'change_summary',
        'created_by',
        'is_current',
        'snapshot',         // Full JSON snapshot of field mapping at version time
    ];

    protected $casts = [
        'version_number' => 'integer',
        'is_current'     => 'boolean',
        'snapshot'       => 'array',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function fhirMapping(): BelongsTo
    {
        return $this->belongsTo(FhirMapping::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function markAsCurrent(): void
    {
        // Demote all other versions
        static::where('fhir_mapping_id', $this->fhir_mapping_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        $this->update(['is_current' => true]);
    }
}
