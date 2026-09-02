<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TriageVitalSign — Triage & Emergency Module (Module 16)
 *
 * Stores vital-sign readings taken during triage assessment.
 * Linked to a TriageRecord and optionally to a Visit.
 *
 * CDSS Safety (NON-NEGOTIABLE):
 * - Vital signs recorded here are clinical data.
 * - This model NEVER auto-corrects, normalises, or silently overwrites values.
 * - Abnormal readings trigger advisory alerts — NOT automatic interventions.
 * - Clinicians make all treatment decisions.
 */
class TriageVitalSign extends Model
{
    use HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'triage_record_id',
        'visit_id',
        'patient_id',
        'temperature',          // °C
        'pulse_rate',           // bpm
        'respiratory_rate',     // breaths/min
        'systolic_bp',          // mmHg
        'diastolic_bp',         // mmHg
        'oxygen_saturation',    // %
        'weight_kg',
        'height_cm',
        'gcs_score',            // 3–15
        'pain_score',           // 0–10
        'consciousness_level',  // alert|voice|pain|unresponsive (AVPU)
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'temperature'        => 'decimal:2',
        'weight_kg'          => 'decimal:2',
        'height_cm'          => 'decimal:2',
        'pulse_rate'         => 'integer',
        'respiratory_rate'   => 'integer',
        'systolic_bp'        => 'integer',
        'diastolic_bp'       => 'integer',
        'oxygen_saturation'  => 'integer',
        'gcs_score'          => 'integer',
        'pain_score'         => 'integer',
        'recorded_at'        => 'datetime',
    ];

    public function triageRecord(): BelongsTo
    {
        return $this->belongsTo(TriageRecord::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Advisory flag helpers (NOT clinical decisions) ────────────────────────

    /** Advisory: temperature may indicate fever (>38°C). Clinical assessment required. */
    public function hasFeverFlag(): bool
    {
        return $this->temperature !== null && (float) $this->temperature > 38.0;
    }

    /** Advisory: SpO2 may be low (<94%). Clinical assessment required. */
    public function hasHypoxiaFlag(): bool
    {
        return $this->oxygen_saturation !== null && $this->oxygen_saturation < 94;
    }

    /** Advisory: GCS ≤8 may indicate altered consciousness. Immediate clinical review required. */
    public function hasAlteredConsciousnessFlag(): bool
    {
        return ($this->gcs_score !== null && $this->gcs_score <= 8)
            || in_array($this->consciousness_level, ['pain', 'unresponsive'], true);
    }

    public function bmi(): ?float
    {
        if ($this->weight_kg && $this->height_cm && (float) $this->height_cm > 0) {
            $heightM = (float) $this->height_cm / 100;
            return round((float) $this->weight_kg / ($heightM * $heightM), 1);
        }
        return null;
    }

    public function scopeForRecord($query, string $triageRecordId)
    {
        return $query->where('triage_record_id', $triageRecordId);
    }
}
