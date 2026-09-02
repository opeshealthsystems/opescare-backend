<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TriageScore — Module 16 (Triage & Emergency Workflow)
 *
 * Computed or manually assigned triage priority score.
 *
 * CDSS Safety rule: Triage scores assist clinical operations but do NOT
 * replace clinical judgment. The system displays scores as support
 * information; the nurse/clinician confirms the final priority.
 */
class TriageScore extends Model
{
    use HasUuids;

    protected $fillable = [
        'triage_assessment_id', 'visit_id',
        'scoring_system', 'priority_level', 'numeric_score',
        'component_scores', 'computed_by', 'scored_at',
    ];

    protected $casts = [
        'component_scores' => 'array',
        'scored_at'        => 'datetime',
    ];

    public function triageAssessment(): BelongsTo
    {
        return $this->belongsTo(TriageRecord::class, 'triage_assessment_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function priorityBadgeClass(): string
    {
        return match($this->priority_level) {
            'P1_immediate'   => 'badge badge--danger',
            'P2_urgent'      => 'badge badge--warning',
            'P3_less_urgent' => 'badge badge--info',
            'P4_standard'    => 'badge badge--neutral',
            'P5_non_urgent'  => 'badge badge--neutral',
            default          => 'badge badge--neutral',
        };
    }

    public function priorityLabel(): string
    {
        return match($this->priority_level) {
            'P1_immediate'   => 'P1 – Immediate',
            'P2_urgent'      => 'P2 – Urgent',
            'P3_less_urgent' => 'P3 – Less Urgent',
            'P4_standard'    => 'P4 – Standard',
            'P5_non_urgent'  => 'P5 – Non-Urgent',
            default          => $this->priority_level,
        };
    }
}
