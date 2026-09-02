<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InpatientMedicationAdministration — Ward Management / MAR
 *
 * Medication Administration Record (MAR) entry for inpatient care.
 * Tracks every scheduled and administered dose during an admission.
 *
 * CDSS Safety: this model supports medication administration workflows.
 * It does NOT replace clinical judgment. Nurses must verify the 5 Rights
 * (right patient, drug, dose, route, time) before administration.
 *
 * Security: MAR records must NEVER be silently altered after creation.
 * Corrections require a new record with an explanatory note.
 */
class InpatientMedicationAdministration extends Model
{
    use HasUuids;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'medicine_id',
        'medicine_name',
        'dose',
        'route',           // oral|IV|IM|SC|topical|etc
        'scheduled_at',
        'administered_at',
        'status',          // scheduled|administered|missed|held|refused
        'administered_by',
        'notes',
        'cdss_checked',
    ];

    protected $casts = [
        'scheduled_at'    => 'datetime',
        'administered_at' => 'datetime',
        'cdss_checked'    => 'boolean',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function administer(string $administeredBy, ?string $notes = null): void
    {
        $this->update([
            'status'          => 'administered',
            'administered_by' => $administeredBy,
            'administered_at' => now(),
            'notes'           => $notes ?? $this->notes,
        ]);
    }

    public function markMissed(?string $reason = null): void
    {
        $this->update(['status' => 'missed', 'notes' => $reason ?? $this->notes]);
    }

    public function hold(?string $reason = null): void
    {
        $this->update(['status' => 'held', 'notes' => $reason ?? $this->notes]);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at->isPast();
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'scheduled')->where('scheduled_at', '<', now());
    }

    public function scopeForAdmission($query, string $admissionId)
    {
        return $query->where('admission_id', $admissionId);
    }
}
