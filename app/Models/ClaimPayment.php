<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ClaimPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'insurance_claim_id',
        'amount',
        'payment_method',
        'reference_number',
        'recorded_by',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id');
    }
}
