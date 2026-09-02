<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PediatricRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'pediatric_records';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'record_date'        => 'date',
        'milestones'         => 'array',
        'immunisations_given' => 'array',
        'growth_data'        => 'array',
    ];
}
