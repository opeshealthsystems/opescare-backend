<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GuardianRelationship — Governance & Patient Rights Module
 *
 * Links a guardian or legal representative to a minor patient.
 * Controls medical consent authority and data access scope.
 *
 * Security constraints:
 * - "Do not allow students to perform restricted clinical actions."
 * - Guardians only have data_access if explicitly granted.
 * - Guardian authority expires when patient reaches legal age.
 */
class GuardianRelationship extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'guardian_user_id',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'relationship_type',        // parent|legal_guardian|court_appointed|emergency_contact
        'has_medical_consent',
        'has_data_access',
        'legal_document_reference',
        'effective_from',
        'effective_until',
        'is_primary',
        'is_active',
        'recorded_by',
    ];

    protected $casts = [
        'has_medical_consent' => 'boolean',
        'has_data_access'     => 'boolean',
        'effective_from'      => 'date',
        'effective_until'     => 'date',
        'is_primary'          => 'boolean',
        'is_active'           => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyEffective($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now()->toDateString());
            });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isCurrentlyEffective(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $now = now()->toDateString();
        if ($this->effective_from && $this->effective_from->toDateString() > $now) {
            return false;
        }
        if ($this->effective_until && $this->effective_until->toDateString() < $now) {
            return false;
        }
        return true;
    }

    public function canAccessData(): bool
    {
        return $this->has_data_access && $this->isCurrentlyEffective();
    }

    public function canGiveMedicalConsent(): bool
    {
        return $this->has_medical_consent && $this->isCurrentlyEffective();
    }
}
