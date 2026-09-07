<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MobileInvoice;
use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Models\SubscriptionPlan;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Domain\PaymentStatus;
use App\Services\Payments\Exceptions\PaymentException;
use App\Services\Payments\MobileMoneyProviderRegistry;
use App\Services\Payments\PaymentChargeService;
use App\Services\Subscriptions\Domain\BillingInterval;
use App\Services\Subscriptions\SubscriptionAccessService;
use App\Services\Subscriptions\SubscriptionCheckoutService;
use App\Services\Subscriptions\SubscriptionPlanCatalog;
use App\Services\Subscriptions\SubscriptionPresenter;
use App\Services\Subscriptions\SubscriptionSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Mobile Patient API — subscriptions.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  ISOLATION
 * ════════════════════════════════════════════════════════════════════════════
 * The subscriber is the patient the auth middleware stamped on
 * `$request->attributes`, and nothing else. There is no patient id, no
 * subscriber id and no subscriber type in any path, query string or body on
 * this controller — a body that says `{"subscriber_id": "<someone else>"}`
 * changes nothing, because no code here reads one.
 *
 * Lookups are scoped in the WHERE clause rather than checked afterwards, so
 * another patient's subscription is a plain 404: a 403 would confirm the id
 * exists, and a confirmable subscription id is an oracle over who is paying
 * for what.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  MONEY
 * ════════════════════════════════════════════════════════════════════════════
 * Prices cross this boundary as `amount_minor`, an INTEGER, alongside a
 * pre-formatted `amount_display`. XAF has ISO 4217 exponent 0, so the integer
 * is a whole number of francs: 1500 is 1 500 FCFA. `amount_minor` on the way
 * in is validated `integer`, never `numeric` — `numeric` accepts "1500.4", and
 * a float is how a franc goes missing.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  IDEMPOTENCY
 * ════════════════════════════════════════════════════════════════════════════
 * `POST /subscriptions` REQUIRES an `Idempotency-Key`, exactly as the invoice
 * payment endpoint does; without one it is a 400. The key stops a double
 * submit at three levels — the deterministic invoice reference, the unique
 * `(patient, key)` charge index, and the partial unique index that permits one
 * pending charge per invoice.
 *
 * ════════════════════════════════════════════════════════════════════════════
 *  WHAT THIS CONTROLLER DOES NOT DO
 * ════════════════════════════════════════════════════════════════════════════
 * It never denies anything on the basis of a subscription. Not a record, not a
 * screen, not an endpoint. It reports state; it does not enforce it. See
 * `SubscriptionAccessService` for why, and for where an enforcement decision
 * would go once somebody has actually made one.
 */
class MobileSubscriptionController extends Controller
{
    /** Bound below the DB column width so an over-long key is a 400, not a 500. */
    private const MAX_IDEMPOTENCY_KEY = 190;

    public function __construct(
        private readonly SubscriptionPlanCatalog $catalog,
        private readonly SubscriptionCheckoutService $checkout,
        private readonly SubscriptionSettlementService $settlement,
        private readonly SubscriptionAccessService $access,
        private readonly SubscriptionPresenter $presenter,
        private readonly PaymentChargeService $charges,
        private readonly MobileMoneyProviderRegistry $providers,
    ) {
    }

    /**
     * GET /api/mobile/subscriptions/plans
     *
     * What the signed-in patient may buy, with a price for every interval the
     * plan is actually sold in.
     *
     * The intervals come from the plan's own price columns, so the app renders
     * whatever the catalogue offers and hardcodes nothing. The day a facility
     * plan gains a monthly price, the facility surface offers monthly — with
     * no app release and nothing left disagreeing.
     */
    public function plans(Request $request): JsonResponse
    {
        $this->applyLocale($request);
        $patient = $this->resolvePatient($request);

        $plans = $this->catalog->availableTo($patient);

        return response()->json([
            'data' => $plans->map(fn (SubscriptionPlan $p) => $this->catalog->toApiArray($p))->all(),
            'meta' => [
                'count'           => $plans->count(),
                'currency'        => 'XAF',
                'currency_symbol' => 'FCFA',
                // Payment availability travels with the price list so the app
                // can grey out "Subscribe" for the right reason rather than
                // failing at submit.
                'payments_available' => $this->providers->isAvailable(),
                'payments_simulated' => $this->providers->isAvailable() && $this->providers->isSimulated(),
                'methods' => array_map(fn (PaymentMethod $m) => [
                    'value' => $m->value,
                    'label' => $m->label(),
                ], PaymentMethod::cases()),
            ],
        ]);
    }

    /**
     * GET /api/mobile/subscriptions
     *
     * The patient's own subscriptions, newest first, with the computed truth
     * about whether each is paid up.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyLocale($request);
        $patient = $this->resolvePatient($request);

        $subscriptions = $this->access->forSubscriber($patient)->limit(50)->get();

        return response()->json([
            'data' => $subscriptions->map(fn (OrganizationSubscription $s) => $this->presenter->toApiArray($s))->all(),
            'meta' => [
                'count'   => $subscriptions->count(),
                // The one the app should show at the top, if there is one.
                'current' => ($current = $this->access->currentFor($patient)) !== null
                    ? $this->presenter->toApiArray($current)
                    : null,
            ],
        ]);
    }

    /**
     * POST /api/mobile/subscriptions
     *
     * Take out a subscription and start paying for it. Requires
     * `Idempotency-Key`.
     *
     * 201 — a new checkout was opened and a prompt sent to the handset.
     * 200 — this exact request had already been made; same charge, no second
     *       prompt.
     * 4xx — a refusal that names itself (`interval_not_offered`,
     *       `already_subscribed`, `payment_in_progress`, …).
     *
     * The response deliberately carries all three rows: the subscription (not
     * yet granting anything), the invoice (what is owed), and the charge (what
     * the app polls). A subscription is NOT active because this returned 201.
     */
    public function store(Request $request): JsonResponse
    {
        $this->applyLocale($request);
        $patient = $this->resolvePatient($request);

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));

        if ($idempotencyKey === '' || mb_strlen($idempotencyKey) > self::MAX_IDEMPOTENCY_KEY) {
            return $this->fail(PaymentException::idempotencyKeyRequired());
        }

        $validated = $request->validate([
            'plan'     => ['required', 'string', 'max:190'],
            // The vocabulary is checked here; whether THIS plan is sold that
            // way is decided by the plan's own prices, in the catalogue.
            'interval' => ['required', Rule::in(BillingInterval::values())],
            'method'   => ['required', Rule::in(PaymentMethod::values())],
            'msisdn'   => ['required', 'string', 'max:32'],
            // The price the app showed. INTEGER, never `numeric`.
            'amount_minor' => ['sometimes', 'integer', 'min:1'],
        ]);

        try {
            $payer = Msisdn::parse($validated['msisdn']);
        } catch (InvalidArgumentException) {
            // The exception message is not echoed back: it was built from
            // caller input, and a 422 body is a poor place to reflect an
            // attacker's string at whoever reads the logs.
            return $this->fail(PaymentException::invalidMsisdn());
        }

        try {
            $plan = $this->catalog->findForSubscriber($patient, $validated['plan']);

            $checkout = $this->checkout->start(
                subscriber: $patient,
                plan: $plan,
                interval: $validated['interval'],
                method: PaymentMethod::from($validated['method']),
                payer: $payer,
                // From the bearer token, via the request attributes. There is
                // no branch in which this comes from the body.
                payerPatientId: $patient->getKey(),
                idempotencyKey: $idempotencyKey,
                expectedAmountMinor: isset($validated['amount_minor']) ? (int) $validated['amount_minor'] : null,
            );
        } catch (PaymentException $e) {
            // SubscriptionException extends PaymentException, so both refusal
            // vocabularies render through one path.
            return $this->fail($e);
        }

        return response()->json(
            [
                'data' => $checkout->toApiArray(),
                'meta' => [
                    'replayed'           => $checkout->replayed,
                    'poll_after_seconds' => 2,
                ],
            ],
            $checkout->replayed ? 200 : 201,
        );
    }

    /**
     * GET /api/mobile/subscriptions/{subscription}
     *
     * The app's polling endpoint after a submit.
     *
     * While a charge is still open this asks the provider for the
     * authoritative answer, then settles the subscription from the invoice —
     * so one response updates the whole screen, and a subscriber whose payment
     * has just gone through sees `is_paid_up: true` on the next poll rather
     * than waiting for a callback that may never arrive.
     *
     * The settlement call here is a BACKSTOP, not the primary path: it is
     * idempotent, and the invoice observer has usually already done the work.
     */
    public function show(Request $request, string $subscription): JsonResponse
    {
        $this->applyLocale($request);
        $patient = $this->resolvePatient($request);

        $model = $this->findOrFail($patient, $subscription);

        $invoice = $this->latestInvoiceFor($model);
        $charge  = $invoice?->pendingCharge();

        if ($charge !== null) {
            $charge  = $this->charges->syncFromProvider($charge);
            $invoice = $invoice?->fresh();
        }

        if ($invoice !== null) {
            $this->settlement->settleFromInvoice($invoice);
        }

        return response()->json([
            'data' => [
                'subscription' => $this->presenter->toApiArray($model->refresh()->load('plan')),
                'invoice'      => $invoice?->fresh()?->toApiArray(),
                'charge'       => $invoice?->fresh()?->charges()
                    ->latest('created_at')
                    ->first()?->toApiArray(),
            ],
        ]);
    }

    // ── Lookup ──────────────────────────────────────────────────────────────

    /**
     * The patient's own subscription, or a 404.
     *
     * The ownership test is in the WHERE clause, and the id is shape-checked
     * first: Postgres rejects `uuid = 'foo'` with SQLSTATE 22P02, which Laravel
     * surfaces as a 500 carrying the failing SQL and the table name. A
     * malformed id is simply an id that does not exist, and must be
     * indistinguishable from any other one that does not.
     */
    private function findOrFail(Patient $patient, string $subscriptionId): OrganizationSubscription
    {
        abort_unless($this->isUuid($subscriptionId), 404, __('subscriptions.errors.not_found'));

        $subscription = OrganizationSubscription::query()
            ->with('plan')
            ->where('subscriber_type', SubscriptionPlanCatalog::SUBSCRIBER_PATIENT)
            ->where('subscriber_id', $patient->getKey())
            ->whereKey($subscriptionId)
            ->first();

        abort_if($subscription === null, 404, __('subscriptions.errors.not_found'));

        return $subscription;
    }

    /** The invoice raised for this subscription — the newest, if it was retried. */
    private function latestInvoiceFor(OrganizationSubscription $subscription): ?MobileInvoice
    {
        return MobileInvoice::query()
            ->where('subject_type', SubscriptionCheckoutService::SUBJECT_TYPE)
            ->where('subject_id', $subscription->id)
            // A paid invoice outranks a newer unpaid one: it is the one that
            // explains the period the subscriber currently has.
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [PaymentStatus::Paid->value])
            ->latest('created_at')
            ->first();
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }

    // ── Request context ─────────────────────────────────────────────────────

    /**
     * The authenticated patient, and only ever the authenticated patient.
     * Set by `AuthenticateMobilePatient` from the bearer token.
     */
    private function resolvePatient(Request $request): Patient
    {
        $patientId = $request->attributes->get('patient_id');

        abort_if(! is_string($patientId) || $patientId === '', 401, 'Unauthenticated.');

        $patient = Patient::find($patientId);

        abort_if($patient === null, 401, 'Unauthenticated.');

        return $patient;
    }

    /**
     * Speak the subscriber's language.
     *
     * `SetLocale` is registered on the `web` group only, so an API request
     * would otherwise always render English — and being told the price of
     * something in the wrong language is exactly the wrong moment to fall
     * back. Accepts `?lang=` and `Accept-Language`, restricted to the two
     * locales kept 1:1.
     */
    private function applyLocale(Request $request): void
    {
        $candidates = [
            $request->query('lang'),
            $request->getPreferredLanguage(['en', 'fr']),
        ];

        foreach ($candidates as $candidate) {
            $locale = is_string($candidate) ? strtolower(substr($candidate, 0, 2)) : null;

            if (in_array($locale, ['en', 'fr'], true)) {
                App::setLocale($locale);

                return;
            }
        }
    }

    /** Render a domain refusal with its own status and translated message. */
    private function fail(PaymentException $e): JsonResponse
    {
        return response()->json($e->toPayload(), $e->status);
    }
}
