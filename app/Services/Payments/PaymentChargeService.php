<?php

namespace App\Services\Payments;

use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Domain\PaymentStatus;
use App\Services\Payments\Domain\ProviderPaymentStatus;
use App\Services\Payments\Dto\CollectionRequest;
use App\Services\Payments\Dto\CollectionResult;
use App\Services\Payments\Exceptions\PaymentException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Starts charges, polls them, and settles them. The only writer of
 * `mobile_payment_charges.status` and of `mobile_invoices.status`.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  IDEMPOTENCY — three independent layers, because one is not enough
 * ════════════════════════════════════════════════════════════════════════════
 * A patient on a bad connection taps "Pay" twice. Two requests arrive at two
 * PHP workers within milliseconds. Both read the invoice, both see it unpaid,
 * both decide to charge. Nothing in application code, on its own, prevents the
 * second charge — so the guarantee is not in application code:
 *
 *  1. **`(patient_id, idempotency_key)` UNIQUE.** The client sends an
 *     `Idempotency-Key`; a replay of the *same* request collides on INSERT.
 *     The loser catches the collision, re-reads the winner's row, and returns
 *     it — same charge, same response, no second provider call. This is the
 *     layer that makes a network retry safe.
 *
 *  2. **`UNIQUE (invoice_id) WHERE status = 'pending'`** — a partial index.
 *     This is the layer that catches a double-tap that generated *different*
 *     idempotency keys (two devices, an app restart, a regenerated UUID). Two
 *     concurrent INSERTs both pass every PHP check and one of them dies in the
 *     database. There is no window.
 *
 *  3. **`UNIQUE (invoice_id) WHERE status = 'paid'`** — the last line. Even if
 *     layers 1 and 2 were both circumvented, an invoice physically cannot
 *     record two successful charges.
 *
 * The provider is called AFTER the row is committed, never before and never
 * inside the transaction. A provider call inside a transaction holds a row lock
 * for the length of a telco round trip; a provider call before the INSERT means
 * the loser of a race has already sent a second prompt to the patient's handset
 * before it discovers it lost.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  SETTLEMENT — forward-only, under a lock, with the database as backstop
 * ════════════════════════════════════════════════════════════════════════════
 * Every status write happens inside a transaction that has taken
 * `SELECT … FOR UPDATE` on the row first, and re-reads the status *after*
 * acquiring the lock — the value read before the lock is worthless. A charge
 * that is already terminal is a no-op, so a callback and a poll arriving at the
 * same instant produce one transition, not two.
 *
 * Under all of that sits `opescare_mobile_payment_guard()`, a Postgres BEFORE
 * UPDATE trigger that rejects any transition outside
 * {pending→paid|failed|expired, paid→refunded} and any change to the amount,
 * currency or owner. If this class ever gets it wrong, the write fails; it does
 * not corrupt.
 */
final class PaymentChargeService
{
    /** A mobile money push prompt dies on the handset in about two minutes. */
    public const CHARGE_TTL_SECONDS = 180;

    /** SQLSTATE for unique_violation. */
    private const SQLSTATE_UNIQUE_VIOLATION = '23505';

    public function __construct(
        private readonly MobileMoneyProviderRegistry $providers,
        private readonly PaymentEventRecorder $events,
    ) {
    }

    /**
     * Begin (or replay) a payment attempt against an invoice.
     *
     * @param  Msisdn  $payer  Held as a value object so the raw number cannot
     *                         leak into a log or a column on the way through.
     * @param  string  $idempotencyKey     The client's `Idempotency-Key`.
     * @param  string  $requestFingerprint SHA-256 of the canonicalised request.
     * @param  int|null $expectedAmountMinor The amount the app DISPLAYED. A
     *         patient authorises an amount, not an invoice id; if what the app
     *         showed is not what the invoice says, nothing is charged.
     *
     * @throws PaymentException
     */
    public function start(
        MobileInvoice $invoice,
        PaymentMethod $method,
        Msisdn $payer,
        string $idempotencyKey,
        string $requestFingerprint,
        ?int $expectedAmountMinor = null,
    ): MobilePaymentCharge {
        // ── Layer 1, fast path: an exact replay of a request we already ran ──
        $existing = MobilePaymentCharge::where('patient_id', $invoice->patient_id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $this->replay($existing, $requestFingerprint);
        }

        if (! $this->providers->isAvailable()) {
            throw PaymentException::providerUnavailable();
        }

        if ($expectedAmountMinor !== null && $expectedAmountMinor !== $invoice->amount_minor) {
            // Not a generic 422: the client showed the patient a number that is
            // not the number on the invoice. Refusing is the whole point.
            throw new PaymentException(
                'amount_mismatch',
                'payments.errors.amount_mismatch',
                409,
                ['expected' => $invoice->money()->format()],
            );
        }

        $provider = $this->providers->for($method);

        // ── Clear a lapsed attempt before trying to open a new one ──────────
        // `UNIQUE (invoice_id) WHERE status = 'pending'` is what stops a double
        // charge, but it also means an ABANDONED attempt blocks the invoice for
        // the length of its TTL. A patient who mistypes their number, gets no
        // prompt, and taps Pay again 40 seconds later would be told "a payment
        // is already in progress" for a payment that will never arrive — with
        // no way out but waiting. So a pending charge that is past its own
        // deadline is closed here first. `syncFromProvider` asks the provider
        // once more before giving up, so a payment that succeeded in the last
        // few seconds is never discarded as expired.
        $stale = MobilePaymentCharge::where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Pending->value)
            ->where('expires_at', '<', Carbon::now())
            ->first();

        if ($stale !== null) {
            $this->syncFromProvider($stale);
        }

        // ── Create the row (short transaction, no network inside) ───────────
        try {
            $charge = DB::transaction(function () use ($invoice, $method, $payer, $idempotencyKey, $requestFingerprint, $provider) {
                // Lock the invoice, then re-read its state. The status read
                // before the lock proves nothing.
                /** @var MobileInvoice $locked */
                $locked = MobileInvoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

                if ($locked->status === PaymentStatus::Paid || $locked->status === PaymentStatus::Refunded) {
                    throw PaymentException::alreadyPaid();
                }

                if ($locked->status !== PaymentStatus::Pending) {
                    throw PaymentException::invoiceNotPayable($locked->status->value);
                }

                if ($locked->expires_at !== null && $locked->expires_at->isPast()) {
                    throw PaymentException::invoiceExpired();
                }

                $charge = new MobilePaymentCharge([
                    'invoice_id'          => $locked->id,
                    'idempotency_key'     => $idempotencyKey,
                    'request_fingerprint' => $requestFingerprint,
                    'provider'            => $provider->key(),
                    'method'              => $method->value,
                    // Masked and hashed. The real number is never written.
                    'payer_msisdn_masked' => $payer->masked(),
                    'payer_msisdn_hash'   => $payer->hash(),
                    'expires_at'          => Carbon::now()->addSeconds(self::CHARGE_TTL_SECONDS),
                ]);

                // Not fillable: authorisation and money, set from the locked
                // invoice — never from the request.
                $charge->patient_id   = $locked->patient_id;
                $charge->amount_minor = $locked->amount_minor;
                $charge->currency     = $locked->currency;
                $charge->status       = PaymentStatus::Pending;
                $charge->is_demo      = $provider->isSimulated();

                $charge->save();

                $this->events->record(
                    charge: $charge,
                    invoice: $locked,
                    source: 'api',
                    from: null,
                    to: PaymentStatus::Pending,
                    note: 'charge opened',
                );

                return $charge;
            });
        } catch (QueryException $e) {
            // ── Layers 1 and 2 firing: we lost a race. ──────────────────────
            if ($this->sqlState($e) !== self::SQLSTATE_UNIQUE_VIOLATION) {
                throw $e;
            }

            return $this->resolveLostRace($invoice, $idempotencyKey, $requestFingerprint);
        }

        // ── Ask the provider, outside the transaction ───────────────────────
        return $this->requestCollection($charge, $payer);
    }

    /**
     * Hand a committed charge to the provider and record what it said.
     *
     * Separate from `start()` because it is also the recovery path: a charge
     * that was committed but never reached the provider (a crash between the
     * two, a provider timeout) has `requested_at = null`, and the poll endpoint
     * calls this rather than leaving the patient staring at a spinner that will
     * never resolve.
     */
    private function requestCollection(MobilePaymentCharge $charge, Msisdn $payer): MobilePaymentCharge
    {
        $provider = $this->providers->for($charge->method);

        try {
            $result = $provider->collect(new CollectionRequest(
                externalReference: $charge->id,
                amount: Money::ofMinor($charge->amount_minor, $charge->currency),
                payer: $payer,
                method: $charge->method,
                description: $charge->invoice?->description ?? __('payments.default_description'),
                callbackUrl: $this->callbackUrlFor($provider->key()),
            ));
        } catch (RuntimeException $e) {
            // The provider blew up rather than answering. That is NOT a
            // failure — the wallet may already be debited. Leave the charge
            // pending and let the poller find out.
            $result = CollectionResult::unknown(failureCode: CollectionResult::FAILURE_UNREACHABLE);
        }

        $charge->forceFill([
            'requested_at'        => Carbon::now(),
            'provider_reference'  => $result->providerReference ?? $charge->provider_reference,
            'provider_status_code'=> $result->providerStatusCode,
        ])->save();

        return $this->applyResult($charge, $result, 'api');
    }

    /**
     * Re-ask the provider what happened, and settle if it is now conclusive.
     *
     * This — not the callback — is what settles a charge. See
     * `MobileMoneyProvider` for why: MTN's callback carries no signature at
     * all, and neither rail guarantees ordering or exactly-once delivery.
     */
    public function syncFromProvider(MobilePaymentCharge $charge): MobilePaymentCharge
    {
        if ($charge->status->isTerminal()) {
            return $charge;
        }

        // A prompt nobody answered. Close it locally; there is nothing to ask.
        if ($charge->expires_at->isPast()) {
            return $this->applyResult(
                $charge,
                new CollectionResult(
                    status: ProviderPaymentStatus::Expired,
                    providerReference: $charge->provider_reference,
                    providerStatusCode: 'LOCAL_TIMEOUT',
                    failureCode: CollectionResult::FAILURE_TIMEOUT,
                ),
                'sweeper',
            );
        }

        if ($charge->provider_reference === null) {
            // Committed but never accepted by the provider. Nothing to poll —
            // and nothing to re-send either: re-sending would be the second
            // prompt this whole class exists to prevent. It expires.
            return $charge;
        }

        $provider = $this->providers->for($charge->method);

        try {
            $result = $provider->poll($charge->provider_reference, [
                'order_id'     => $charge->id,
                'amount_minor' => $charge->amount_minor,
                'currency'     => $charge->currency,
            ]);
        } catch (RuntimeException) {
            $result = CollectionResult::unknown($charge->provider_reference, CollectionResult::FAILURE_UNREACHABLE);
        }

        $charge->forceFill(['last_polled_at' => Carbon::now()])->save();

        return $this->applyResult($charge, $result, 'poll');
    }

    /**
     * Write a provider answer onto the charge, and onto the invoice when it is
     * conclusive. Idempotent, locked, and forward-only.
     *
     * @param  string  $source  'api' | 'poll' | 'callback' | 'sweeper'
     */
    public function applyResult(MobilePaymentCharge $charge, CollectionResult $result, string $source): MobilePaymentCharge
    {
        $target = $result->status->toRecordStatus();

        // `Unknown` and `Pending` are not answers — record the code and leave
        // the row alone. Mapping "I could not tell" onto `failed` is how a paid
        // invoice gets charged again.
        if ($target === null) {
            if ($result->providerStatusCode !== null) {
                $charge->forceFill(['provider_status_code' => $result->providerStatusCode])->save();
            }

            return $charge->refresh();
        }

        return DB::transaction(function () use ($charge, $result, $target, $source) {
            /** @var MobilePaymentCharge $locked */
            $locked = MobilePaymentCharge::whereKey($charge->id)->lockForUpdate()->firstOrFail();

            // Re-read AFTER the lock. Another worker may have settled it while
            // this one waited, and that worker's answer stands.
            if ($locked->status->isTerminal()) {
                return $locked;
            }

            $from = $locked->status;

            if (! $from->canTransitionTo($target)) {
                // Unreachable given the check above; kept because the day it
                // becomes reachable, silently writing would be worse.
                return $locked;
            }

            $locked->forceFill([
                'status'               => $target->value,
                'settled_at'           => Carbon::now(),
                'provider_reference'   => $result->providerReference ?? $locked->provider_reference,
                'provider_status_code' => $result->providerStatusCode ?? $locked->provider_status_code,
                'failure_code'         => $result->failureCode,
            ])->save();

            $this->events->record(
                charge: $locked,
                invoice: null,
                source: $source,
                from: $from,
                to: $target,
                providerStatusCode: $result->providerStatusCode,
                failureCode: $result->failureCode,
            );

            $this->settleInvoice($locked, $target, $source, $result);

            return $locked;
        });
    }

    /**
     * Move the invoice, when the charge's outcome justifies it.
     *
     * A FAILED or EXPIRED charge does NOT close the invoice — the patient is
     * still free to try again with another wallet, and closing it would turn a
     * mistyped number into an unpayable bill. Only success moves it.
     */
    private function settleInvoice(MobilePaymentCharge $charge, PaymentStatus $chargeStatus, string $source, CollectionResult $result): void
    {
        if ($chargeStatus !== PaymentStatus::Paid) {
            return;
        }

        /** @var MobileInvoice $invoice */
        $invoice = MobileInvoice::whereKey($charge->invoice_id)->lockForUpdate()->firstOrFail();

        if ($invoice->status !== PaymentStatus::Pending) {
            // Already settled by a concurrent path. The partial unique index on
            // `(invoice_id) WHERE status = 'paid'` means at most one charge can
            // ever have got this far, so this is the benign "we raced and lost"
            // branch, not a double payment.
            return;
        }

        $from = $invoice->status;

        $invoice->forceFill([
            'status'  => PaymentStatus::Paid->value,
            'paid_at' => Carbon::now(),
        ])->save();

        $this->events->record(
            charge: $charge,
            invoice: $invoice,
            source: $source,
            from: $from,
            to: PaymentStatus::Paid,
            providerStatusCode: $result->providerStatusCode,
            note: 'invoice settled',
        );
    }

    /**
     * Close charges whose handset prompt lapsed, and invoices past their own
     * deadline. Safe to run repeatedly; every write is forward-only.
     *
     * @return array{charges: int, invoices: int}
     */
    public function expireLapsed(int $limit = 200): array
    {
        $charges = MobilePaymentCharge::where('status', PaymentStatus::Pending->value)
            ->where('expires_at', '<', Carbon::now())
            ->limit($limit)
            ->get();

        $closedCharges = 0;
        foreach ($charges as $charge) {
            // Ask once more before giving up: a charge that succeeded in the
            // last few seconds must not be recorded as expired.
            $refreshed = $this->syncFromProvider($charge);
            if ($refreshed->status !== PaymentStatus::Pending) {
                $closedCharges++;
            }
        }

        $invoices = MobileInvoice::where('status', PaymentStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->whereDoesntHave('charges', fn ($q) => $q->where('status', PaymentStatus::Pending->value))
            ->limit($limit)
            ->get();

        $closedInvoices = 0;
        foreach ($invoices as $invoice) {
            $from = $invoice->status;
            $invoice->forceFill([
                'status'     => PaymentStatus::Expired->value,
                'expired_at' => Carbon::now(),
            ])->save();

            $this->events->record(
                charge: null,
                invoice: $invoice,
                source: 'sweeper',
                from: $from,
                to: PaymentStatus::Expired,
                note: 'invoice lapsed unpaid',
            );

            $closedInvoices++;
        }

        return ['charges' => $closedCharges, 'invoices' => $closedInvoices];
    }

    // ── Idempotency plumbing ────────────────────────────────────────────────

    /**
     * A request whose idempotency key we have seen before.
     *
     * Same key + same body  → the original charge, unchanged, no second call.
     * Same key + DIFFERENT body → 409. This is the case people forget: a client
     * that reuses a key for a different payment must be told, not quietly
     * handed someone else's result.
     */
    private function replay(MobilePaymentCharge $existing, string $requestFingerprint): MobilePaymentCharge
    {
        if (! hash_equals($existing->request_fingerprint, $requestFingerprint)) {
            throw PaymentException::idempotencyConflict();
        }

        return $existing;
    }

    /**
     * We tried to INSERT and hit a unique index. Work out which one, and answer
     * correctly — without ever creating a second charge.
     */
    private function resolveLostRace(MobileInvoice $invoice, string $idempotencyKey, string $requestFingerprint): MobilePaymentCharge
    {
        // Layer 1: the twin request beat us with the SAME key. Its row is the
        // canonical result of both requests.
        $twin = MobilePaymentCharge::where('patient_id', $invoice->patient_id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($twin !== null) {
            return $this->replay($twin, $requestFingerprint);
        }

        // Layer 2: a DIFFERENT key, but this invoice already has a payment in
        // flight. Returning it silently would be wrong (the patient may have
        // chosen a different wallet), so this is a 409 that carries the
        // in-flight charge, letting the app jump straight to its pending state.
        $inFlight = MobilePaymentCharge::where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Pending->value)
            ->first();

        if ($inFlight !== null) {
            throw PaymentException::paymentInProgress($inFlight->toApiArray());
        }

        // Layer 3: it is already paid.
        throw PaymentException::alreadyPaid();
    }

    private function sqlState(QueryException $e): ?string
    {
        $state = $e->errorInfo[0] ?? null;

        return is_string($state) ? $state : null;
    }

    /**
     * Where this rail should call us back.
     *
     * The path segment carries the provider key only; the charge is identified
     * by the body (`order_id` / `externalId`) and authenticated by the rail's
     * own mechanism. Built from `app.url` so a demo host and a production host
     * never hand a provider each other's address.
     */
    private function callbackUrlFor(string $providerKey): string
    {
        return rtrim((string) config('app.url'), '/')."/api/mobile/payments/callbacks/{$providerKey}";
    }
}
