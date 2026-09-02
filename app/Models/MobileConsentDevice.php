<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MobileConsentDevice — Mobile Patient App / Consent Management
 *
 * Records a consent event captured via a mobile device.
 * Links to the underlying consent record and captures device/method context
 * for legal and audit purposes.
 */
class MobileConsentDevice extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'consent_id',          // FK to existing consent record
        'consent_type',        // treatment|data_sharing|telemedicine|research
        'device_identifier',
        'platform',            // ios|android|web
        'consent_method',      // tap|signature|voice_recorded|face_id
        'ip_address',
        'consented_at',
        'revoked_at',
        'is_valid',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'revoked_at'   => 'datetime',
        'is_valid'     => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null || !$this->is_valid;
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now(), 'is_valid' => false]);
    }

    public function scopeValid($query)
    {
        return $query->where('is_valid', true)->whereNull('revoked_at');
    }

    public function scopeForPatient($query, string $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
