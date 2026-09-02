<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * GoLiveChecklist — Module 13 (Go-Live Readiness)
 *
 * Granular per-facility go-live readiness checklist.
 * All required items must be completed and blockers resolved
 * before GoLiveApproval can be granted.
 */
class GoLiveChecklist extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'checklist_version',
        'status',               // draft|in_progress|submitted|approved|rejected
        'total_items',
        'completed_items',
        'blocker_count',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'total_items'     => 'integer',
        'completed_items' => 'integer',
        'blocker_count'   => 'integer',
        'submitted_at'    => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoLiveChecklistItem::class);
    }

    public function blockers(): HasMany
    {
        return $this->hasMany(GoLiveBlocker::class);
    }

    public function approval(): HasOne
    {
        return $this->hasOne(GoLiveApproval::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function completionPercent(): int
    {
        if ($this->total_items === 0) {
            return 0;
        }
        return (int) round(($this->completed_items / $this->total_items) * 100);
    }

    public function hasOpenBlockers(): bool
    {
        return $this->blockers()->whereIn('status', ['open', 'in_progress'])->exists();
    }

    public function isReadyForSubmission(): bool
    {
        return $this->completionPercent() === 100 && ! $this->hasOpenBlockers();
    }

    public function submit(string $submittedBy): void
    {
        if (! $this->isReadyForSubmission()) {
            throw new \RuntimeException('Checklist is not ready for submission — incomplete items or open blockers.');
        }
        $this->update([
            'status'       => 'submitted',
            'submitted_by' => $submittedBy,
            'submitted_at' => now(),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'draft'       => 'badge badge--neutral',
            'in_progress' => 'badge badge--info',
            'submitted'   => 'badge badge--warning',
            'approved'    => 'badge badge--success',
            'rejected'    => 'badge badge--danger',
            default       => 'badge badge--neutral',
        };
    }
}
