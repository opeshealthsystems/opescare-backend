<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentWaitlist extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id', 'facility_id', 'provider_id',
        'appointment_type', 'preferred_earliest_date', 'preferred_latest_date',
        'urgency', 'status', 'notes', 'notified_at', 'booked_appointment_id',
    ];

    protected $casts = [
        'preferred_earliest_date' => 'date',
        'preferred_latest_date'   => 'date',
        'notified_at'             => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
