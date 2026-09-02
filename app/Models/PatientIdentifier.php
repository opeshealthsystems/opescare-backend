<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientIdentifier extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'identifier_type',
        'identifier_value',
        'issuer',
        'facility_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
