<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanInstallment extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'payment_plan_id', 'due_date', 'amount', 'paid_amount',
        'status', 'paid_at', 'payment_reference',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at'     => 'datetime',
    ];

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PatientPaymentPlan::class, 'payment_plan_id');
    }
}
