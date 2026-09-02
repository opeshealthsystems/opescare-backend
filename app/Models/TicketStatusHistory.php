<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TicketStatusHistory — Module 10 (Support, Helpdesk & Incident Management)
 *
 * Immutable status transition log for support tickets. Append-only.
 */
class TicketStatusHistory extends Model
{
    use HasUuids;

    protected $fillable = [
        'support_ticket_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public static function record(
        string $ticketId,
        ?string $fromStatus,
        string $toStatus,
        ?string $changedBy = null,
        ?string $reason = null
    ): self {
        return static::create([
            'support_ticket_id' => $ticketId,
            'from_status'       => $fromStatus,
            'to_status'         => $toStatus,
            'changed_by'        => $changedBy,
            'reason'            => $reason,
            'changed_at'        => now(),
        ]);
    }
}
