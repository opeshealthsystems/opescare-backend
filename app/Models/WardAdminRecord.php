<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WardAdminRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'ward_admin_records';

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'record_date' => 'date',
        'content'     => 'array',
    ];
}
