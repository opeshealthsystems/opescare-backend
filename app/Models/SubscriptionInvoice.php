<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_id', 'organization_id', 'invoice_number',
        'invoice_date', 'due_date', 'paid_at', 'status',
        'subtotal_kobo', 'discount_kobo', 'tax_kobo', 'total_kobo', 'currency',
        'line_items', 'payment_reference', 'payment_method', 'notes', 'created_by',
    ];

    protected $casts = [
        'invoice_date'   => 'date',
        'due_date'       => 'date',
        'paid_at'        => 'date',
        'line_items'     => 'array',
        'subtotal_kobo'  => 'integer',
        'discount_kobo'  => 'integer',
        'tax_kobo'       => 'integer',
        'total_kobo'     => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationSubscription::class, 'subscription_id');
    }

    public function totalFormatted(): string
    {
        return $this->currency . ' ' . number_format($this->total_kobo / 100, 2);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'sent' && $this->due_date->isPast();
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'paid'    => 'success',
            'sent'    => $this->isOverdue() ? 'danger' : 'warning',
            'overdue' => 'danger',
            'void'    => 'default',
            default   => 'default',
        };
    }
}
