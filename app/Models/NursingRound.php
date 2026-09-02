<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NursingRound — Module 19 (Ward / Admission / Bed Management)
 *
 * Records nursing assessments performed at scheduled intervals during
 * an inpatient stay. Captures vital signs, observations, interventions.
 */
class NursingRound extends Model
{
    use HasUuids;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'nurse_id',
        'round_time',
        'vital_signs',          // JSON: BP, temp, pulse, SpO2, respiratory_rate etc.
        'pain_level',           // 0-10 numeric string
        'observations',
        'interventions',
        'patient_response',     // stable|improving|deteriorating|critical
        'escalation_required',
    ];

    protected $casts = [
        'round_time'          => 'datetime',
        'vital_signs'         => 'array',
        'escalation_required' => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isCritical(): bool
    {
        return $this->patient_response === 'critical';
    }

    public function requiresEscalation(): bool
    {
        return (bool) $this->escalation_required;
    }

    public function statusBadgeClass(): string
    {
        return match($this->patient_response) {
            'critical'      => 'badge badge--danger',
            'deteriorating' => 'badge badge--warning',
            'improving'     => 'badge badge--success',
            'stable'        => 'badge badge--neutral',
            default         => 'badge badge--neutral',
        };
    }
}
