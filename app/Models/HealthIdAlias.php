<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthIdAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'alias_type',
        'alias_value',
        'source_facility_id',
        'status',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
