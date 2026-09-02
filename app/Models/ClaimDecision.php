<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ClaimDecision extends Model
{
    use HasUuids;

    protected $fillable = [
        'insurance_claim_id',
        'decided_by',
        'decision',
        'approved_amount',
        'reason',
        'missing_information',
        'decided_at',
    ];

    protected $casts = [
        'approved_amount' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id');
    }
}
