<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OccupationalHealthAssessment extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'examiner_id',
        'assessment_date',
        'assessment_type',
        'job_title',
        'employer',
        'exposure_history',
        'clinical_findings',
        'fitness_conclusion',
        'restrictions',
        'next_review_date',
    ];

    protected $casts = [
        'assessment_date'  => 'date',
        'next_review_date' => 'date',
        'exposure_history' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }
}
