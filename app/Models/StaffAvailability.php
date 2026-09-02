<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffAvailability — Module 15 (Staff / HR / Shift Management)
 *
 * Weekly availability windows per staff member.
 * Used by the appointment booking system to check provider availability
 * before creating slots.
 */
class StaffAvailability extends Model
{
    use HasUuids;

    protected $fillable = [
        'staff_profile_id', 'facility_id', 'day_of_week',
        'start_time', 'end_time', 'is_on_call', 'is_available',
        'effective_from', 'effective_until', 'notes',
    ];

    protected $casts = [
        'is_on_call'    => 'boolean',
        'is_available'  => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function isCurrentlyEffective(): bool
    {
        $today = now()->toDateObject();
        if ($this->effective_from && $today->lt($this->effective_from)) return false;
        if ($this->effective_until && $today->gt($this->effective_until)) return false;
        return true;
    }
}
