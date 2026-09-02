<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RemittanceAdvice extends Model
{
    use HasUuids;

    protected $table = 'remittance_advices';

    protected $fillable = [
        'claim_submission_id', 'paid_amount', 'adjustment_amount',
        'adjustment_reason', 'paid_on', 'payment_reference',
    ];

    protected $casts = ['paid_on' => 'date'];

    public function claim() { return $this->belongsTo(ClaimSubmission::class, 'claim_submission_id'); }
}
