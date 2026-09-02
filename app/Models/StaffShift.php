<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffShift extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'name', 'department',
        'start_time', 'end_time', 'crosses_midnight',
        'duration_hours', 'status',
    ];

    protected $casts = [
        'crosses_midnight' => 'boolean',
        'duration_hours'   => 'integer',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function rosterAssignments(): HasMany
    {
        return $this->hasMany(RosterAssignment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
