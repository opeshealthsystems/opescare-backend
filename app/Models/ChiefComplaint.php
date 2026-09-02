<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ChiefComplaint — Module 16 (Triage & Emergency Workflow)
 *
 * The patient's primary presenting complaint as recorded by triage staff.
 * Linked to the triage assessment, visit, and patient.
 */
class ChiefComplaint extends Model
{
    use HasUuids;

    protected $fillable = [
        'triage_assessment_id', 'patient_id', 'visit_id',
        'complaint_text', 'complaint_category', 'pain_score',
        'duration_hours', 'associated_symptoms', 'recorded_by', 'recorded_at',
    ];

    protected $casts = [
        'associated_symptoms' => 'array',
        'recorded_at'         => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
