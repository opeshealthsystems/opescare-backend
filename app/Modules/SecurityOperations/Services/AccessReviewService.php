<?php

namespace App\Modules\SecurityOperations\Services;

use App\Models\AccessReview;
use App\Models\AccessReviewSchedule;
use App\Models\PermissionAudit;
use App\Models\AuditEvent;

/**
 * AccessReviewService — Manages periodic access review workflows.
 *
 * High-risk permissions require periodic access review (§9.5 of Strategic Maturity doc).
 * This service schedules, initiates, and completes those reviews.
 *
 * Reviews trigger when:
 *  - AccessReviewSchedule.next_review_due is reached
 *  - A user's role changes
 *  - A security incident involves the user
 *  - A privacy officer manually initiates a review
 */
class AccessReviewService
{
    public function initiateReview(string $targetUserId, string $initiatedBy, string $reason): AccessReview
    {
        $review = AccessReview::create([
            'review_scope'          => 'user',
            'reviewed_subject_type' => 'user',
            'reviewed_subject_id'   => $targetUserId,
            'reviewed_by'           => $initiatedBy,
            'status'                => 'in_progress',
            'findings'              => $reason,
        ]);

        PermissionAudit::record($initiatedBy, $targetUserId, 'review', [
            'reason'    => $reason,
            'review_id' => $review->id,
        ]);

        AuditEvent::create([
            'actor_id'      => $initiatedBy,
            'action_type'   => 'create',
            'resource_type' => 'access_review',
            'resource_id'   => $review->id,
            'reason'        => 'Access review initiated for user ' . $targetUserId . '. Reason: ' . $reason,
        ]);

        return $review;
    }

    public function completeReview(
        string $reviewId,
        string $reviewerUserId,
        string $outcome, // no_change|modified|revoked|escalated
        ?string $notes = null
    ): AccessReview {
        $review = AccessReview::findOrFail($reviewId);
        $review->update([
            'status'       => 'completed',
            'outcome'      => $outcome,
            'reviewed_by'  => $reviewerUserId,
            'completed_at' => now(),
            'recommendations' => $notes,
        ]);

        PermissionAudit::record($reviewerUserId, $review->reviewed_subject_id, 'review', [
            'outcome'   => $outcome,
            'review_id' => $reviewId,
        ]);

        return $review->fresh();
    }

    /** Returns all access review schedules that are overdue. */
    public function getOverdueSchedules(): \Illuminate\Database\Eloquent\Collection
    {
        return AccessReviewSchedule::where('next_review_due', '<', now())
            ->where('status', 'pending')
            ->get();
    }

    /** Advances a schedule to completed and sets next review date. */
    public function completeSchedule(string $scheduleId, string $actorId, ?string $notes = null): AccessReviewSchedule
    {
        $schedule = AccessReviewSchedule::findOrFail($scheduleId);

        // Calculate next review date based on frequency
        $nextDue = match ($schedule->review_frequency) {
            'monthly'   => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'biannual'  => now()->addMonths(6),
            'annual'    => now()->addYear(),
            default     => now()->addMonths(3),
        };

        $schedule->update([
            'status'           => 'completed',
            'last_reviewed_at' => now()->toDateString(),
            'next_review_due'  => $nextDue->toDateString(),
            'notes'            => $notes,
        ]);

        return $schedule->fresh();
    }
}
