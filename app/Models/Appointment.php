<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'provider_id',
        'appointment_slot_id',
        'visit_id',
        'rescheduled_from_appointment_id',
        'appointment_type',
        'status',
        'scheduled_at',
        'booked_by_type',
        'booked_by_id',
        'reason',
        'cancellation_reason',
        'cancelled_by_id',
        'cancelled_at',
        'checked_in_at',
        'no_show_at',
        'billing_deferred',
        'telemedicine_deferred',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'no_show_at' => 'datetime',
        'billing_deferred' => 'boolean',
        'telemedicine_deferred' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function slot()
    {
        return $this->belongsTo(AppointmentSlot::class, 'appointment_slot_id');
    }
}
