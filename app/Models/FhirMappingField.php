<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FhirMappingField — Interoperability Suite (FHIR R4)
 *
 * Defines the field-level mapping between an OpesCare field and
 * a FHIR path within the target resource.
 *
 * Transformation rules: none|concat|split|lookup|calculate
 */
class FhirMappingField extends Model
{
    use HasUuids;

    protected $fillable = [
        'fhir_mapping_id',
        'internal_field',          // e.g. patient.first_name
        'fhir_path',               // e.g. Patient.name[0].given[0]
        'transformation',          // none|concat|split|lookup|calculate
        'transformation_rule',
        'is_required',
        'is_identifier',
        'notes',
    ];

    protected $casts = [
        'is_required'   => 'boolean',
        'is_identifier' => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function fhirMapping(): BelongsTo
    {
        return $this->belongsTo(FhirMapping::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function hasTransformation(): bool
    {
        return $this->transformation !== null && $this->transformation !== 'none';
    }
}
