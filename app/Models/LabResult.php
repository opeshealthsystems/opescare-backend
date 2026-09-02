<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'lab_order_id',
        'patient_id',
        'parameter_name',
        'value',
        'unit',
        'reference_range',
        'flag',
        'notes',
        'verified_by',
        'resulted_at',
        'loinc_code',
        'loinc_display',
    ];

    protected $casts = [
        'resulted_at' => 'datetime',
    ];

    public function labOrder()
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function isAbnormal(): bool
    {
        return in_array($this->flag, ['H', 'L', 'HH', 'LL', 'abnormal'], true);
    }

    public function flagLabel(): string
    {
        return match ($this->flag) {
            'H'        => 'High',
            'HH'       => 'Critical High',
            'L'        => 'Low',
            'LL'       => 'Critical Low',
            'abnormal' => 'Abnormal',
            default    => 'Normal',
        };
    }
}
