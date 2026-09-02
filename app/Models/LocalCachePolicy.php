<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocalCachePolicy extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'device_id',
        'allowed_scopes',
        'encryption_required',
        'encryption_policy',
        'emergency_access',
        'review_required',
        'status',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'allowed_scopes' => 'array',
        'encryption_required' => 'boolean',
        'emergency_access' => 'boolean',
        'review_required' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
