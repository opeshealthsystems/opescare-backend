<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinorTransitionReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'date_of_birth', 'turns_18_on', 'status',
        'guardian_user_id', 'reviewed_by', 'notes',
        'notified_at', 'consented_at', 'completed_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'turns_18_on'   => 'date',
        'notified_at'   => 'datetime',
        'consented_at'  => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function guardianUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function daysUntil18(): int
    {
        return (int) now()->diffInDays($this->turns_18_on, false);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'     => 'warning',
            'notified'    => 'info',
            'consented'   => 'info',
            'transferred' => 'success',
            'declined'    => 'danger',
            default       => 'default',
        };
    }
}
