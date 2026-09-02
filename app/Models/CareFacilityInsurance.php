<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CareFacilityInsurance extends Model
{
    use HasUuids;

    protected $table = 'care_facility_insurance';

    protected $fillable = [
        'facility_id',
        'insurance_partner_id',
        'insurance_name',
        'plan_name',
        'coverage_type',
        'preauthorization_required',
        'cashless_available',
        'claim_supported',
        'last_verified_at',
        'status',
    ];

    protected $casts = [
        'preauthorization_required' => 'boolean',
        'cashless_available' => 'boolean',
        'claim_supported' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }
}
