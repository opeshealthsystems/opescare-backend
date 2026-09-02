<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentPlanItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'payment_plan_id', 'due_date', 'amount', 'status', 'paid_at', 'payment_method',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at'  => 'datetime',
    ];

    public function plan() { return $this->belongsTo(PaymentPlan::class, 'payment_plan_id'); }
}
