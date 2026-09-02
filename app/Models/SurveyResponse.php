<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_survey_id',
        'question_key',
        'question_text',
        'response_type',
        'numeric_response',
        'text_response',
    ];

    public function survey(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PatientSurvey::class, 'patient_survey_id');
    }
}
