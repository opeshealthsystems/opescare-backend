<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OfflineAuditEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'offline_queue_id',
        'local_cache_policy_id',
        'patient_id',
        'device_id',
        'event_type',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
}
