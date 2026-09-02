<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * QueueStation — Module 19 (Queue & Patient Flow)
 *
 * Represents a physical or logical service point within a facility
 * (e.g. Triage Bay 1, Consultation Room 3, Lab Counter).
 * Queue tickets are routed to stations for patient care steps.
 */
class QueueStation extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'queue_id',
        'name',
        'station_type',
        'status',
        'current_operator',
        'display_order',
        'is_priority_station',
    ];

    protected $casts = [
        'is_priority_station' => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(FacilityQueue::class, 'queue_id');
    }

    public function visitSteps(): HasMany
    {
        return $this->hasMany(VisitStep::class, 'station_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBusy(): bool
    {
        return $this->status === 'busy';
    }

    public function setOperator(string $operatorId): void
    {
        $this->update([
            'current_operator' => $operatorId,
            'status'           => 'busy',
        ]);
    }

    public function clearOperator(): void
    {
        $this->update([
            'current_operator' => null,
            'status'           => 'active',
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'active'   => 'badge badge--success',
            'busy'     => 'badge badge--warning',
            'inactive' => 'badge badge--neutral',
            default    => 'badge badge--neutral',
        };
    }
}
