<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TriageReassessment — Module 16 (Triage & Emergency Workflow)
 *
 * Records a nurse's reassessment of a patient's condition and priority.
 * Each reassessment captures the reason for change and new vital signs.
 */
class TriageReassessment extends Model
{
    use HasUuids;

    protected $fillable = [
        'triage_assessment_id', 'visit_id', 'reassessed_by',
        'previous_priority', 'new_priority', 'reason_for_change',
        'new_vital_signs', 'reassessed_at',
    ];

    protected $casts = [
        'new_vital_signs' => 'array',
        'reassessed_at'   => 'datetime',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function isUpgrade(): bool
    {
        $levels = ['P5_non_urgent', 'P4_standard', 'P3_less_urgent', 'P2_urgent', 'P1_immediate'];
        return array_search($this->new_priority, $levels) > array_search($this->previous_priority, $levels);
    }
}
