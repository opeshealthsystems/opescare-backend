<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QueueStatusHistory — Module 6 (Queue & Patient Flow)
 *
 * Immutable log of every status transition a queue ticket goes through.
 * Append-only — never update or delete entries.
 * Used for patient flow analytics and audit.
 */
class QueueStatusHistory extends Model
{
    use HasUuids;

    protected $fillable = [
        'queue_ticket_id',
        'from_status',
        'to_status',
        'station_id',
        'changed_by',
        'reason',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function queueTicket(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(QueueStation::class, 'station_id');
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    public static function record(
        string $queueTicketId,
        ?string $fromStatus,
        string $toStatus,
        ?string $stationId = null,
        ?string $changedBy = null,
        ?string $reason = null
    ): self {
        return static::create([
            'queue_ticket_id' => $queueTicketId,
            'from_status'     => $fromStatus,
            'to_status'       => $toStatus,
            'station_id'      => $stationId,
            'changed_by'      => $changedBy,
            'reason'          => $reason,
            'changed_at'      => now(),
        ]);
    }
}
