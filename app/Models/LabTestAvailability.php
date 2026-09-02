<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LabTestAvailability extends Model
{
    use HasUuids;

    protected $table = 'lab_test_availability';

    protected $fillable = [
        'facility_id',
        'test_name',
        'local_test_code',
        'loinc_code',
        'specimen_type',
        'turnaround_time',
        'price',
        'currency',
        'requires_doctor_order',
        'sample_collection_available',
        'home_sample_collection_available',
        'availability_status',
        'freshness_status',
        'last_updated_at',
    ];

    protected $casts = [
        'price' => 'float',
        'requires_doctor_order' => 'boolean',
        'sample_collection_available' => 'boolean',
        'home_sample_collection_available' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }
}
