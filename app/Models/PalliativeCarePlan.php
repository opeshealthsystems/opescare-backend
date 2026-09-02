<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PalliativeCarePlan extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'lead_clinician_id',
        'diagnosis',
        'prognosis',
        'goals_of_care',
        'pain_management_plan',
        'symptom_management',
        'psychological_support',
        'spiritual_support',
        'family_support',
        'dnr_status',
        'advance_directive_id',
        'status',
    ];

    protected $casts = [
        'dnr_status'         => 'boolean',
        'symptom_management' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function leadClinician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_clinician_id');
    }
}
