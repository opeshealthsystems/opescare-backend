<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CareFacilityService extends Model
{
    use HasUuids;

    protected $table = 'care_facility_services';

    protected $fillable = [
        'facility_id',
        'service_name',
        'service_category',
        'specialty',
        'service_code',
        'availability_status',
        'appointment_required',
        'walk_in_allowed',
        'telemedicine_available',
        'price_range',
        'last_updated_at',
        'source',
        'catalogue_item_id',
    ];

    /** Typed in on the old settings / listing forms, or imported. */
    public const SOURCE_MANUAL = 'manual';

    /** Derived from the facility service catalogue by PublicServicesSync. */
    public const SOURCE_CATALOGUE = 'catalogue';

    protected $casts = [
        'appointment_required' => 'boolean',
        'walk_in_allowed' => 'boolean',
        'telemedicine_available' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }
}
