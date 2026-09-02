<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Encounter — Module 9 (End-to-End Patient Visit Flow) + FHIR Interoperability
 *
 * Represents a clinical encounter — a single patient-provider interaction.
 * Maps directly to FHIR R4 Encounter resource.
 *
 * One Visit may produce one or more Encounters (e.g., triage, consultation, procedure).
 */
class Encounter extends Model
{
    use HasUuids;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'facility_id',
        'encounter_type',       // outpatient|inpatient|emergency|telemedicine|referral
        'status',               // active|completed|cancelled
        'encounter_class',      // AMB|IMP|EMER|VR (FHIR class codes)
        'attending_provider_id',
        'admission_id',
        'reason_for_encounter',
        'discharge_disposition',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function durationMinutes(): ?int
    {
        if (! $this->started_at || ! $this->ended_at) {
            return null;
        }
        return (int) $this->started_at->diffInMinutes($this->ended_at);
    }

    /** FHIR class code for FHIR R4 Encounter.class */
    public function fhirClass(): string
    {
        return match($this->encounter_class) {
            'AMB'  => 'ambulatory',
            'IMP'  => 'inpatient encounter',
            'EMER' => 'emergency',
            'VR'   => 'virtual',
            default => $this->encounter_class ?? 'ambulatory',
        };
    }
}
