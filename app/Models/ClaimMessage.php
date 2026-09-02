<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ClaimMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'insurance_claim_id',
        'sender_type',
        'sender_id',
        'body',
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class, 'insurance_claim_id');
    }
}
