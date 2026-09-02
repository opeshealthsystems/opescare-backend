<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubscriptionPayment — Subscription / SaaS Billing (Module 23)
 *
 * Records a payment made against an organization subscription invoice.
 * SaaS billing is entirely separate from patient billing (Payment, Receipt).
 *
 * Security: every subscription payment must have a FinancialAudit companion.
 */
class SubscriptionPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_subscription_id',
        'subscription_invoice_id',
        'amount',
        'currency',
        'payment_method',       // card|bank|mobile_money
        'transaction_reference',
        'status',               // pending|confirmed|failed|refunded
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function organizationSubscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationSubscription::class);
    }

    public function subscriptionInvoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed', 'paid_at' => $this->paid_at ?? now()]);
    }

    public function refund(): void
    {
        $this->update(['status' => 'refunded']);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}
