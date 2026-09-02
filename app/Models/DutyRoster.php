<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DutyRoster extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'department',
        'period_start', 'period_end',
        'status', 'created_by', 'published_at', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'published_at' => 'datetime',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RosterAssignment::class);
    }

    public function canBePublished(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeArchived(): bool
    {
        return in_array($this->status, ['draft', 'published']);
    }
}
