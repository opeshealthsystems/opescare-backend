<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientPaymentPlan extends Model
{
    use HasUuids, HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id', 'invoice_id', 'facility_id',
        'total_amount', 'down_payment', 'installment_amount',
        'installment_count', 'paid_count', 'frequency', 'status',
        'next_due_date', 'started_at', 'completed_at', 'notes',
    ];

    protected $casts = [
        'total_amount'       => 'decimal:2',
        'down_payment'       => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'installment_count'  => 'integer',
        'paid_count'         => 'integer',
        'next_due_date'      => 'date',
        'started_at'         => 'datetime',
        'completed_at'       => 'datetime',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function invoice(): BelongsTo  { return $this->belongsTo(Invoice::class); }
    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class, 'payment_plan_id');
    }
}
