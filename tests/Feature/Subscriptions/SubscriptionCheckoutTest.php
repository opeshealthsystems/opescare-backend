<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Facility;
use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Models\OrganizationSubscription;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\SubscriptionPlan;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Exceptions\PaymentException;
use App\Services\Subscriptions\Domain\BillingInterval;
use App\Services\Subscriptions\Domain\SubscriptionStatus;
use App\Services\Subscriptions\SubscriptionCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Taking out a subscription, and paying for it through the rail that already
 * exists.
 *
 * ── The prices used here are the real catalogue's shape, on purpose ────────
 * A patient plan carries a daily, a monthly and an annual price; a facility
 * plan carries only an annual one. Nothing in the code under test reads a list
 * of "who may choose what" — it reads the plan's price columns — so these
 * fixtures ARE the rule, and changing them changes what the tests observe.
 *
 * ── The amounts are whole francs, and the tests say so ─────────────────────
 * XAF has an ISO 4217 exponent of 0. 50 is fifty francs, not fifty centimes,
 * and `50 FCFA` is what a subscriber must read. The assertions below check the
 * stored integer, the rendered string, AND the absence of a decimal point,
 * because the old schema divided by 100 and a 100× error is silent in every
 * one of those three on its own.
 */
class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** Last digit 2 → the demo rail approves after its 6-second delay. */
    private const PAYING_MSISDN = '670000012';

    /** Last digit 0 → the demo rail declines immediately, insufficient funds. */
    private const DECLINING_MSISDN = '670000010';

    protected function setUp(): void
    {
        parent::setUp();

        // A fixed instant, so "one month from today" is an arithmetic fact and
        // not a flake on the 31st. Time still MOVES from here (travel() is
        // relative), which the demo provider's approval delay needs.
        $this->travelTo(Carbon::parse('2026-03-15 09:00:00'));
    }

    // ────────────────────────────────────────────────────────────────────────
    //  A patient pays for each of the three intervals
    // ────────────────────────────────────────────────────────────────────────

    public function test_a_patient_can_pay_daily_and_gets_exactly_one_day(): void
    {
        $this->assertPatientCanPay(
            interval: 'daily',
            expectedMinor: 50,
            expectedEnd: '2026-03-16',
        );
    }

    public function test_a_patient_can_pay_monthly_and_gets_exactly_one_month(): void
    {
        $this->assertPatientCanPay(
            interval: 'monthly',
            expectedMinor: 1_500,
            expectedEnd: '2026-04-15',
        );
    }

    public function test_a_patient_can_pay_yearly_and_gets_exactly_one_year(): void
    {
        $this->assertPatientCanPay(
            interval: 'annual',
            expectedMinor: 18_250,
            expectedEnd: '2027-03-15',
        );
    }

    /**
     * One interval, end to end: catalogue → checkout → prompt → settlement.
     *
     * @param  string  $expectedEnd  The period end, computed by hand from the
     *         fixed clock. End-exclusive: a day bought on the 15th runs out on
     *         the 16th, so the subscriber has the whole of the 15th.
     */
    private function assertPatientCanPay(string $interval, int $expectedMinor, string $expectedEnd): void
    {
        $plan    = $this->patientPlan();
        $patient = $this->patient();
        $headers = $this->bearerFor($patient);

        // ── The catalogue prices it, and prices it in whole francs ──────────
        $catalogue = $this->withHeaders($headers)->getJson('/api/mobile/subscriptions/plans');
        $catalogue->assertOk();

        $offered = collect($catalogue->json('data.0.intervals'))->keyBy('interval');
        $this->assertSame(
            ['daily', 'monthly', 'annual'],
            $offered->keys()->all(),
            'A patient plan is sold in all three intervals, because all three price columns are set.'
        );
        $this->assertSame($expectedMinor, $offered[$interval]['amount_minor']);

        // ── Take it out ────────────────────────────────────────────────────
        $response = $this->withHeaders($headers + ['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/mobile/subscriptions', [
                'plan'         => $plan->slug,
                'interval'     => $interval,
                'method'       => PaymentMethod::MtnMomo->value,
                'msisdn'       => self::PAYING_MSISDN,
                // The price the app displayed. If the catalogue had moved, the
                // rail would refuse rather than charge a different number.
                'amount_minor' => $expectedMinor,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.subscription.status', SubscriptionStatus::PendingPayment->value);
        $response->assertJsonPath('data.subscription.interval', $interval);
        $response->assertJsonPath('data.subscription.is_paid_up', false);
        $response->assertJsonPath('data.charge.status', 'pending');

        // ── Nothing has been granted yet ───────────────────────────────────
        $this->assertNull($response->json('data.subscription.current_period_start'));
        $this->assertNull($response->json('data.subscription.current_period_end'));

        // ── The amount that reached the invoice is the plan's own integer ───
        $this->assertPricedWithoutA100xError($plan, $interval, $expectedMinor, $response->json('data.invoice'));

        $subscriptionId = $response->json('data.subscription.id');

        // ── The handset answers ────────────────────────────────────────────
        $this->travel(7)->seconds();

        $settled = $this->withHeaders($headers)->getJson('/api/mobile/subscriptions/'.$subscriptionId);
        $settled->assertOk();

        $settled->assertJsonPath('data.charge.status', 'paid');
        $settled->assertJsonPath('data.invoice.status', 'paid');
        $settled->assertJsonPath('data.subscription.status', SubscriptionStatus::Active->value);
        $settled->assertJsonPath('data.subscription.is_paid_up', true);

        // The period is the one that was PAID for, dated from the payment.
        $settled->assertJsonPath('data.subscription.current_period_start', '2026-03-15');
        $settled->assertJsonPath('data.subscription.current_period_end', $expectedEnd);

        // What actually paid it, recorded on the subscription.
        $subscription = OrganizationSubscription::findOrFail($subscriptionId);
        $invoice      = MobileInvoice::where('subject_id', $subscriptionId)->firstOrFail();

        $this->assertSame($invoice->reference, $subscription->payment_reference);
        $this->assertSame(PaymentMethod::MtnMomo->value, $subscription->payment_method);
        $this->assertStringStartsWith('OC-SUB-', $subscription->payment_reference);

        // Mobile money holds no mandate. Nothing renews itself, and the record
        // must not pretend otherwise.
        $this->assertFalse((bool) $subscription->auto_renew);
    }

    /**
     * The price crossed every boundary as the same integer, and rendered as
     * francs.
     *
     * @param  array<string, mixed>  $invoice
     */
    private function assertPricedWithoutA100xError(
        SubscriptionPlan $plan,
        string $interval,
        int $expectedMinor,
        array $invoice,
    ): void {
        // 1. The catalogue column, untouched by arithmetic.
        $this->assertSame($expectedMinor, $plan->priceForInterval($interval));

        // 2. The invoice the rail will charge holds the SAME integer. Not a
        //    scaled copy of it — the same one.
        $this->assertSame($expectedMinor, $invoice['amount_minor']);
        $this->assertSame(
            $expectedMinor,
            MobileInvoice::whereKey($invoice['id'])->value('amount_minor'),
            'The stored amount must be the plan price, with no conversion on the way in.'
        );

        // 3. What the subscriber READS is whole francs. A 100× error would
        //    render "0.50 FCFA" or "50.00 FCFA" here; both carry a decimal
        //    point, and a currency with exponent 0 never does.
        $display = $invoice['amount_display'];
        $this->assertStringNotContainsString('.', $display);
        $this->assertStringNotContainsString(',', $display);
        $this->assertStringContainsString('FCFA', $display);
        $this->assertStringNotContainsString('XAF', $display, 'FCFA is what a Cameroonian reads; XAF is for the wire.');
        $this->assertSame(Money::ofMinor($expectedMinor)->format(), $display);

        // 4. And it is the digits of the price, grouped — nothing else.
        $digitsOnly = preg_replace('/\D+/', '', $display);
        $this->assertSame((string) $expectedMinor, $digitsOnly);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  A facility is annual only — and that comes from the catalogue
    // ────────────────────────────────────────────────────────────────────────

    public function test_a_facility_cannot_be_sold_a_daily_interval(): void
    {
        $facility = Facility::factory()->create();
        $plan     = $this->facilityPlan();
        $payer    = $this->patient();

        try {
            $this->checkout()->start(
                subscriber: $facility,
                plan: $plan,
                interval: 'daily',
                method: PaymentMethod::MtnMomo,
                payer: Msisdn::parse(self::PAYING_MSISDN),
                payerPatientId: (string) $payer->getKey(),
                idempotencyKey: (string) Str::uuid(),
            );

            $this->fail('A facility plan with no daily price must not be sellable daily.');
        } catch (PaymentException $e) {
            $this->assertSame('interval_not_offered', $e->errorCode);
            $this->assertSame(422, $e->status);

            // The refusal names what IS on offer, and it names it from the
            // plan's own price columns.
            $this->assertSame(['annual'], $e->context['intervals_offered']);
        }

        // A refusal writes nothing. Not a subscription anyone could later
        // mistake for real, not an invoice, not a charge.
        $this->assertSame(0, OrganizationSubscription::count());
        $this->assertSame(0, MobileInvoice::count());
        $this->assertSame(0, MobilePaymentCharge::count());
    }

    /**
     * The control for the experiment above.
     *
     * The daily refusal has to be caused by the plan's missing daily price, not
     * by a rule somewhere that says facilities cannot pay. The same facility,
     * the same service and the same rail sell the annual interval fine.
     */
    public function test_the_same_facility_can_be_sold_the_annual_interval_it_is_priced_for(): void
    {
        $facility = Facility::factory()->create();
        $plan     = $this->facilityPlan();
        $payer    = $this->patient();

        $checkout = $this->checkout()->start(
            subscriber: $facility,
            plan: $plan,
            interval: 'annual',
            method: PaymentMethod::MtnMomo,
            payer: Msisdn::parse(self::PAYING_MSISDN),
            payerPatientId: (string) $payer->getKey(),
            idempotencyKey: (string) Str::uuid(),
        );

        $this->assertSame('facility', $checkout->subscription->subscriber_type);
        $this->assertSame($facility->getKey(), $checkout->subscription->subscriber_id);
        $this->assertSame(25_000, $checkout->invoice->amount_minor);

        // Written so the entitlement middleware that already exists can find
        // it — that middleware looks subscriptions up by organization_id.
        $this->assertSame($facility->getKey(), $checkout->subscription->organization_id);

        // The charge is recorded against the natural person who paid, because
        // that is who the mobile money rail debits.
        $this->assertSame($payer->getKey(), $checkout->charge->patient_id);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  A failed charge grants nothing
    // ────────────────────────────────────────────────────────────────────────

    public function test_a_failed_payment_grants_no_period_at_all(): void
    {
        $plan    = $this->patientPlan();
        $patient = $this->patient();
        $headers = $this->bearerFor($patient);

        $response = $this->withHeaders($headers + ['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/mobile/subscriptions', [
                'plan'     => $plan->slug,
                'interval' => 'monthly',
                'method'   => PaymentMethod::MtnMomo->value,
                // A wallet with nothing in it. The demo rail refuses this one
                // synchronously, so there is no prompt and no waiting.
                'msisdn'   => self::DECLINING_MSISDN,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.charge.status', 'failed');
        $response->assertJsonPath('data.charge.failure_code', 'insufficient_funds');

        $subscriptionId = $response->json('data.subscription.id');

        // ── Nothing was granted, and nothing becomes granted with time ──────
        $this->travel(7)->seconds();

        $after = $this->withHeaders($headers)->getJson('/api/mobile/subscriptions/'.$subscriptionId);
        $after->assertOk();
        $after->assertJsonPath('data.subscription.status', SubscriptionStatus::PendingPayment->value);
        $after->assertJsonPath('data.subscription.is_paid_up', false);
        $after->assertJsonPath('data.subscription.current_period_start', null);
        $after->assertJsonPath('data.subscription.current_period_end', null);
        $after->assertJsonPath('data.subscription.days_remaining', 0);

        $subscription = OrganizationSubscription::findOrFail($subscriptionId);
        $this->assertNull($subscription->current_period_start);
        $this->assertNull($subscription->current_period_end);
        $this->assertNull($subscription->payment_reference);
        $this->assertFalse(app(\App\Services\Subscriptions\SubscriptionAccessService::class)
            ->isCurrentlyPaid($subscription));

        // The invoice stays OPEN on purpose: a declined wallet is not an
        // unpayable bill, and the subscriber must be able to try another one.
        $this->assertSame(
            'pending',
            MobileInvoice::where('subject_id', $subscriptionId)->firstOrFail()->status->value,
        );

        // And the list endpoint tells the same story.
        $list = $this->withHeaders($headers)->getJson('/api/mobile/subscriptions');
        $list->assertOk();
        $list->assertJsonPath('meta.current.is_paid_up', false);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  A double submit charges once
    // ────────────────────────────────────────────────────────────────────────

    public function test_a_retried_submit_charges_once_and_creates_one_subscription(): void
    {
        $plan    = $this->patientPlan();
        $patient = $this->patient();
        $headers = $this->bearerFor($patient);

        $key  = (string) Str::uuid();
        $body = [
            'plan'         => $plan->slug,
            'interval'     => 'daily',
            'method'       => PaymentMethod::MtnMomo->value,
            'msisdn'       => self::PAYING_MSISDN,
            'amount_minor' => 50,
        ];

        $first = $this->withHeaders($headers + ['Idempotency-Key' => $key])
            ->postJson('/api/mobile/subscriptions', $body);
        $first->assertCreated();
        $first->assertJsonPath('meta.replayed', false);

        // The same submit again — a tap on a bad connection, or an app retry.
        $second = $this->withHeaders($headers + ['Idempotency-Key' => $key])
            ->postJson('/api/mobile/subscriptions', $body);
        $second->assertOk();
        $second->assertJsonPath('meta.replayed', true);

        // Same everything. Not a second prompt on the handset, not a second
        // subscription, not a second invoice.
        $this->assertSame($first->json('data.charge.id'), $second->json('data.charge.id'));
        $this->assertSame($first->json('data.invoice.id'), $second->json('data.invoice.id'));
        $this->assertSame($first->json('data.subscription.id'), $second->json('data.subscription.id'));

        $this->assertSame(1, MobilePaymentCharge::count(), 'One submit, retried, is one charge.');
        $this->assertSame(1, MobileInvoice::count(), 'A retry must not leave an orphan invoice behind.');
        $this->assertSame(1, OrganizationSubscription::count(), 'Nor an orphan subscription.');

        // And after it settles there is still exactly one of each, and exactly
        // one period.
        $this->travel(7)->seconds();
        $this->withHeaders($headers)
            ->getJson('/api/mobile/subscriptions/'.$first->json('data.subscription.id'))
            ->assertOk()
            ->assertJsonPath('data.subscription.is_paid_up', true);

        $this->assertSame(1, MobilePaymentCharge::where('status', 'paid')->count());
        $this->assertSame(1, OrganizationSubscription::count());
    }

    public function test_the_same_key_cannot_be_reused_to_buy_a_different_interval(): void
    {
        $plan    = $this->patientPlan();
        $patient = $this->patient();
        $headers = $this->bearerFor($patient);
        $key     = (string) Str::uuid();

        $this->withHeaders($headers + ['Idempotency-Key' => $key])
            ->postJson('/api/mobile/subscriptions', [
                'plan'     => $plan->slug,
                'interval' => 'daily',
                'method'   => PaymentMethod::MtnMomo->value,
                'msisdn'   => self::PAYING_MSISDN,
            ])
            ->assertCreated();

        // Same key, different purchase. That is a client bug and must be said
        // out loud, not silently answered with somebody's other charge.
        $this->withHeaders($headers + ['Idempotency-Key' => $key])
            ->postJson('/api/mobile/subscriptions', [
                'plan'     => $plan->slug,
                'interval' => 'annual',
                'method'   => PaymentMethod::MtnMomo->value,
                'msisdn'   => self::PAYING_MSISDN,
            ])
            ->assertStatus(409)
            ->assertJsonPath('error', 'idempotency_conflict');

        $this->assertSame(1, MobilePaymentCharge::count());
        $this->assertSame(1, OrganizationSubscription::count());
    }

    public function test_a_submit_without_an_idempotency_key_is_refused(): void
    {
        $plan    = $this->patientPlan();
        $patient = $this->patient();

        $this->withHeaders($this->bearerFor($patient))
            ->postJson('/api/mobile/subscriptions', [
                'plan'     => $plan->slug,
                'interval' => 'daily',
                'method'   => PaymentMethod::MtnMomo->value,
                'msisdn'   => self::PAYING_MSISDN,
            ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'idempotency_key_required');

        $this->assertSame(0, OrganizationSubscription::count());
    }

    public function test_a_patient_is_never_offered_a_facility_plan(): void
    {
        $this->patientPlan();
        $this->facilityPlan();

        $patient = $this->patient();
        $headers = $this->bearerFor($patient);

        $catalogue = $this->withHeaders($headers)->getJson('/api/mobile/subscriptions/plans');
        $catalogue->assertOk();
        $catalogue->assertJsonCount(1, 'data');
        $catalogue->assertJsonPath('data.0.audience', 'patient');

        // And quoting the facility plan's slug directly does not buy it.
        $this->withHeaders($headers + ['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/mobile/subscriptions', [
                'plan'     => 'facility-annual',
                'interval' => 'annual',
                'method'   => PaymentMethod::MtnMomo->value,
                'msisdn'   => self::PAYING_MSISDN,
            ])
            ->assertStatus(403)
            ->assertJsonPath('error', 'plan_not_for_subscriber');

        $this->assertSame(0, OrganizationSubscription::count());
    }

    public function test_one_patient_cannot_see_another_patients_subscription(): void
    {
        $plan  = $this->patientPlan();
        $mine  = $this->patient();
        $other = $this->patient();

        $created = $this->withHeaders($this->bearerFor($mine) + ['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/api/mobile/subscriptions', [
                'plan'     => $plan->slug,
                'interval' => 'daily',
                'method'   => PaymentMethod::MtnMomo->value,
                'msisdn'   => self::PAYING_MSISDN,
            ]);
        $created->assertCreated();

        // A 404, not a 403: a 403 would confirm the id exists.
        $this->withHeaders($this->bearerFor($other))
            ->getJson('/api/mobile/subscriptions/'.$created->json('data.subscription.id'))
            ->assertNotFound();
    }

    public function test_the_billing_vocabulary_matches_the_plan_catalogues(): void
    {
        // The two halves of the same vocabulary. If a fourth interval is ever
        // added to the catalogue and not to the billing enum, a subscriber
        // could be sold a period nobody can compute the end of.
        $this->assertSame(
            SubscriptionPlan::INTERVALS,
            BillingInterval::values(),
            'BillingInterval and SubscriptionPlan::INTERVALS must stay the same list.'
        );
    }

    // ── Fixtures ────────────────────────────────────────────────────────────

    private function checkout(): SubscriptionCheckoutService
    {
        return app(SubscriptionCheckoutService::class);
    }

    /**
     * A patient plan: sold daily, monthly and yearly. The three prices are what
     * make all three intervals choosable — there is no other switch.
     */
    private function patientPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name'                => 'Patient',
            'slug'                => 'patient-test',
            'audience'            => 'patient',
            'billing_cycle'       => 'daily',
            'price_minor'         => 50,
            'daily_price_minor'   => 50,
            'monthly_price_minor' => 1_500,
            'annual_price_minor'  => 18_250,
            'currency'            => 'XAF',
            'description'         => 'Your Health ID, your record, and everything the app does with it.',
            'is_active'           => true,
            'is_public'           => true,
            'sort_order'          => 0,
        ]);
    }

    /** A facility plan: an annual price and nothing else. */
    private function facilityPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name'                => 'Pharmacy',
            'slug'                => 'facility-annual',
            'audience'            => 'facility',
            'billing_cycle'       => 'annual',
            'price_minor'         => 25_000,
            'daily_price_minor'   => null,
            'monthly_price_minor' => null,
            'annual_price_minor'  => 25_000,
            'currency'            => 'XAF',
            'description'         => 'Dispensing desk, prescription lookup and stock visibility.',
            'is_active'           => true,
            'is_public'           => true,
            'sort_order'          => 1,
        ]);
    }

    private function patient(): Patient
    {
        return Patient::factory()->create([
            'identity_status' => 'verified',
            'is_demo'         => (bool) config('demo.enabled'),
        ]);
    }

    /** A valid mobile bearer, exactly as MobileAuthController issues one. */
    private function bearerFor(Patient $patient): array
    {
        $raw = 'pat_'.Str::random(40);

        PatientAccessToken::create([
            'patient_id'        => $patient->id,
            'token_hash'        => Hash::make($raw),
            'token_prefix'      => substr($raw, 0, 12),
            'token_lookup_hash' => hash('sha256', $raw),
            'expires_at'        => now()->addDays(30),
        ]);

        return ['Authorization' => "Bearer {$raw}"];
    }
}
