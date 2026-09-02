<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GoLiveBlocker — Module 13 (Go-Live Readiness)
 *
 * Records a blocking issue that prevents a facility from going live.
 * All blockers must be resolved or waived before the checklist
 * can be submitted for approval.
 */
class GoLiveBlocker extends Model
{
    use HasUuids;

    protected $fillable = [
        'go_live_checklist_id',
        'blocker_type',         // critical|major|minor
        'description',
        'resolution_required',
        'status',               // open|in_progress|resolved|waived
        'assigned_to',
        'resolved_by',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(GoLiveChecklist::class, 'go_live_checklist_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function resolve(string $resolvedBy, string $notes): void
    {
        $this->update([
            'status'           => 'resolved',
            'resolved_by'      => $resolvedBy,
            'resolution_notes' => $notes,
            'resolved_at'      => now(),
        ]);
    }

    public function waive(string $waivedBy, string $reason): void
    {
        $this->update([
            'status'           => 'waived',
            'resolved_by'      => $waivedBy,
            'resolution_notes' => $reason,
            'resolved_at'      => now(),
        ]);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'waived']);
    }

    public function isCritical(): bool
    {
        return $this->blocker_type === 'critical';
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'open'        => 'badge badge--danger',
            'in_progress' => 'badge badge--warning',
            'resolved'    => 'badge badge--success',
            'waived'      => 'badge badge--info',
            default       => 'badge badge--neutral',
        };
    }
}
