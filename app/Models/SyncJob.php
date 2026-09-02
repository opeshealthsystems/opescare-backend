<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncJob extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'offline_queue_id',
        'status',
        'attempts',
        'last_attempted_at',
    ];

    protected $casts = [
        'last_attempted_at' => 'datetime',
    ];
}
