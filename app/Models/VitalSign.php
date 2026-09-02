<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitalSign extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'triage_record_id',
        'temperature',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse',
        'respiratory_rate',
        'oxygen_saturation',
        'weight',
        'height',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'oxygen_saturation' => 'decimal:2',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public function triageRecord()
    {
        return $this->belongsTo(TriageRecord::class);
    }
}
