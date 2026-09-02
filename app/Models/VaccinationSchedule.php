<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaccinationSchedule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'vaccine_code',
        'vaccine_name',
        'dose_number',
        'dose_sequence',
        'due_date',
        'earliest_date',
        'latest_date',
        'status',
        'completed_by_immunization_id',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'earliest_date' => 'date',
        'latest_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function completedByImmunization()
    {
        return $this->belongsTo(ImmunizationRecord::class, 'completed_by_immunization_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'due' && $this->due_date !== null && $this->due_date->isPast();
    }
}
