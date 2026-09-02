<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabPathReport extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'lab_path_reports';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'collected_date' => 'date',
        'reported_date'  => 'date',
        'critical_value' => 'boolean',
    ];
}
