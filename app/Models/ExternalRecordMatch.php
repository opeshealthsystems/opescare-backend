<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExternalRecordMatch — Data Quality & Reconciliation (Module 37)
 *
 * Tracks the result of attempting to match an inbound external record
 * (from a FHIR server, HL7 feed, CSV import, etc.) to an existing OpesCare
 * patient or resource.  If matching fails the record is held here in
 * 'unmatched' status so a human reviewer can manually reconcile it.
 *
 * Security: match confidence and match fields are advisory — a human must
 * confirm every non-automated match before data is merged into the EMR.
 */
class ExternalRecordMatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'reconciliation_case_id',
        'external_system',        // FHIR|HL7|CSV|DHIS2|etc
        'external_record_id',
        'external_record_type',   // Patient|Encounter|Observation|etc
        'matched_patient_id',
        'match_status',           // unmatched|matched|rejected|manual
        'match_confidence',       // 0.0 – 1.0
        'match_fields',
        'notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'match_confidence' => 'float',
        'match_fields'     => 'array',
        'resolved_at'      => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function reconciliationCase(): BelongsTo
    {
        return $this->belongsTo(ReconciliationCase::class);
    }

    public function matchedPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'matched_patient_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeUnmatched($query)
    {
        return $query->where('match_status', 'unmatched');
    }

    public function scopeForSystem($query, string $system)
    {
        return $query->where('external_system', $system);
    }

    public function scopeHighConfidence($query, float $threshold = 0.9)
    {
        return $query->where('match_confidence', '>=', $threshold);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isResolved(): bool
    {
        return in_array($this->match_status, ['matched', 'rejected', 'manual'], true);
    }

    public function resolve(string $status, string $resolvedBy, ?string $notes = null): void
    {
        $this->update([
            'match_status' => $status,
            'resolved_by'  => $resolvedBy,
            'resolved_at'  => now(),
            'notes'        => $notes ?? $this->notes,
        ]);
    }

    public function confidencePercent(): string
    {
        if ($this->match_confidence === null) {
            return '—';
        }
        return number_format($this->match_confidence * 100, 1) . '%';
    }
}
