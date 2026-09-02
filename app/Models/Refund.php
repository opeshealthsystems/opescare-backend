<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Refund — Module 20 (Billing, Payments & Wallet)
 *
 * Tracks refund requests and processing against payments.
 * Refunds require explicit approval before processing to maintain
 * financial integrity and audit compliance.
 *
 * Security: Payment/refund changes require audit. (see constraints)
 */
class Refund extends Model
{
    use HasUuids;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'patient_id',
        'facility_id',
        'amount',
        'currency',
        'reason',
        'notes',
        'status',
        'refund_method',
        'processed_by',
        'approved_at',
        'processed_at',
        'reference_number',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'approved_at'  => 'datetime',
        'processed_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the refund. Does NOT process it — requires explicit process() call.
     * Audit log must be created by the calling service/controller.
     */
    public function approve(string $approvedBy): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'processed_by' => $approvedBy,
        ]);
    }

    /**
     * Mark as processed (disbursed).
     * Audit log must be created by the calling service/controller.
     */
    public function process(string $processedBy, ?string $referenceNumber = null): void
    {
        $this->update([
            'status'           => 'processed',
            'processed_by'     => $processedBy,
            'processed_at'     => now(),
            'reference_number' => $referenceNumber ?? $this->reference_number,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'notes'  => $reason,
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'   => 'badge badge--warning',
            'approved'  => 'badge badge--info',
            'processed' => 'badge badge--success',
            'rejected'  => 'badge badge--danger',
            default     => 'badge badge--neutral',
        };
    }
}
