<?php

namespace App\Models;

use App\Enums\PreconsultStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One patient's pre-consult questionnaire for one appointment.
 *
 * `patient_id` and `facility_id` are copied off the appointment row by
 * App\Services\Preconsult\PreconsultQuestionnaireService and are never
 * assignable from a request: they are not in $fillable, and the service is the
 * only writer.
 *
 * Everything hanging off this record is PATIENT-REPORTED CONTEXT, never a
 * clinical finding — see config/preconsult.php and App\Enums\VitalProvenance.
 */
class PreconsultSubmission extends Model
{
    use HasUuids;

    protected $table = 'preconsult_submissions';

    /**
     * Deliberately narrow. patient_id and facility_id are absent because they
     * are derived server-side from the appointment; mass-assigning either is
     * exactly the bug the platform's facility_id rule exists to prevent.
     */
    protected $fillable = [
        'appointment_id',
        'definition_key',
        'definition_version',
        'status',
        'completed_by_user_id',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'status'             => PreconsultStatus::class,
        'definition_version' => 'integer',
        'started_at'         => 'datetime',
        'submitted_at'       => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PreconsultAnswer::class, 'submission_id');
    }
}
