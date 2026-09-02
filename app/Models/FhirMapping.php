<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FhirMapping — Interoperability Suite (FHIR R4)
 *
 * Defines the mapping between an OpesCare internal resource and a
 * FHIR R4 resource type. Each mapping must be reviewed and approved
 * by the clinical/data governance team before it is activated.
 *
 * Rule: Do not claim FHIR compliance without a complete, approved mapping.
 */
class FhirMapping extends Model
{
    use HasUuids;

    protected $fillable = [
        'internal_resource',     // Patient|Encounter|Observation|MedicationRequest|etc
        'fhir_resource_type',    // FHIR R4 resource name
        'fhir_version',          // R4|R4B|R5
        'direction',             // outbound|inbound|bidirectional
        'status',                // draft|under_review|approved|deprecated
        'description',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function versions(): HasMany
    {
        return $this->hasMany(FhirMappingVersion::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FhirMappingField::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(MappingError::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForResource($query, string $resource)
    {
        return $query->where('internal_resource', $resource);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function approve(string $approvedBy): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function currentVersion(): ?FhirMappingVersion
    {
        return $this->versions()->where('is_current', true)->first();
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'approved'     => 'badge badge--success',
            'under_review' => 'badge badge--warning',
            'draft'        => 'badge badge--neutral',
            'deprecated'   => 'badge badge--danger',
            default        => 'badge badge--neutral',
        };
    }
}
