<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientFlowEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'queue_ticket_id',
        'patient_id',
        'facility_id',
        'visit_id',
        'actor_id',
        'event_type',
        'from_queue',
        'to_queue',
        'from_status',
        'to_status',
        'reason',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];
}
