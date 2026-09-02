<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'patient_id',
        'facility_id',
        'cashier_id',
        'cashier_session_id',
        'wallet_id',
        'payment_reference',
        'method',
        'gateway',
        'gateway_transaction_id',
        'gateway_status',
        'payer_phone',
        'payer_name',
        'service_type',
        'device_type',
        'device_id',
        'user_agent',
        'ip_address',
        'status',
        'amount',
        'currency',
        'refunded_amount',
        'initiated_at',
        'confirmed_at',
        'failed_at',
        'gateway_metadata',
        'failure_reason',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'initiated_at'    => 'datetime',
        'confirmed_at'    => 'datetime',
        'failed_at'       => 'datetime',
        'gateway_metadata' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function reversals()
    {
        return $this->hasMany(PaymentReversal::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getGatewayLabelAttribute(): string
    {
        return match($this->gateway ?? $this->method) {
            'mtn_momo'       => 'MTN MoMo',
            'orange_money'   => 'Orange Money',
            'card'           => 'Card',
            'cash'           => 'Cash',
            'insurance'      => 'Insurance',
            'bank_transfer'  => 'Bank Transfer',
            'wallet'         => 'Platform Wallet',
            default          => ucfirst($this->gateway ?? $this->method ?? 'Unknown'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'successful', 'completed' => 'success',
            'pending'                 => 'warning',
            'failed'                  => 'danger',
            'refunded', 'reversed'    => 'info',
            default                   => 'secondary',
        };
    }

    public function getNetAmountAttribute(): float
    {
        return (float) $this->amount - (float) $this->refunded_amount;
    }
}
