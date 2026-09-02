<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'action', 'resource_type', 'resource_id', 'before', 'after', 'ip_address', 'occurred_at',
    ];

    protected $casts = [
        'before'      => 'array',
        'after'       => 'array',
        'occurred_at' => 'datetime',
    ];
}
