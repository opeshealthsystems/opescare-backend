<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerioperativeRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'perioperative_records';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'procedure_datetime' => 'datetime',
        'checklist_data'     => 'array',
        'complications'      => 'boolean',
    ];
}
