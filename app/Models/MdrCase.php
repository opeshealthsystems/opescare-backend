<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MdrCase extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'registered_at',
        'diagnosis_basis',
        'drug_resistance_profile',
        'treatment_regimen',
        'treatment_start_date',
        'treatment_end_date',
        'treatment_outcome',
        'supervising_doctor_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'registered_at'          => 'date',
        'treatment_start_date'   => 'date',
        'treatment_end_date'     => 'date',
        'drug_resistance_profile' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function supervisingDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervising_doctor_id');
    }
}
