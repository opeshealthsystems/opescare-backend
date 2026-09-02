<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ClaimItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'insurance_claim_id',
        'description',
        'service_code',
        'quantity',
        'unit_price',
        'total_price',
        'approved_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'approved_amount' => 'decimal:2',
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id');
    }
}
