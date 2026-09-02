<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DataAccessCommitteeReview — Module 17 (Research & Data Access Program)
 *
 * Records a Data Access Committee (DAC) member's review of a research
 * data access request. Multiple reviews may be required per request.
 *
 * Decisions: approved|rejected|deferred|more_info_needed
 */
class DataAccessCommitteeReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'research_access_request_id',
        'reviewer_id',
        'decision',              // approved|rejected|deferred|more_info_needed
        'comments',
        'conditions',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function researchAccessRequest(): BelongsTo
    {
        return $this->belongsTo(ResearchAccessRequest::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->decision === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->decision === 'rejected';
    }

    public function needsMoreInfo(): bool
    {
        return $this->decision === 'more_info_needed';
    }

    public function decisionBadgeClass(): string
    {
        return match($this->decision) {
            'approved'           => 'badge badge--success',
            'rejected'           => 'badge badge--danger',
            'deferred'           => 'badge badge--warning',
            'more_info_needed'   => 'badge badge--info',
            default              => 'badge badge--neutral',
        };
    }
}
