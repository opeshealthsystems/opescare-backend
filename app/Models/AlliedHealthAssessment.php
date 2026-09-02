<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlliedHealthAssessment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'allied_health_assessments';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'assessment_date' => 'date',
    ];
}
