<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalReviewRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'clinical_review_records';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'review_date'    => 'date',
        'findings'       => 'array',
        'recommendations' => 'array',
    ];
}
