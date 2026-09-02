<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PreauthorizationDecision extends Model
{
    use HasUuids;

    protected $fillable = [
        'preauthorization_request_id',
        'decided_by',
        'decision',
        'approved_amount',
        'reason',
        'authorization_number',
        'valid_until',
        'decided_at',
    ];

    protected $casts = [
        'approved_amount' => 'decimal:2',
        'valid_until' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(PreauthorizationRequest::class, 'preauthorization_request_id');
    }
}
