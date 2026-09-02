<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportMapping extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'import_type',
        'name',
        'mapping',
        'created_by',
    ];

    protected $casts = [
        'mapping' => 'array',
    ];
}
