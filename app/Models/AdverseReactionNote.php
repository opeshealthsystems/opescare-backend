<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdverseReactionNote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'immunization_record_id',
        'patient_id',
        'reported_by_id',
        'severity',
        'description',
        'onset_timing',
        'onset_at',
        'action_taken',
        'outcome',
        'reported_to_authority',
        'authority_report_reference',
    ];

    protected $casts = [
        'onset_at' => 'datetime',
        'reported_to_authority' => 'boolean',
    ];

    public function immunizationRecord()
    {
        return $this->belongsTo(ImmunizationRecord::class, 'immunization_record_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }
}
