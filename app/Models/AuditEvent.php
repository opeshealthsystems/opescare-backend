<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'actor_role',
        'facility_id',
        'patient_id',
        'encounter_id',
        'action_type',
        'resource_type',
        'resource_id',
        'consent_grant_id',
        'emergency_override',
        'source_system',
        'device_id',
        'ip_address',
        'reason',
        'before_state',
        'after_state',
        'created_at',
    ];

    protected $casts = [
        'emergency_override' => 'boolean',
        'before_state' => 'array',
        'after_state' => 'array',
        'created_at' => 'datetime',
    ];
}
