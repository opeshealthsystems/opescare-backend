<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class IdempotencyRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'idempotency_key',
        'client_id',
        'request_hash',
        'response_status',
        'response_body',
        'expires_at'
    ];

    protected $casts = [
        'response_body' => 'array',
        'expires_at' => 'datetime'
    ];
}
