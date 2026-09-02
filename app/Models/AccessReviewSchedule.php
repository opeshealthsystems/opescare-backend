<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * AccessReviewSchedule — Periodic access review schedule for high-risk permissions.
 *
 * Tracks when each role/permission combination must be reviewed next,
 * enabling the privacy/security team to detect over-privileged accounts
 * before they cause a breach.
 *
 * Required per OPESCARE_STRATEGIC_MATURITY §9.5 — periodic access review.
 */
class AccessReviewSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'role_name', 'permission_key', 'facility_id', 'reviewer_user_id',
        'review_frequency', 'next_review_due', 'last_reviewed_at', 'status', 'notes',
    ];

    protected $casts = [
        'next_review_due'  => 'date',
        'last_reviewed_at' => 'date',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->next_review_due->isPast() && $this->status !== 'completed';
    }

    // ── Actions ────────────────────────────────────────────────────────────

    public function complete(string $notes = null): void
    {
        $interval = match ($this->review_frequency) {
            'monthly'   => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'biannual'  => now()->addMonths(6),
            'annual'    => now()->addYear(),
            default     => now()->addMonths(3),
        };

        $this->update([
            'status'           => 'completed',
            'last_reviewed_at' => today(),
            'next_review_due'  => $interval->toDateString(),
            'notes'            => $notes,
        ]);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeOverdue($query)
    {
        return $query->where('next_review_due', '<', today())->where('status', '!=', 'completed');
    }

    public function scopeDueSoon($query, int $days = 14)
    {
        return $query->whereBetween('next_review_due', [today(), today()->addDays($days)]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
