<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppointmentCancellation — Module 5 (Appointments & Booking)
 *
 * Structured record of an appointment cancellation with actor,
 * reason, and financial consequence tracking.
 * Separate from the appointment status itself for full audit trail.
 */
class AppointmentCancellation extends Model
{
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'cancelled_by',
        'cancelled_by_type',
        'reason_code',
        'reason_notes',
        'slot_released',
        'refund_applicable',
        'refund_id',
        'cancelled_at',
    ];

    protected $casts = [
        'slot_released'    => 'boolean',
        'refund_applicable' => 'boolean',
        'cancelled_at'     => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function wasPatientInitiated(): bool
    {
        return $this->cancelled_by_type === 'patient';
    }

    public function wasStaffInitiated(): bool
    {
        return $this->cancelled_by_type === 'staff';
    }
}
