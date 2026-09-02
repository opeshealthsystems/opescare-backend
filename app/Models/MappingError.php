<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MappingError — Interoperability Suite (FHIR R4)
 *
 * Records errors that occur during FHIR mapping operations.
 * Errors must be reviewed and resolved before affected records
 * can be considered interoperable.
 */
class MappingError extends Model
{
    use HasUuids;

    protected $fillable = [
        'fhir_mapping_id',
        'source_resource_type',
        'source_record_id',
        'error_type',            // missing_field|invalid_code|transformation_failed|validation_failed
        'error_message',
        'error_context',
        'direction',             // outbound|inbound
        'resolved',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'error_context' => 'array',
        'resolved'      => 'boolean',
        'resolved_at'   => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function fhirMapping(): BelongsTo
    {
        return $this->belongsTo(FhirMapping::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function resolve(string $resolvedBy): void
    {
        $this->update([
            'resolved'    => true,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ]);
    }

    public function isUnresolved(): bool
    {
        return ! $this->resolved;
    }
}
