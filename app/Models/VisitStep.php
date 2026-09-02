<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * VisitStep — Module 41 (End-to-End Visit Flow)
 *
 * Represents a discrete clinical or administrative step within a patient visit
 * (check-in → triage → consultation → lab → pharmacy → billing → checkout).
 * Steps are ordered and track assignment, timing, and completion state.
 */
class VisitStep extends Model
{
    use HasUuids;

    /**
     * Canonical step names (used as step_name values).
     */
    public const STEPS = [
        'check_in',
        'triage',
        'consultation',
        'lab',
        'radiology',
        'pharmacy',
        'billing',
        'checkout',
    ];

    protected $fillable = [
        'visit_id',
        'step_name',
        'step_type',
        'status',
        'display_order',
        'assigned_to',
        'station_id',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(QueueStation::class, 'station_id');
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(VisitTimeline::class, 'visit_step_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function start(string $assignedTo): void
    {
        $this->update([
            'status'      => 'in_progress',
            'assigned_to' => $assignedTo,
            'started_at'  => $this->started_at ?? now(),
        ]);
    }

    public function complete(?string $notes = null): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'notes'        => $notes ?? $this->notes,
        ]);
    }

    public function skip(?string $reason = null): void
    {
        $this->update([
            'status' => 'skipped',
            'notes'  => $reason ?? $this->notes,
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'     => 'badge badge--neutral',
            'in_progress' => 'badge badge--info',
            'completed'   => 'badge badge--success',
            'skipped'     => 'badge badge--warning',
            default       => 'badge badge--neutral',
        };
    }

    public function durationMinutes(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        return (int) $this->started_at->diffInMinutes($this->completed_at);
    }
}
