<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientSurvey extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'visit_id',
        'template_key',
        'status',
        'sent_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'completed_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function responses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SurveyResponse::class, 'patient_survey_id');
    }
}
