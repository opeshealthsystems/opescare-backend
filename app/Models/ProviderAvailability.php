<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderAvailability extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'facility_id',
        'provider_id',
        'day_of_week',
        'starts_at',
        'ends_at',
        'slot_duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
