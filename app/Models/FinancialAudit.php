<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FinancialAudit — Module 7 (Billing, Payments & Wallet)
 *
 * Structured audit trail for all financial actions: invoice creation,
 * payments, refunds, adjustments, and reconciliations.
 *
 * Security constraint: "Do not allow payment/refund changes without audit."
 * Every controller or service that changes financial data MUST call
 * FinancialAudit::record() before or after the operation.
 *
 * Append-only — never update or delete entries.
 */
class FinancialAudit extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'patient_id',
        'actor_id',
        'actor_type',
        'amount',
        'currency',
        'before_state',
        'after_state',
        'ip_address',
        'occurred_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
        'amount'       => 'decimal:2',
        'occurred_at'  => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    /**
     * Record a financial audit event.
     * Call this for every invoice, payment, refund, or adjustment action.
     */
    public static function record(
        string $eventType,
        string $auditableType,
        string $auditableId,
        string $actorId,
        string $actorType = 'user',
        ?string $facilityId = null,
        ?string $patientId = null,
        ?float $amount = null,
        ?string $currency = null,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $ipAddress = null
    ): self {
        return static::create([
            'facility_id'    => $facilityId,
            'event_type'     => $eventType,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'patient_id'     => $patientId,
            'actor_id'       => $actorId,
            'actor_type'     => $actorType,
            'amount'         => $amount,
            'currency'       => $currency,
            'before_state'   => $beforeState,
            'after_state'    => $afterState,
            'ip_address'     => $ipAddress,
            'occurred_at'    => now(),
        ]);
    }
}
