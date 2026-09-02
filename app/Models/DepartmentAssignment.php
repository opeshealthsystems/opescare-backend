<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'staff_profile_id', 'department',
        'is_primary', 'assigned_from', 'assigned_until',
    ];

    protected $casts = [
        'is_primary'    => 'boolean',
        'assigned_from' => 'date',
        'assigned_until'=> 'date',
    ];

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function isCurrent(): bool
    {
        $now = now()->toDateString();
        $from = $this->assigned_from?->toDateString();
        $until = $this->assigned_until?->toDateString();

        if ($from && $from > $now) {
            return false;
        }
        if ($until && $until < $now) {
            return false;
        }
        return true;
    }
}
