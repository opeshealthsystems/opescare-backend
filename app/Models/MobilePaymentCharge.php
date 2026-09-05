<?php

namespace App\Models;

use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Domain\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One attempt to settle one invoice through one mobile money wallet.
 *
 * ── The payer's phone number is not in this model ───────────────────────────
 * There is no `payer_msisdn` attribute and there is no accessor that yields
 * one. What persists is `payer_msisdn_masked` ('+2376*****01', DB-constrained
 * to contain a '*') and `payer_msisdn_hash` (HMAC-SHA256 keyed on APP_KEY).
 * The real number exists only inside the request that created this row, as an
 * `App\Services\Payments\Domain\Msisdn`, and is handed to the provider and
 * dropped. `$hidden` keeps the hash out of every serialisation as well — it is
 * a support-correlation key, not something a client needs.
 *
 * ── Idempotency ─────────────────────────────────────────────────────────────
 * `(patient_id, idempotency_key)` is UNIQUE, so a retried submit collides
 * rather than charging twice. `request_fingerprint` catches the other half of
 * the problem: the same key sent with a *different* body is a client bug and
 * must be a 409, not a silent replay of the wrong amount.
 *
 * ── State ───────────────────────────────────────────────────────────────────
 * Forward-only, enforced by a Postgres BEFORE UPDATE trigger, plus partial
 * unique indexes that permit at most one `paid` and one `pending` charge per
 * invoice. A double-charge is a constraint violation here, not a code review
 * finding.
 */
class MobilePaymentCharge extends Model
{
    use HasUuids;

    protected $table = 'mobile_payment_charges';

    /**
     * Everything meaningful is set by `PaymentChargeService` explicitly. Mass
     * assignment is limited to the descriptive fields; `status`, `patient_id`,
     * `amount_minor` and every provider field stay out of reach of a body.
     */
    protected $fillable = [
        'invoice_id',
        'idempotency_key',
        'request_fingerprint',
        'provider',
        'method',
        'payer_msisdn_masked',
        'payer_msisdn_hash',
        'expires_at',
    ];

    protected $casts = [
        'amount_minor'            => 'integer',
        'status'                  => PaymentStatus::class,
        'method'                  => PaymentMethod::class,
        'is_demo'                 => 'boolean',
        // AES-256 via APP_KEY. Orange Money's per-transaction `notif_token` is
        // a bearer secret; it is stored encrypted and never leaves the server.
        'provider_callback_token' => 'encrypted',
        'requested_at'            => 'datetime',
        'last_polled_at'          => 'datetime',
        'settled_at'              => 'datetime',
        'expires_at'              => 'datetime',
    ];

    /**
     * Never serialised, to anyone. The hash is a one-way correlation key for
     * support; the callback token is a provider secret.
     */
    protected $hidden = [
        'payer_msisdn_hash',
        'provider_callback_token',
        'request_fingerprint',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(MobileInvoice::class, 'invoice_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MobilePaymentEvent::class, 'charge_id')->orderBy('occurred_at');
    }

    public function money(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }

    /** Still worth polling: open, and not past its own deadline. */
    public function isPollable(): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->provider_reference !== null
            && $this->expires_at->isFuture();
    }

    /**
     * The wire shape.
     *
     * `failure_code` is a normalised code, not a provider sentence — the app
     * translates it through `payments.failure.*`, so a French-speaking patient
     * is not shown an English string from a telco. `simulated` is what the app
     * renders its "no money will move" banner from.
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id'             => $this->id,
            'invoice_id'     => $this->invoice_id,
            'status'         => $this->status->value,
            'status_label'   => $this->status->label(),
            'method'         => $this->method->value,
            'method_label'   => $this->method->label(),
            'provider'       => $this->provider,
            'simulated'      => (bool) $this->is_demo,
            ...$this->money()->toArray(),
            // Masked, always. There is no code path that returns the real one.
            'payer_masked'   => $this->payer_msisdn_masked,
            'failure_code'   => $this->failure_code,
            'failure_reason' => $this->failure_code ? __('payments.failure.'.$this->failure_code) : null,
            'requested_at'   => $this->requested_at?->toIso8601String(),
            'settled_at'     => $this->settled_at?->toIso8601String(),
            'expires_at'     => $this->expires_at?->toIso8601String(),
            // Tells the app whether to keep polling, so the poll cadence is a
            // server decision rather than a guess baked into the client.
            'poll'           => $this->status === PaymentStatus::Pending,
        ];
    }
}
