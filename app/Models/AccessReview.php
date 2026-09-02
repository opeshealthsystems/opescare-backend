<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccessReview — Module 15 (Security, Audit & Compliance Operations)
 *
 * Formal periodic review of role/user access rights.
 * Access reviews ensure principle of least privilege is maintained.
 *
 * Security constraint: High-risk permissions must have access review schedules.
 */
class AccessReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'review_scope',           // facility|role|user|module
        'reviewed_subject_type',  // role|user|permission_group
        'reviewed_subject_id',
        'reviewed_by',
        'status',                 // pending|in_progress|completed|escalated
        'findings',
        'recommendations',
        'outcome',                // no_change|modified|revoked|escalated
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'due_date'     => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->isComplete();
    }

    public function complete(string $outcome, ?string $findings = null): void
    {
        $this->update([
            'status'       => 'completed',
            'outcome'      => $outcome,
            'findings'     => $findings ?? $this->findings,
            'completed_at' => now(),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'     => 'badge badge--neutral',
            'in_progress' => 'badge badge--info',
            'completed'   => 'badge badge--success',
            'escalated'   => 'badge badge--danger',
            default       => 'badge badge--neutral',
        };
    }
}
