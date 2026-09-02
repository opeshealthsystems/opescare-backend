<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientExternalIdentifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'source_system',
        'external_patient_id',
        'identifier_type',
        'status',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
