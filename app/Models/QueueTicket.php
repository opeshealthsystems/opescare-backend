<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueTicket extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'visit_id',
        'appointment_id',
        'patient_check_in_id',
        'assigned_to_id',
        'queue_number',
        'current_queue',
        'status',
        'priority_level',
        'priority_reason',
        'status_reason',
        'checked_in_at',
        'called_at',
        'service_started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'called_at' => 'datetime',
        'service_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /** Append-only, oldest first: the ticket's life as it was recorded. */
    public function histories()
    {
        return $this->hasMany(QueueStatusHistory::class)->orderBy('changed_at')->orderBy('created_at');
    }
}
