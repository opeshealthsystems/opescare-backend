<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'duty_roster_id', 'staff_profile_id', 'staff_shift_id',
        'work_date', 'status', 'assigned_by', 'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public function dutyRoster(): BelongsTo
    {
        return $this->belongsTo(DutyRoster::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function staffShift(): BelongsTo
    {
        return $this->belongsTo(StaffShift::class);
    }
}
