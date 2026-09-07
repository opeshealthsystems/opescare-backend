<?php

namespace App\Services\Subscriptions;

use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Exceptions\PaymentException;
use App\Services\Payments\InvoiceService;
use App\Services\Payments\MobileMoneyProviderRegistry;
use App\Services\Payments\PaymentChargeService;
use App\Services\Subscriptions\Domain\SubscriptionStatus;
use App\Services\Subscriptions\Dto\SubscriptionCheckout;
use App\Services\Subscriptions\Exceptions\SubscriptionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Takes out a subscription and sends it to be paid for.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  IT DOES NOT TAKE MONEY — IT ASKS THE RAIL THAT ALREADY DOES
 * ════════════════════════════════════════════════════════════════════════════
 * There is exactly one payment path in this application: an invoice
 * (`mobile_invoices`), a charge against it (`mobile_payment_charges`), and
 * `PaymentChargeService`, which is the only writer of either status and which
 * carries three independent layers of double-charge protection, forward-only
 * settlement under a row lock, and a Postgres trigger underneath all of it.
 *
 * A subscription is not a new kind of money. So this class issues an ordinary
 * invoice through the ordinary issuer, stamps it `subject_type =
 * 'subscription'` so it can be recognised later, and hands it to
 * `PaymentChargeService::start()` like every other payment. It writes no
 * payment status of its own, calls no provider, and knows nothing about MTN or
 * Orange beyond which `PaymentMethod` the subscriber picked.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  WHAT IS GRANTED HERE: NOTHING
 * ════════════════════════════════════════════════════════════════════════════
 * The subscription this class creates is `pending_payment` with a NULL period.
 * It confers nothing. The period is written later and elsewhere — by
 * `SubscriptionSettlementService`, only when the invoice reaches `paid`, and
 * from the interval that was actually paid for. A charge that fails leaves the
 * row exactly as this class wrote it: asked for, not paid, granting nothing.
 * That ordering is the whole safety property, and it is why the two halves are
 * two classes.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  IDEMPOTENCY — the rail's discipline, extended one step earlier
 * ════════════════════════════════════════════════════════════════════════════
 * The rail's guarantee starts at the charge: `(patient_id, idempotency_key)`
 * is UNIQUE, so a retried submit collides instead of charging twice. But a
 * subscription checkout does two writes BEFORE that — a subscription row and
 * an invoice row — and a retry that reached those first would leave a
 * duplicate of each behind even though only one charge was ever made.
 *
 * So the same key is pushed one step earlier, onto a column that is already
 * unique: `mobile_invoices.reference`. The reference for a subscription
 * checkout is DERIVED from the key —
 * `OC-SUB-` + HMAC(APP_KEY, payer id | key) — so
 *
 *   - a retry with the same key computes the same reference and collides on
 *     INSERT rather than racing; the loser re-reads the winner's invoice and
 *     its subscription and carries on to the (also idempotent) charge;
 *   - both writes are in ONE transaction, so a loser leaves nothing behind —
 *     not an orphan subscription, not an orphan invoice;
 *   - and the key can no longer be reused for a different purchase without
 *     being noticed, because the fingerprint handed to the rail covers the
 *     plan, the interval, the subscriber and the amount as well as the wallet.
 *
 * The provider is still only ever called by `PaymentChargeService`, outside
 * any transaction. Nothing here holds a lock across a telco round trip.
 */
final class SubscriptionCheckoutService
{
    /**
     * The `mobile_invoices.subject_type` marker. This is the loose pointer the
     * settlement side reads back — deliberately not a foreign key, because the
     * money tables must not gain one from a module they do not own.
     */
    public const SUBJECT_TYPE = 'subscription';

    /** Prefix on every reference this checkout mints. Unmistakable in a log. */
    private const REFERENCE_PREFIX = 'OC-SUB-';

    /** SQLSTATE for unique_violation. */
    private const SQLSTATE_UNIQUE_VIOLATION = '23505';

    public function __construct(
        private readonly SubscriptionPlanCatalog $catalog,
        private readonly InvoiceService $invoices,
        private readonly PaymentChargeService $charges,
        private readonly MobileMoneyProviderRegistry $providers,
        private readonly SubscriptionAccessService $access,
    ) {
    }

    /**
     * Subscribe `$subscriber` to `$plan` for `$interval`, and start paying.
     *
     * @param  Model    $subscriber   A `Patient` or a `Facility`. WHO the
     *         subscription is for.
     * @param  string   $interval     'daily' | 'monthly' | 'annual'. Validated
     *         against the plan's own price columns, never against a list in
     *         code — that is what makes a facility annual-only.
     * @param  Msisdn   $payer        The wallet to debit. A value object, so
     *         the number cannot reach a log or a column on the way through.
     * @param  string   $payerPatientId  WHOSE ACCOUNT the charge is recorded
     *         against. The mobile money rail is a consumer rail: it debits a
     *         handset belonging to a person, and `mobile_invoices.patient_id`
     *         is that rail's only authorisation key. For a patient subscriber
     *         this MUST be the subscriber; for a facility it is the natural
     *         person paying on the facility's behalf.
     * @param  string   $idempotencyKey  The client's `Idempotency-Key`.
     * @param  int|null $expectedAmountMinor  The price the app DISPLAYED.
     *         Optional on the wire, always sent by the app: a subscriber
     *         authorises an amount, not a plan id, and if the catalogue has
     *         changed underneath them nothing is charged.
     *
     * @throws SubscriptionException|PaymentException
     */
    public function start(
        Model $subscriber,
        SubscriptionPlan $plan,
        string $interval,
        PaymentMethod $method,
        Msisdn $payer,
        string $payerPatientId,
        string $idempotencyKey,
        ?int $expectedAmountMinor = null,
    ): SubscriptionCheckout {
        $subscriberType = $this->catalog->subscriberTypeFor($subscriber);

        // A patient may only ever subscribe THEMSELVES through their own
        // wallet. Anything else on this path would mean one patient's bearer
        // token could open a subscription in another patient's name.
        if ($subscriber instanceof Patient && (string) $subscriber->getKey() !== $payerPatientId) {
            throw SubscriptionException::subscriberNotAllowed();
        }

        // ── Has this exact submit already been made? ────────────────────────
        // Checked FIRST and with no writes: a retry must not create a second
        // subscription on its way to discovering it is a retry.
        $existingCharge = MobilePaymentCharge::query()
            ->where('patient_id', $payerPatientId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingCharge !== null) {
            $invoice      = $this->invoiceOf($existingCharge);
            $subscription = $this->subscriptionOn($invoice);

            // Deliberately NOT returned directly. The call below re-enters the
            // rail's own replay path, which compares the request fingerprint
            // and answers 409 if this key is being reused to describe a
            // DIFFERENT purchase. One authority for that judgement, not two.
            //
            // The fingerprint is built from what was REQUESTED (this call's
            // plan and interval), never from the stored subscription — reading
            // it back off the row would make every replay match itself by
            // construction, and a key reused to buy something else would be
            // answered with somebody's other charge.
            return $this->handToRail(
                $subscription,
                $invoice,
                $subscriberType,
                $subscriber,
                $plan,
                $interval,
                $method,
                $payer,
                $idempotencyKey,
                $expectedAmountMinor,
                replayed: true,
            );
        }

        // ── Can this host take money at all? ────────────────────────────────
        // Asked before anything is written. `PaymentChargeService` asks the
        // same question, but by then a subscription and an invoice would exist
        // for a payment that was never possible.
        if (! $this->providers->isAvailable()) {
            throw PaymentException::providerUnavailable();
        }

        // ── Price it, from the plan ─────────────────────────────────────────
        // `priceFor()` refuses an interval the plan is not sold in. There is no
        // arithmetic here: the integer in the plan's price column is the
        // integer that reaches the invoice.
        $amount = $this->catalog->priceFor($plan, $interval);

        $this->refuseIfAlreadyCovered($subscriber, $plan);

        // ── Reuse a checkout that is still waiting to be paid ───────────────
        // A subscriber whose first wallet declined must be able to try another
        // one. That is a NEW charge against the SAME invoice, not a second
        // subscription — the rail already knows how to refuse a second
        // concurrent charge on one invoice.
        $reusable = $this->reusablePendingCheckout($subscriberType, $subscriber, $plan, $interval, $amount);

        if ($reusable !== null) {
            [$subscription, $invoice] = $reusable;
        } else {
            [$subscription, $invoice] = $this->open(
                $subscriberType,
                $subscriber,
                $plan,
                $interval,
                $amount,
                $payerPatientId,
                $idempotencyKey,
            );
        }

        return $this->handToRail(
            $subscription,
            $invoice,
            $subscriberType,
            $subscriber,
            $plan,
            $interval,
            $method,
            $payer,
            $idempotencyKey,
            $expectedAmountMinor,
            replayed: false,
        );
    }

    /**
     * The subscription an invoice was raised for.
     *
     * @throws SubscriptionException when the invoice is not a subscription
     *         invoice at all — which means an `Idempotency-Key` minted for
     *         some other kind of payment is being replayed here.
     */
    public function subscriptionOn(MobileInvoice $invoice): OrganizationSubscription
    {
        if ($invoice->subject_type !== self::SUBJECT_TYPE || $invoice->subject_id === null) {
            throw SubscriptionException::idempotencyMismatch();
        }

        $subscription = OrganizationSubscription::with('plan')->find($invoice->subject_id);

        if ($subscription === null) {
            throw SubscriptionException::notFound();
        }

        return $subscription;
    }

    // ── Creation ────────────────────────────────────────────────────────────

    /**
     * Write the subscription and its invoice, together or not at all.
     *
     * One transaction, so a caller that loses the race on the deterministic
     * reference leaves NOTHING behind — no half-open subscription, no invoice
     * nobody will pay. The loser re-reads the winner's rows and continues.
     *
     * @return array{0: OrganizationSubscription, 1: MobileInvoice}
     */
    private function open(
        string $subscriberType,
        Model $subscriber,
        SubscriptionPlan $plan,
        string $interval,
        Money $amount,
        string $payerPatientId,
        string $idempotencyKey,
    ): array {
        $reference = $this->referenceFor($payerPatientId, $idempotencyKey);

        try {
            return DB::transaction(function () use (
                $subscriberType, $subscriber, $plan, $interval, $amount, $payerPatientId, $reference
            ) {
                $isFacility = $subscriberType === SubscriptionPlanCatalog::SUBSCRIBER_FACILITY;

                $subscription = new OrganizationSubscription([
                    'subscriber_type' => $subscriberType,
                    'subscriber_id'   => $subscriber->getKey(),

                    // The legacy organization columns, filled ONLY for a
                    // facility — and filled on purpose: `EnforceModuleEntitlement`
                    // still looks subscriptions up by `organization_id`, so a
                    // facility subscription written without it would be
                    // invisible to the one gate that already exists.
                    'organization_id'   => $isFacility ? $subscriber->getKey() : null,
                    'organization_name' => $isFacility ? $subscriber->name : null,

                    'plan_id'  => $plan->id,
                    'interval' => $interval,

                    // Asked for. Not paid for. Grants nothing.
                    'status'   => SubscriptionStatus::PendingPayment->value,

                    // Always false, and it is not a placeholder. Mobile money
                    // holds no mandate: neither MTN MoMo nor Orange Money can
                    // be debited again without a fresh prompt on the handset.
                    // A `true` here would be a promise the rail cannot keep.
                    'auto_renew' => false,

                    'created_by' => 'subscription_checkout',
                ]);

                // Not fillable in spirit even where it is in fact: the period
                // is NOT set here. It is written once, by the settlement
                // service, after money arrives.
                $subscription->current_period_start = null;
                $subscription->current_period_end   = null;

                $subscription->save();

                $invoice = $this->invoices->issueFor(
                    patientId: $payerPatientId,
                    amount: $amount,
                    description: __('subscriptions.invoice_description', [
                        'plan'     => $plan->name,
                        'interval' => __('subscriptions.interval.'.$interval),
                    ]),
                    subjectType: self::SUBJECT_TYPE,
                    subjectId: $subscription->id,
                    // `facility_id` on an invoice means WHO GETS PAID. A
                    // facility subscription is paid TO the platform, not to
                    // the facility, so this stays null even for a facility
                    // subscriber. Putting the facility here would make the
                    // platform's own revenue look like the facility's.
                    facilityId: null,
                    isDemo: $this->providers->isSimulated(),
                    reference: $reference,
                );

                return [$subscription, $invoice];
            });
        } catch (QueryException $e) {
            if ($this->sqlState($e) !== self::SQLSTATE_UNIQUE_VIOLATION) {
                throw $e;
            }

            // Lost the race on `mobile_invoices.reference`. The twin request
            // holds the canonical rows for this idempotency key.
            return $this->adopt($reference, $payerPatientId, $plan, $interval, $amount);
        }
    }

    /**
     * Re-read the rows a concurrent twin committed under the same key.
     *
     * The checks are not ceremony: a key reused to buy something ELSE must be
     * a 409, not a silent hand-over of somebody's other purchase.
     *
     * @return array{0: OrganizationSubscription, 1: MobileInvoice}
     */
    private function adopt(
        string $reference,
        string $payerPatientId,
        SubscriptionPlan $plan,
        string $interval,
        Money $amount,
    ): array {
        $invoice = MobileInvoice::query()
            ->where('patient_id', $payerPatientId)
            ->where('reference', $reference)
            ->first();

        if ($invoice === null) {
            // A unique violation on this table with no matching reference is
            // not a race we understand. Surfacing it beats guessing.
            throw SubscriptionException::idempotencyMismatch();
        }

        $subscription = $this->subscriptionOn($invoice);

        $samePurchase = $subscription->plan_id === $plan->id
            && $subscription->interval === $interval
            && $invoice->amount_minor === $amount->minor;

        if (! $samePurchase) {
            throw SubscriptionException::idempotencyMismatch();
        }

        return [$subscription, $invoice];
    }

    /**
     * A checkout already open for exactly this purchase, if there is one.
     *
     * Only reused when the invoice is still payable AND still carries the
     * current price — an invoice's amount is immutable by database trigger, so
     * a plan whose price has changed since must start a fresh checkout rather
     * than quietly charge the old figure.
     *
     * @return array{0: OrganizationSubscription, 1: MobileInvoice}|null
     */
    private function reusablePendingCheckout(
        string $subscriberType,
        Model $subscriber,
        SubscriptionPlan $plan,
        string $interval,
        Money $amount,
    ): ?array {
        $subscriptions = OrganizationSubscription::query()
            ->with('plan')
            ->where('subscriber_type', $subscriberType)
            ->where('subscriber_id', $subscriber->getKey())
            ->where('plan_id', $plan->id)
            ->where('interval', $interval)
            ->where('status', SubscriptionStatus::PendingPayment->value)
            ->latest('created_at')
            ->limit(10)
            ->get();

        foreach ($subscriptions as $subscription) {
            $invoice = MobileInvoice::query()
                ->where('subject_type', self::SUBJECT_TYPE)
                ->where('subject_id', $subscription->id)
                ->where('amount_minor', $amount->minor)
                ->latest('created_at')
                ->first();

            if ($invoice !== null && $invoice->isPayable()) {
                return [$subscription, $invoice];
            }
        }

        return null;
    }

    /**
     * Refuse to sell a period that overlaps one already paid for.
     *
     * Scoped to the SAME plan on purpose: moving to a different plan is a
     * legitimate thing to want, and refusing it here would make an upgrade
     * impossible. Renewing an existing period is not implemented — that is a
     * separate decision (does it extend from today, or from the current end?)
     * and inventing an answer would be worse than saying so.
     */
    private function refuseIfAlreadyCovered(Model $subscriber, SubscriptionPlan $plan): void
    {
        $covered = $this->access->forSubscriber($subscriber)
            ->where('plan_id', $plan->id)
            ->get()
            ->first(fn (OrganizationSubscription $s) => $this->access->isCurrentlyPaid($s));

        if ($covered !== null) {
            throw SubscriptionException::alreadySubscribed(
                app(SubscriptionPresenter::class)->toApiArray($covered)
            );
        }
    }

    // ── The rail ────────────────────────────────────────────────────────────

    /**
     * Hand the invoice to `PaymentChargeService` and package the answer.
     *
     * The fingerprint is computed HERE and covers everything that defines the
     * purchase — subscriber, plan, interval, invoice, wallet and amount — so
     * that reusing one `Idempotency-Key` for a different plan is caught by the
     * rail's own conflict check rather than silently replaying the wrong
     * charge. The MSISDN goes in as its HASH: this value is stored, and a
     * stored fingerprint computed over a raw phone number is a phone number in
     * the database.
     */
    private function handToRail(
        OrganizationSubscription $subscription,
        MobileInvoice $invoice,
        string $subscriberType,
        Model $subscriber,
        SubscriptionPlan $plan,
        string $interval,
        PaymentMethod $method,
        Msisdn $payer,
        string $idempotencyKey,
        ?int $expectedAmountMinor,
        bool $replayed,
    ): SubscriptionCheckout {
        $charge = $this->charges->start(
            invoice: $invoice,
            method: $method,
            payer: $payer,
            idempotencyKey: $idempotencyKey,
            // Every field here comes from the REQUEST, not from the rows it
            // resolved to. That is the difference between a fingerprint that
            // can catch a reused key and one that always agrees with itself.
            requestFingerprint: hash('sha256', json_encode([
                'subscriber_type' => $subscriberType,
                'subscriber_id'   => (string) $subscriber->getKey(),
                'plan'            => (string) $plan->id,
                'interval'        => $interval,
                'invoice'         => $invoice->id,
                'method'          => $method->value,
                'payer'           => $payer->hash(),
                'amount'          => $invoice->amount_minor,
            ], JSON_THROW_ON_ERROR)),
            expectedAmountMinor: $expectedAmountMinor,
        );

        return new SubscriptionCheckout(
            subscription: $subscription->refresh(),
            invoice: $invoice->refresh(),
            charge: $charge,
            replayed: $replayed,
        );
    }

    /** The invoice a charge settles. A charge cannot exist without one. */
    private function invoiceOf(MobilePaymentCharge $charge): MobileInvoice
    {
        $invoice = $charge->invoice;

        if (! $invoice instanceof MobileInvoice) {
            throw SubscriptionException::notFound();
        }

        return $invoice;
    }

    /**
     * `OC-SUB-` + 12 symbols derived from the idempotency key.
     *
     * Deterministic, so a retry collides on the unique index instead of
     * creating a second invoice. Keyed on APP_KEY so the reference cannot be
     * predicted from the key alone and used to probe whether somebody else's
     * checkout exists. The alphabet is the issuer's own — Crockford-ish, with
     * I, O, 1 and 0 removed, because this string gets read down a phone line
     * and `OC-SUB-0O1I` is unreadable. 32 symbols, and 256 is an exact
     * multiple of 32, so the mapping below is unbiased.
     *
     * 19 characters, inside the column's 32.
     */
    private function referenceFor(string $payerPatientId, string $idempotencyKey): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        $digest = hash_hmac(
            'sha256',
            'subscription-checkout:'.$payerPatientId.'|'.$idempotencyKey,
            (string) Config::get('app.key', 'opescare-subscription'),
            true,
        );

        $tail = '';
        for ($i = 0; $i < 12; $i++) {
            $tail .= $alphabet[ord($digest[$i]) % 32];
        }

        return self::REFERENCE_PREFIX.$tail;
    }

    private function sqlState(QueryException $e): ?string
    {
        $state = $e->errorInfo[0] ?? null;

        return is_string($state) ? $state : null;
    }
}
