<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppointmentCheckIn — Module 18 (Appointments & Booking)
 *
 * Records when a patient physically arrives for their appointment.
 * Separate from QueueTicket which tracks station-level patient flow.
 */
class AppointmentCheckIn extends Model
{
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'facility_id',
        'checked_in_by',
        'check_in_mode',
        'status',
        'checked_in_at',
        'cancelled_at',
        'notes',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in';
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'notes'        => $reason ?? $this->notes,
        ]);
    }
}
