<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * AdminActionReview — Master Admin / Security Operations
 *
 * Holds high-risk admin actions for mandatory review before or after
 * execution (e.g. platform setting changes, module toggles, user impersonation).
 */
class AdminActionReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'admin_action_log_id',
        'action_type',    // setting_change|feature_flag|module_toggle|user_impersonation
        'status',         // pending|approved|rejected
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function approve(string $reviewedBy, ?string $notes = null): void
    {
        $this->update(['status' => 'approved', 'reviewed_by' => $reviewedBy, 'reviewed_at' => now(), 'review_notes' => $notes]);
    }

    public function reject(string $reviewedBy, string $notes): void
    {
        $this->update(['status' => 'rejected', 'reviewed_by' => $reviewedBy, 'reviewed_at' => now(), 'review_notes' => $notes]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
