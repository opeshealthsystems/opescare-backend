<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TelemedicinePaymentLink — Telemedicine Module
 *
 * Represents a payment link sent to a patient for a teleconsultation fee.
 * Links expire and must be regenerated if unused.
 *
 * Security: payment links must never bypass the billing/financial audit trail.
 * Every payment recorded here must also generate a FinancialAudit event.
 */
class TelemedicinePaymentLink extends Model
{
    use HasUuids;

    protected $fillable = [
        'teleconsultation_id',
        'patient_id',
        'amount',
        'currency',
        'payment_url',
        'reference',
        'status',       // pending|paid|expired|cancelled
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at'    => 'datetime',
    ];

    public function teleconsultation(): BelongsTo
    {
        return $this->belongsTo(Teleconsultation::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at !== null && $this->expires_at->isPast() && $this->status === 'pending');
    }

    public function markPaid(): void
    {
        $this->update(['status' => 'paid', 'paid_at' => now()]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
