<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TelemedicineConsent — Module 18 (Telemedicine)
 *
 * Records informed consent for a telemedicine session. Must be
 * obtained before the consultation begins.
 *
 * Consent is patient-controlled and can be revoked prior to the session.
 */
class TelemedicineConsent extends Model
{
    use HasUuids;

    protected $fillable = [
        'teleconsultation_id',
        'patient_id',
        'consented',
        'consent_method',       // verbal|digital|written
        'consent_text_version',
        'witnessed_by',
        'consented_at',
        'revoked_at',
    ];

    protected $casts = [
        'consented'    => 'boolean',
        'consented_at' => 'datetime',
        'revoked_at'   => 'datetime',
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

    public function isValid(): bool
    {
        return $this->consented && $this->revoked_at === null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }
}
