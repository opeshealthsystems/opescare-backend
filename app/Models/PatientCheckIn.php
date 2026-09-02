<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientCheckIn extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'visit_id',
        'appointment_id',
        'checked_in_by_id',
        'check_in_type',
        'checked_in_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];
}
