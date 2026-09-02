<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SurveyTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'title', 'trigger', 'questions', 'is_active',
    ];

    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
    ];

    public function facility()  { return $this->belongsTo(Facility::class); }
    public function responses() { return $this->hasMany(SurveyTemplateResponse::class); }
}
