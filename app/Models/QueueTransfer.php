<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QueueTransfer — Module 6 (Queue & Patient Flow)
 *
 * Records patient movement from one queue station to another.
 * Every inter-station transfer is logged here for flow analytics
 * and audit purposes.
 */
class QueueTransfer extends Model
{
    use HasUuids;

    protected $fillable = [
        'from_ticket_id',
        'to_ticket_id',
        'from_station_id',
        'to_station_id',
        'visit_id',
        'transferred_by',
        'reason',
        'reason_code',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function fromTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class, 'from_ticket_id');
    }

    public function toTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class, 'to_ticket_id');
    }

    public function fromStation(): BelongsTo
    {
        return $this->belongsTo(QueueStation::class, 'from_station_id');
    }

    public function toStation(): BelongsTo
    {
        return $this->belongsTo(QueueStation::class, 'to_station_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
