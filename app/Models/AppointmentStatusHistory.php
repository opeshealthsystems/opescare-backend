<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppointmentStatusHistory — Module 5 (Appointments & Booking)
 *
 * Immutable log of every status transition an appointment goes through.
 * Append-only — never update or delete entries.
 */
class AppointmentStatusHistory extends Model
{
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_by_type',
        'reason',
        'metadata',
        'changed_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'changed_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    public static function record(
        string $appointmentId,
        ?string $fromStatus,
        string $toStatus,
        ?string $changedBy = null,
        string $changedByType = 'user',
        ?string $reason = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'appointment_id'   => $appointmentId,
            'from_status'      => $fromStatus,
            'to_status'        => $toStatus,
            'changed_by'       => $changedBy,
            'changed_by_type'  => $changedByType,
            'reason'           => $reason,
            'metadata'         => $metadata,
            'changed_at'       => now(),
        ]);
    }
}
