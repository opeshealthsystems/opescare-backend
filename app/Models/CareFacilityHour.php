<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CareFacilityHour extends Model
{
    use HasUuids;

    protected $table = 'care_facility_hours';

    protected $fillable = [
        'facility_id',
        'day_of_week',
        'opens_at',
        'closes_at',
        'is_closed',
        'is_24_hours',
        'service_context',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_closed' => 'boolean',
        'is_24_hours' => 'boolean',
    ];

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }
}
