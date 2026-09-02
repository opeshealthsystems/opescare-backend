<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RecordCorrectionDecision — Governance & Patient Rights Module
 *
 * Records the formal decision on a patient's record correction request.
 * Tracks exact approved and rejected field changes.
 *
 * After a decision is made, the patient must be notified (patient_notified flag).
 */
class RecordCorrectionDecision extends Model
{
    use HasUuids;

    protected $fillable = [
        'correction_request_id',
        'decided_by',
        'decision',              // approved|rejected|partial
        'decision_reason',
        'approved_changes',      // JSON: exact field changes approved
        'rejected_changes',      // JSON: fields rejected and why
        'decided_at',
        'patient_notified',
        'patient_notified_at',
    ];

    protected $casts = [
        'approved_changes'    => 'array',
        'rejected_changes'    => 'array',
        'decided_at'          => 'datetime',
        'patient_notified'    => 'boolean',
        'patient_notified_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return in_array($this->decision, ['approved', 'partial']);
    }

    public function isFullyApproved(): bool
    {
        return $this->decision === 'approved';
    }

    public function markPatientNotified(): void
    {
        $this->update([
            'patient_notified'    => true,
            'patient_notified_at' => now(),
        ]);
    }
}
