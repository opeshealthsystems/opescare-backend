<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MobileAppSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'push_appointments',
        'push_lab_results',
        'push_prescriptions',
        'push_billing',
        'push_consent_requests',
        'preferred_language',
        'preferred_theme',
        'biometric_login_enabled',
        'extra_preferences',
    ];

    protected $casts = [
        'push_appointments'    => 'boolean',
        'push_lab_results'     => 'boolean',
        'push_prescriptions'   => 'boolean',
        'push_billing'         => 'boolean',
        'push_consent_requests'=> 'boolean',
        'biometric_login_enabled' => 'boolean',
        'extra_preferences'    => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get or create settings for a patient.
     *
     * The column defaults live in the migration, not on the model, so a row
     * created here comes back holding only patient_id/id/timestamps — every
     * preference reads as null until the row is re-read. That made the very
     * first GET /mobile/settings of a new account return all-null, which the
     * app renders as "every notification is off" even though the stored
     * defaults are on. Re-read after inserting so callers always see the
     * values that are actually persisted.
     */
    public static function forPatient(string $patientId): self
    {
        $settings = self::firstOrCreate(['patient_id' => $patientId]);

        return $settings->wasRecentlyCreated ? $settings->refresh() : $settings;
    }
}
