<?php

namespace App\Models;

use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Something a patient owes, in integer minor units.
 *
 * `amount_minor` is a BIGINT of minor units and XAF has ISO 4217 exponent 0,
 * so **the minor unit is the franc**: `amount_minor = 5000` is 5 000 FCFA. It
 * is cast to `integer`, never to `float`/`decimal`, and every read that needs
 * to display or compare it goes through `money()`.
 *
 * Deliberately NOT using the `IsDemoRecord` trait. That trait installs a global
 * scope keyed on `config('demo.enabled')`, which the bearer-token mobile API
 * never sets (`DemoDataScope` requires `Auth::check()`, and there is no session
 * guard here). A global scope that is half-applied on a money table is how an
 * invoice becomes invisible to the very patient who owes it — the same trap
 * that made mobile login 404 for non-demo patients. Isolation on this table is
 * `patient_id`, taken from the bearer token and never from a request body.
 *
 * Mutability: after insert, the database itself refuses to change
 * `amount_minor`, `currency` or `patient_id`, and refuses any status move
 * outside pending→paid|failed|expired and paid→refunded. See
 * `opescare_mobile_payment_guard()` in the creating migration.
 */
class MobileInvoice extends Model
{
    use HasUuids;

    protected $table = 'mobile_invoices';

    /**
     * `patient_id` is absent on purpose: it is authorisation, not input. It is
     * set by `InvoiceService` from `$request->attributes`, so no mass-assign
     * from a request body can ever point an invoice at another patient.
     * `status` and the settlement timestamps are absent for the same reason —
     * only `PaymentChargeService` moves them, inside a locked transaction.
     */
    protected $fillable = [
        'reference',
        'facility_id',
        'subject_type',
        'subject_id',
        'description',
        'amount_minor',
        'currency',
        'expires_at',
    ];

    protected $casts = [
        // INTEGER. Never float, never decimal.
        'amount_minor' => 'integer',
        'status'       => PaymentStatus::class,
        'is_demo'      => 'boolean',
        'expires_at'   => 'datetime',
        'paid_at'      => 'datetime',
        'failed_at'    => 'datetime',
        'expired_at'   => 'datetime',
        'refunded_at'  => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(MobilePaymentCharge::class, 'invoice_id');
    }

    /** The one successful charge, if there is one. Enforced unique by the DB. */
    public function paidCharge(): ?MobilePaymentCharge
    {
        return $this->charges()->where('status', PaymentStatus::Paid->value)->first();
    }

    /** The one in-flight charge, if there is one. Enforced unique by the DB. */
    public function pendingCharge(): ?MobilePaymentCharge
    {
        return $this->charges()->where('status', PaymentStatus::Pending->value)->first();
    }

    public function money(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }

    /** Open, and not past its own deadline. */
    public function isPayable(): bool
    {
        return $this->status === PaymentStatus::Pending
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * The wire shape. `amount_minor` is the integer of record; `amount_display`
     * is the pre-formatted FCFA string, so no client ever has to know an
     * exponent — the bug where a currency is re-derived on the client is
     * exactly how "5 000 FCFA" becomes "50.00".
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id'            => $this->id,
            'reference'     => $this->reference,
            'description'   => $this->description,
            'status'        => $this->status->value,
            'status_label'  => $this->status->label(),
            ...$this->money()->toArray(),
            'facility_id'   => $this->facility_id,
            'subject_type'  => $this->subject_type,
            'subject_id'    => $this->subject_id,
            'is_payable'    => $this->isPayable(),
            'is_demo'       => (bool) $this->is_demo,
            'expires_at'    => $this->expires_at?->toIso8601String(),
            'paid_at'       => $this->paid_at?->toIso8601String(),
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
