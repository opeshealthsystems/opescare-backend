<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentReconciliation — Module 7 (Billing, Payments & Wallet)
 *
 * End-of-shift or end-of-day cashier reconciliation record.
 * Compares expected totals from system records against actual
 * counted cash and digital transaction confirmations.
 */
class PaymentReconciliation extends Model
{
    use HasUuids;

    protected $fillable = [
        'cashier_session_id',
        'facility_id',
        'reconciliation_date',
        'reconciled_by',
        'expected_cash',
        'actual_cash',
        'expected_digital',
        'actual_digital',
        'variance',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'expected_cash'       => 'decimal:2',
        'actual_cash'         => 'decimal:2',
        'expected_digital'    => 'decimal:2',
        'actual_digital'      => 'decimal:2',
        'variance'            => 'decimal:2',
        'approved_at'         => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function cashierSession(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function calculateVariance(): float
    {
        $cashVariance    = (float) $this->actual_cash    - (float) $this->expected_cash;
        $digitalVariance = (float) $this->actual_digital - (float) $this->expected_digital;
        return round($cashVariance + $digitalVariance, 2);
    }

    public function hasDiscrepancy(): bool
    {
        return abs($this->calculateVariance()) > 0.01;
    }

    public function submit(): void
    {
        $this->update([
            'variance' => $this->calculateVariance(),
            'status'   => 'submitted',
        ]);
    }

    public function approve(string $approvedBy): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'draft'     => 'badge badge--neutral',
            'submitted' => 'badge badge--info',
            'approved'  => 'badge badge--success',
            'disputed'  => 'badge badge--danger',
            default     => 'badge badge--neutral',
        };
    }
}
