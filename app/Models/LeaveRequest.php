<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'staff_profile_id', 'leave_type',
        'start_date', 'end_date', 'days_requested',
        'reason', 'status',
        'reviewed_by', 'review_notes', 'reviewed_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeReviewed(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeWithdrawn(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }
}
