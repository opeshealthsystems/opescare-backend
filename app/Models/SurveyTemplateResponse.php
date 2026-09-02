<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SurveyTemplateResponse extends Model
{
    use HasUuids;

    protected $fillable = [
        'survey_template_id', 'patient_id', 'facility_id',
        'answers', 'overall_score', 'submitted_at',
    ];

    protected $casts = [
        'answers'      => 'array',
        'submitted_at' => 'datetime',
    ];

    public function template() { return $this->belongsTo(SurveyTemplate::class, 'survey_template_id'); }
    public function patient()  { return $this->belongsTo(Patient::class); }
    public function facility() { return $this->belongsTo(Facility::class); }
}
