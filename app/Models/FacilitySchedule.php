<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilitySchedule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'facility_id',
        'day_of_week',
        'opens_at',
        'closes_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
