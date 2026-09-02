<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PatientRightsRequest — Governance & Patient Rights Module
 *
 * Manages formal patient data rights requests under GDPR/NDPR and similar
 * privacy regulations. Covers export, deletion, correction, objection,
 * restriction of processing, and data portability rights.
 *
 * Security constraint: "Do not expose patient data publicly."
 * Response due date enforces the regulatory 30-day response window.
 */
class PatientRightsRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'request_type',         // data_export|deletion|correction|objection|restrict_processing|portability
        'reason',
        'status',               // pending|under_review|completed|rejected|partially_fulfilled
        'reviewed_by',
        'response_notes',
        'reviewed_at',
        'fulfilled_at',
        'response_due_date',
    ];

    protected $casts = [
        'reviewed_at'      => 'datetime',
        'fulfilled_at'     => 'datetime',
        'response_due_date' => 'date',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'partially_fulfilled']);
    }

    public function isOverdue(): bool
    {
        return $this->response_due_date !== null
            && $this->response_due_date->isPast()
            && ! $this->isCompleted();
    }

    public function complete(string $reviewedBy, ?string $notes = null): void
    {
        $this->update([
            'status'         => 'completed',
            'reviewed_by'    => $reviewedBy,
            'response_notes' => $notes,
            'reviewed_at'    => now(),
            'fulfilled_at'   => now(),
        ]);
    }

    public function reject(string $reviewedBy, string $notes): void
    {
        $this->update([
            'status'         => 'rejected',
            'reviewed_by'    => $reviewedBy,
            'response_notes' => $notes,
            'reviewed_at'    => now(),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'              => 'badge badge--neutral',
            'under_review'         => 'badge badge--info',
            'completed'            => 'badge badge--success',
            'rejected'             => 'badge badge--danger',
            'partially_fulfilled'  => 'badge badge--warning',
            default                => 'badge badge--neutral',
        };
    }
}
