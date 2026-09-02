<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GoLiveApproval — Module 13 (Go-Live Readiness)
 *
 * Formal approval record for a facility to go live on OpesCare.
 * One approval per facility. Requires submitted checklist with no open blockers.
 *
 * Conditional approval is supported — conditions must be resolved within
 * the agreed timeline after go-live.
 */
class GoLiveApproval extends Model
{
    use HasUuids;

    protected $fillable = [
        'go_live_checklist_id',
        'facility_id',
        'status',               // pending|approved|rejected|conditional
        'approved_by',
        'approval_notes',
        'conditions',           // JSON: conditions for conditional approval
        'approved_at',
        'go_live_date',
    ];

    protected $casts = [
        'conditions'  => 'array',
        'approved_at' => 'datetime',
        'go_live_date' => 'date',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(GoLiveChecklist::class, 'go_live_checklist_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function approve(string $approvedBy, ?\DateTime $goLiveDate = null, ?string $notes = null): void
    {
        $this->update([
            'status'         => 'approved',
            'approved_by'    => $approvedBy,
            'approval_notes' => $notes,
            'approved_at'    => now(),
            'go_live_date'   => $goLiveDate,
        ]);

        // Mark checklist as approved
        $this->checklist?->update(['status' => 'approved']);
    }

    public function approveConditionally(
        string $approvedBy,
        array $conditions,
        ?\DateTime $goLiveDate = null
    ): void {
        $this->update([
            'status'       => 'conditional',
            'approved_by'  => $approvedBy,
            'conditions'   => $conditions,
            'approved_at'  => now(),
            'go_live_date' => $goLiveDate,
        ]);
    }

    public function reject(string $rejectedBy, string $notes): void
    {
        $this->update([
            'status'         => 'rejected',
            'approved_by'    => $rejectedBy,
            'approval_notes' => $notes,
            'approved_at'    => now(),
        ]);

        $this->checklist?->update(['status' => 'rejected']);
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'conditional']);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'     => 'badge badge--neutral',
            'approved'    => 'badge badge--success',
            'conditional' => 'badge badge--info',
            'rejected'    => 'badge badge--danger',
            default       => 'badge badge--neutral',
        };
    }
}
