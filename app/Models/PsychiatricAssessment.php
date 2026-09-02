<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PsychiatricAssessment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'psychiatric_assessments';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'presenting_complaints' => 'array',
        'risk_factors'          => 'array',
        'medications_current'   => 'array',
        'assessment_date'       => 'date',
    ];
}
