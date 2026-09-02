<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PublicationReview — Module 17 (Research & Data Access Program)
 *
 * Tracks publication submissions arising from approved research access.
 * OpesCare reviews publications before they are publicly released to
 * ensure data use complied with the agreement terms.
 */
class PublicationReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'research_access_request_id',
        'publication_title',
        'target_journal',
        'status',               // submitted|under_review|approved|rejected|published
        'review_comments',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
        'doi',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function researchAccessRequest(): BelongsTo
    {
        return $this->belongsTo(ResearchAccessRequest::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function approve(string $reviewedBy, ?string $comments = null): void
    {
        $this->update([
            'status'          => 'approved',
            'reviewed_by'     => $reviewedBy,
            'review_comments' => $comments,
            'reviewed_at'     => now(),
        ]);
    }

    public function reject(string $reviewedBy, string $comments): void
    {
        $this->update([
            'status'          => 'rejected',
            'reviewed_by'     => $reviewedBy,
            'review_comments' => $comments,
            'reviewed_at'     => now(),
        ]);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'submitted'    => 'badge badge--neutral',
            'under_review' => 'badge badge--info',
            'approved'     => 'badge badge--success',
            'rejected'     => 'badge badge--danger',
            'published'    => 'badge badge--success',
            default        => 'badge badge--neutral',
        };
    }
}
