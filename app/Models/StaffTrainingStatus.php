<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffTrainingStatus — Module 15 (Staff / HR / Shift Management)
 *
 * Tracks required training and certification completion per staff member.
 * Links to external OPES Academy certifications where applicable.
 * Missing required training can block go-live or permissions.
 */
class StaffTrainingStatus extends Model
{
    use HasUuids;

    protected $fillable = [
        'staff_profile_id', 'training_type', 'training_name',
        'training_reference', 'status',
        'started_at', 'completed_at', 'expiry_date',
        'completed_by_source',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'expiry_date'  => 'date',
    ];

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function markCompleted(string $source = 'external'): void
    {
        $this->update([
            'status'               => 'completed',
            'completed_at'         => now(),
            'completed_by_source'  => $source,
        ]);
    }

    public function statusBadgeClass(): string
    {
        if ($this->isExpired()) return 'badge badge--danger';
        return match($this->status) {
            'completed'   => 'badge badge--success',
            'in_progress' => 'badge badge--info',
            'pending'     => 'badge badge--neutral',
            'expired'     => 'badge badge--danger',
            default       => 'badge badge--neutral',
        };
    }
}
