<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VisitClosure — Module 9 (End-to-End Patient Visit Flow)
 *
 * Records the formal closure of a patient visit including discharge
 * summary, instructions, and confirmation that all closure criteria
 * have been met.
 *
 * A visit cannot be closed if critical blockers remain unresolved.
 */
class VisitClosure extends Model
{
    use HasUuids;

    protected $fillable = [
        'visit_id',
        'closed_by',
        'closure_type',
        'discharge_summary',
        'follow_up_instructions',
        'billing_settled',
        'documents_complete',
        'prescriptions_dispensed',
        'patient_notified',
        'blockers_resolved',
        'closed_at',
    ];

    protected $casts = [
        'billing_settled'        => 'boolean',
        'documents_complete'     => 'boolean',
        'prescriptions_dispensed' => 'boolean',
        'patient_notified'       => 'boolean',
        'blockers_resolved'      => 'array',
        'closed_at'              => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isNormalDischarge(): bool
    {
        return $this->closure_type === 'normal';
    }

    public function hasAllRequirementsMet(): bool
    {
        return $this->billing_settled
            && $this->documents_complete;
    }
}
