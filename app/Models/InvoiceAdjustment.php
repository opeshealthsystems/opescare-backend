<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvoiceAdjustment — Module 7 (Billing, Payments & Wallet)
 *
 * Records discounts, surcharges, write-offs, corrections, and
 * insurance credits applied to an invoice.
 *
 * Security: Payment/refund/adjustment changes require audit trail.
 * The calling service must create a FinancialAudit record.
 */
class InvoiceAdjustment extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'adjustment_type',
        'amount',
        'currency',
        'reason',
        'notes',
        'adjusted_by',
        'approved_by',
        'approved_at',
        'requires_approval',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'requires_approval' => 'boolean',
        'approved_at'      => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->approved_by !== null;
    }

    public function isPendingApproval(): bool
    {
        return $this->requires_approval && $this->approved_by === null;
    }

    public function approve(string $approvedBy): void
    {
        $this->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function isDebit(): bool
    {
        return in_array($this->adjustment_type, ['surcharge']);
    }

    public function isCredit(): bool
    {
        return in_array($this->adjustment_type, ['discount', 'write_off', 'correction', 'insurance_credit']);
    }
}
