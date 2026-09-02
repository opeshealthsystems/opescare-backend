<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReconciliationCase extends Model
{
    use HasUuids;

    protected $fillable = [
        'mismatch_reason',
        'external_reference',
        'submitted_payload',
        'status',
        'resolved_at'
    ];

    protected $casts = [
        'submitted_payload' => 'array',
        'resolved_at' => 'datetime'
    ];
}
