<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityQueue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'facility_id',
        'name',
        'display_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
