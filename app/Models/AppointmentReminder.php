<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppointmentReminder — Module 5 (Appointments & Booking)
 *
 * Tracks scheduled and delivered reminders for appointments.
 * Created by the AppointmentReminderJob; delivery status is updated
 * by the notification system.
 */
class AppointmentReminder extends Model
{
    use HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'appointment_id',
        'channel',
        'status',
        'remind_before_hours',
        'scheduled_at',
        'sent_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function markSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $error,
            'retry_count'   => $this->retry_count + 1,
        ]);
    }
}
