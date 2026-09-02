<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpecialCareRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'special_care_records';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'record_date'  => 'date',
        'vitals'       => 'array',
        'medications'  => 'array',
        'observations' => 'array',
    ];
}
