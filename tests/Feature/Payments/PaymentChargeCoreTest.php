<?php

namespace Tests\Feature\Payments;

use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Models\Patient;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Domain\PaymentStatus;
use App\Services\Payments\Domain\ProviderPaymentStatus;
use App\Services\Payments\Dto\CollectionResult;
use App\Services\Payments\Exceptions\PaymentException;
use App\Services\Payments\InvoiceService;
use App\Services\Payments\PaymentChargeService;
use App\Services\Payments\Providers\DemoPaymentProvider;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The payment core (PaymentChargeService + the forward-only trigger + the
 * partial unique indexes) had no direct tests: only the subscription checkout
 * exercised it, end to end. These pin each guarantee the class docblock makes.
 *
 * Demo rail outcomes are a pure function of the payer's last digit:
 *   2 → approves after 6 s, 1 → declines after 6 s, 0 → declines at once,
 *   5 → never answers.
 */
class PaymentChargeCoreTest extends TestCase
{
    use RefreshDatabase;

    private const PAYS     = '670000012';
    private const DECLINES = '670000011';
    private const STALLS   = '670000015';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-03-15 09:00:00'));
    }

    // ── Idempotency ─────────────────────────────────────────────────────────

    public function test_double_tap_with_the_same_key_returns_the_same_charge_and_opens_one_row(): void
    {
        $invoice = $this->invoice();

        $a = $this->start($invoice, self::PAYS, 'key-1', 'fp-1');
        $b = $this->start($invoice, self::PAYS, 'key-1', 'fp-1');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, MobilePaymentCharge::where('invoice_id', $invoice->id)->count());
    }

    public function test_reusing_a_key_for_a_different_request_is_a_conflict_not_a_replay(): void
    {
        $invoice = $this->invoice();
        $this->start($invoice, self::PAYS, 'key-1', 'fp-1');

        $e = $this->expectPaymentError(fn () => $this->start($invoice, self::PAYS, 'key-1', 'fp-DIFFERENT'));

        $this->assertSame('idempotency_conflict', $e->errorCode);
        $this->assertSame(409, $e->status);
    }

    public function test_double_tap_with_different_keys_hits_the_pending_index_and_reports_the_charge_in_flight(): void
    {
        $invoice = $this->invoice();
        $first = $this->start($invoice, self::PAYS, 'key-1', 'fp-1');

        $e = $this->expectPaymentError(fn () => $this->start($invoice, self::PAYS, 'key-2', 'fp-2'));

        $this->assertSame('payment_in_progress', $e->errorCode);
        $this->assertSame($first->id, $e->context['charge']['id']);
        $this->assertSame(1, MobilePaymentCharge::where('invoice_id', $invoice->id)->count());
    }

    public function test_a_displayed_amount_that_differs_from_the_invoice_charges_nothing(): void
    {
        $invoice = $this->invoice(1500);

        $e = $this->expectPaymentError(fn () => $this->start($invoice, self::PAYS, 'k', 'f', 1499));

        $this->assertSame('amount_mismatch', $e->errorCode);
        $this->assertSame(0, MobilePaymentCharge::count());
    }

    public function test_a_paid_invoice_cannot_be_charged_again(): void
    {
        $invoice = $this->invoice();
        $charge = $this->start($invoice, self::PAYS, 'key-1', 'fp-1');
        $this->travel(7)->seconds();
        $this->service()->syncFromProvider($charge->refresh());

        $e = $this->expectPaymentError(fn () => $this->start($invoice->refresh(), self::PAYS, 'key-2', 'fp-2'));

        $this->assertSame('invoice_already_paid', $e->errorCode);
    }

    // ── Callbacks: 202 always, and the body never settles anything ─────────

    public function test_a_forged_callback_without_a_valid_token_is_accepted_flat_and_changes_nothing(): void
    {
        $charge = $this->start($this->invoice(), self::PAYS, 'k', 'f');
        $this->travel(7)->seconds();

        $this->postJson('/api/mobile/payments/callbacks/demo', [
            'order_id'    => $charge->id,
            'status'      => 'SUCCESSFUL',
            'notif_token' => str_repeat('a', 64),
        ])->assertStatus(202)->assertExactJson(['status' => 'accepted']);

        $this->assertSame(PaymentStatus::Pending, $charge->refresh()->status);
    }

    public function test_a_validly_signed_callback_claiming_success_is_re_polled_and_the_provider_answer_wins(): void
    {
        // The rail will DECLINE this payer. The callback body says SUCCESS.
        $charge = $this->start($this->invoice(), self::DECLINES, 'k', 'f');
        $this->travel(7)->seconds();

        $this->postJson('/api/mobile/payments/callbacks/demo', [
            'order_id'    => $charge->id,
            'status'      => 'SUCCESSFUL',
            'notif_token' => DemoPaymentProvider::callbackToken($charge->id),
        ])->assertStatus(202);

        $charge->refresh();
        $this->assertSame(PaymentStatus::Failed, $charge->status);
        $this->assertSame(PaymentStatus::Pending, $charge->invoice->status, 'A declined charge must not close the invoice.');
    }

    public function test_a_signed_callback_before_the_provider_has_answered_leaves_the_charge_pending(): void
    {
        $charge = $this->start($this->invoice(), self::PAYS, 'k', 'f');

        $this->postJson('/api/mobile/payments/callbacks/demo', [
            'order_id'    => $charge->id,
            'status'      => 'SUCCESSFUL',
            'notif_token' => DemoPaymentProvider::callbackToken($charge->id),
        ])->assertStatus(202);

        $this->assertSame(PaymentStatus::Pending, $charge->refresh()->status);
    }

    public function test_a_signed_callback_after_approval_settles_charge_and_invoice_exactly_once(): void
    {
        $charge = $this->start($this->invoice(), self::PAYS, 'k', 'f');
        $this->travel(7)->seconds();

        $payload = [
            'order_id'    => $charge->id,
            'status'      => 'SUCCESSFUL',
            'notif_token' => DemoPaymentProvider::callbackToken($charge->id),
        ];
        $this->postJson('/api/mobile/payments/callbacks/demo', $payload)->assertStatus(202);
        $this->postJson('/api/mobile/payments/callbacks/demo', $payload)->assertStatus(202);

        $charge->refresh();
        $this->assertSame(PaymentStatus::Paid, $charge->status);
        $this->assertSame(PaymentStatus::Paid, $charge->invoice->status);
        $this->assertSame(1, DB::table('mobile_payment_events')
            ->where('invoice_id', $charge->invoice_id)->where('to_status', 'paid')->where('note', 'invoice settled')->count(),
            'A replayed callback must not settle the invoice a second time.');
    }

    public function test_a_malformed_or_unknown_order_id_is_202_never_500(): void
    {
        $this->postJson('/api/mobile/payments/callbacks/demo', [
            'order_id' => "x' OR 1=1 --", 'notif_token' => DemoPaymentProvider::callbackToken("x' OR 1=1 --"),
        ])->assertStatus(202);

        $ghost = (string) Str::uuid();
        $this->postJson('/api/mobile/payments/callbacks/demo', [
            'order_id' => $ghost, 'notif_token' => DemoPaymentProvider::callbackToken($ghost),
        ])->assertStatus(202);
    }

    // ── Settlement under the lock ───────────────────────────────────────────

    public function test_settlement_re_reads_after_the_lock_so_a_stale_copy_cannot_overwrite_a_settled_charge(): void
    {
        $charge = $this->start($this->invoice(), self::PAYS, 'k', 'f');
        $stale = MobilePaymentCharge::find($charge->id); // read before the "other worker" settles

        $this->travel(7)->seconds();
        $this->service()->syncFromProvider($charge->refresh()); // worker A: paid

        $sql = [];
        DB::listen(function ($q) use (&$sql) { $sql[] = strtolower($q->sql); });

        // Worker B arrives late with a stale, still-pending model and a failure.
        $result = $this->service()->applyResult($stale, new CollectionResult(
            status: ProviderPaymentStatus::Failed,
            providerReference: $stale->provider_reference,
            providerStatusCode: 'LATE',
            failureCode: CollectionResult::FAILURE_REJECTED_BY_PAYER,
        ), 'callback');

        $this->assertSame(PaymentStatus::Paid, $result->status);
        $this->assertSame(PaymentStatus::Paid, $charge->refresh()->status);
        $this->assertNotEmpty(array_filter($sql, fn ($s) => str_contains($s, 'for update')), 'Settlement must take a row lock.');
    }

    public function test_unknown_provider_answers_never_become_failures(): void
    {
        $charge = $this->start($this->invoice(), self::STALLS, 'k', 'f');

        $out = $this->service()->applyResult($charge, CollectionResult::unknown($charge->provider_reference), 'poll');

        $this->assertSame(PaymentStatus::Pending, $out->status);
    }

    public function test_a_stalled_charge_expires_and_frees_the_invoice_for_a_retry(): void
    {
        $invoice = $this->invoice();
        $charge = $this->start($invoice, self::STALLS, 'key-1', 'fp-1');

        $this->travel(PaymentChargeService::CHARGE_TTL_SECONDS + 5)->seconds();
        $this->service()->expireLapsed();

        $this->assertSame(PaymentStatus::Expired, $charge->refresh()->status);
        $retry = $this->start($invoice->refresh(), self::PAYS, 'key-2', 'fp-2');
        $this->assertSame(PaymentStatus::Pending, $retry->status);
    }

    // ── Refund terminality ─────────────────────────────────────────────────

    public function test_a_refunded_charge_is_terminal_for_the_service_and_for_the_database(): void
    {
        $charge = $this->paidCharge();
        DB::table('mobile_payment_charges')->where('id', $charge->id)->update(['status' => 'refunded']);
        $charge->refresh();

        $this->assertTrue($charge->status->isTerminal());

        $out = $this->service()->applyResult($charge, new CollectionResult(
            status: ProviderPaymentStatus::Succeeded,
            providerReference: $charge->provider_reference,
            providerStatusCode: 'REPLAY',
        ), 'callback');
        $this->assertSame(PaymentStatus::Refunded, $out->status);

        $this->assertTriggerRejects(fn () => DB::table('mobile_payment_charges')
            ->where('id', $charge->id)->update(['status' => 'paid']));
    }

    // ── The trigger: the backstop if the service is ever wrong ─────────────

    public function test_trigger_rejects_backward_and_sideways_transitions(): void
    {
        $charge = $this->paidCharge();

        $this->assertTriggerRejects(fn () => DB::table('mobile_payment_charges')->where('id', $charge->id)->update(['status' => 'pending']));
        $this->assertTriggerRejects(fn () => DB::table('mobile_payment_charges')->where('id', $charge->id)->update(['status' => 'failed']));
        $this->assertTriggerRejects(fn () => DB::table('mobile_invoices')->where('id', $charge->invoice_id)->update(['status' => 'pending']));

        $declined = $this->start($this->invoice(), '670000010', 'k2', 'f2'); // fails at once
        $this->assertSame(PaymentStatus::Failed, $declined->refresh()->status);
        $this->assertTriggerRejects(fn () => DB::table('mobile_payment_charges')->where('id', $declined->id)->update(['status' => 'paid']));
    }

    public function test_trigger_rejects_any_change_to_amount_currency_or_owner(): void
    {
        $invoice = $this->invoice(1500);
        $other = Patient::factory()->create();

        $this->assertTriggerRejects(fn () => DB::table('mobile_invoices')->where('id', $invoice->id)->update(['amount_minor' => 1]));
        $this->assertTriggerRejects(fn () => DB::table('mobile_invoices')->where('id', $invoice->id)->update(['currency' => 'EUR']));
        $this->assertTriggerRejects(fn () => DB::table('mobile_invoices')->where('id', $invoice->id)->update(['patient_id' => $other->id]));

        $this->assertSame(1500, (int) MobileInvoice::whereKey($invoice->id)->value('amount_minor'));
    }

    public function test_an_invoice_can_never_hold_two_paid_charges(): void
    {
        $charge = $this->paidCharge();

        $row = (array) DB::table('mobile_payment_charges')->where('id', $charge->id)->first();
        $row['id'] = (string) Str::uuid();
        $row['idempotency_key'] = 'second';
        $row['provider_reference'] = null;

        $this->assertTriggerRejects(fn () => DB::table('mobile_payment_charges')->insert($row), '23505');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function service(): PaymentChargeService
    {
        return app(PaymentChargeService::class);
    }

    private function invoice(int $amount = 1500): MobileInvoice
    {
        $patient = Patient::factory()->create(['identity_status' => 'verified', 'is_demo' => (bool) config('demo.enabled')]);

        return app(InvoiceService::class)->issueFor(
            patientId: $patient->id,
            amount: Money::ofMinor($amount, 'XAF'),
            description: 'Consultation',
            subjectType: 'self_service',
        );
    }

    private function start(MobileInvoice $invoice, string $msisdn, string $key, string $fp, ?int $expected = null): MobilePaymentCharge
    {
        // The real fingerprint is a SHA-256 hex digest and the column is
        // char(64): PostgreSQL blank-pads a shorter literal, so a raw label
        // like 'fp-1' would never compare equal on replay. Hash it, as the
        // controller does.
        return $this->service()->start($invoice, PaymentMethod::MtnMomo, Msisdn::parse($msisdn), $key, hash('sha256', $fp), $expected);
    }

    private function paidCharge(): MobilePaymentCharge
    {
        $charge = $this->start($this->invoice(), self::PAYS, 'paid-'.Str::random(6), 'fp');
        $this->travel(7)->seconds();
        $charge = $this->service()->syncFromProvider($charge->refresh());
        $this->assertSame(PaymentStatus::Paid, $charge->status);

        return $charge;
    }

    private function expectPaymentError(callable $fn): PaymentException
    {
        try {
            $fn();
        } catch (PaymentException $e) {
            return $e;
        }
        $this->fail('Expected a PaymentException.');
    }

    /** Runs $fn inside a savepoint so the rejected statement does not poison the test transaction. */
    private function assertTriggerRejects(callable $fn, string $sqlState = '23514'): void
    {
        DB::beginTransaction();
        try {
            $fn();
            DB::rollBack();
            $this->fail('The database accepted a write it must reject.');
        } catch (QueryException $e) {
            DB::rollBack();
            $this->assertSame($sqlState, $e->errorInfo[0] ?? null, $e->getMessage());
        }
    }
}
