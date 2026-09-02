<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncConflict extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'offline_queue_id',
        'conflict_type',
        'status',
        'resolution_strategy',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];
}
