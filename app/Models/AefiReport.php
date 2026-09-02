<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AefiReport extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'immunization_record_id',
        'reporter_id',
        'report_date',
        'onset_date',
        'severity',
        'event_description',
        'vaccine_name',
        'vaccine_lot',
        'batch_number',
        'causality_assessment',
        'outcome',
        'action_taken',
        'reported_to_authorities',
    ];

    protected $casts = [
        'report_date'              => 'date',
        'onset_date'               => 'date',
        'reported_to_authorities'  => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
