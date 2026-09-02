<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TelemedicineNote — Module 18 (Telemedicine)
 *
 * Clinical note authored during or after a teleconsultation in SOAP format.
 *
 * CDSS Safety: Notes document clinical observations; the platform does NOT
 * auto-generate clinical content or diagnoses.
 */
class TelemedicineNote extends Model
{
    use HasUuids;

    protected $fillable = [
        'teleconsultation_id',
        'patient_id',
        'authored_by',
        'note_type',            // consultation|follow_up|prescription_note|referral_note
        'subjective',
        'objective',
        'assessment',
        'plan',
        'recommendations',
        'is_signed',
        'signed_at',
    ];

    protected $casts = [
        'is_signed' => 'boolean',
        'signed_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function teleconsultation(): BelongsTo
    {
        return $this->belongsTo(Teleconsultation::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function sign(string $signedBy): void
    {
        $this->update([
            'is_signed'  => true,
            'signed_at'  => now(),
            'authored_by' => $signedBy,
        ]);
    }

    public function isSigned(): bool
    {
        return $this->is_signed;
    }
}
