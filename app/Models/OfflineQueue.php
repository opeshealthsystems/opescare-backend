<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflineQueue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'local_cache_policy_id',
        'patient_id',
        'facility_id',
        'device_id',
        'scopes',
        'encrypted_payload',
        'payload_hash',
        'status',
        'retry_count',
        'last_error',
        'next_retry_at',
        'synced_at',
        'created_by',
    ];

    protected $casts = [
        'scopes' => 'array',
        'next_retry_at' => 'datetime',
        'synced_at' => 'datetime',
    ];
}
