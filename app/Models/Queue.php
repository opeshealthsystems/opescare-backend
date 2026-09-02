<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Queue — Queue Management
 *
 * Represents a named service queue within a facility/department
 * (e.g. "General OPD Queue", "Emergency Queue", "Pharmacy Queue").
 * QueueTickets are issued against a Queue.
 */
class Queue extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'department_id',
        'name',
        'queue_type',       // general|emergency|vip|specialist
        'is_active',
        'current_serving',
        'waiting_count',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'current_serving' => 'integer',
        'waiting_count'   => 'integer',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function waitingTickets()
    {
        return $this->tickets()->where('status', 'waiting');
    }

    public function refreshWaitingCount(): void
    {
        $this->update(['waiting_count' => $this->waitingTickets()->count()]);
    }
}
