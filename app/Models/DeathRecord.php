<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeathRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'certifying_doctor_id',
        'deceased_at',
        'place_of_death',
        'manner_of_death',
        'primary_cause',
        'secondary_causes',
        'duration_primary',
        'contributing_conditions',
        'was_autopsy_performed',
        'autopsy_report_id',
        'registrar_id',
        'registered_at',
        'official_document_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'deceased_at'          => 'datetime',
        'registered_at'        => 'datetime',
        'secondary_causes'     => 'array',
        'was_autopsy_performed' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function certifyingDoctor()
    {
        return $this->belongsTo(User::class, 'certifying_doctor_id');
    }
}
