<?php

namespace Tests\Feature\Billing;

use App\Models\CashierSession;
use App\Models\Facility;
use App\Models\FacilityCatalogueItem;
use App\Models\FacilityRoleAssignment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Services\Billing\BillingDeskService;
use App\Services\Billing\BillingRefusal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The billing / cashier desk, and specifically the ways it is not allowed to
 * lose money or invent it.
 *
 *   - A price comes from the facility's catalogue or from the cashier's own
 *     typed figure. Totals are whole francs, integers, end to end.
 *   - A payment above the balance is refused with the exact balance.
 *   - An invoice is voided, never deleted. A refund needs a reason and a
 *     second person with the authority to sign it.
 *   - A cashier session's variance is written down, never corrected.
 *   - Another facility's invoice does not exist (404); a nurse is refused
 *     (403 role_forbidden).
 */
class BillingDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** A professional posted at one facility, exactly as the middleware leaves one. */
    private function staff(string $roleName = 'cashier', ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = $facility ?? Facility::factory()->create(['type' => 'hospital']);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function actingAsStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function patient(): Patient
    {
        return Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
    }

    private function catalogueItem(Facility $facility, int $price, string $name = 'Consultation générale'): FacilityCatalogueItem
    {
        return FacilityCatalogueItem::create([
            'facility_id' => $facility->id,
            'system'      => 'LOCAL',
            'code'        => 'CONS-'.strtoupper(substr(md5($name.$price), 0, 6)),
            'local_name'  => $name,
            'price_xaf'   => $price,
            'is_active'   => true,
        ]);
    }

    private function openSession(User $cashier, Facility $facility, int $float = 5000): CashierSession
    {
        return app(BillingDeskService::class)->openSession($facility->id, $cashier->id, $float);
    }

    // ── Invoices ─────────────────────────────────────────────────────────

    public function test_an_invoice_is_raised_from_catalogue_lines_with_integer_xaf_totals(): void
    {
        [$cashier, $facility] = $this->staff();
        $patient = $this->patient();
        $consult = $this->catalogueItem($facility, 1500);
        $lab     = $this->catalogueItem($facility, 4250, 'Test VIH');

        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing', [
                'patient_id' => $patient->id,
                'issue'      => '1',
                'lines'      => [
                    ['catalogue_item_id' => $consult->id, 'quantity' => 1],
                    ['catalogue_item_id' => $lab->id, 'quantity' => 2],
                    ['description' => 'Pansement', 'unit_price_xaf' => '700', 'quantity' => 3],
                    ['quantity' => 1], // a blank row the form always posts
                ],
            ])
            ->assertRedirect();

        $invoice = Invoice::where('patient_id', $patient->id)->firstOrFail();

        $this->assertSame('issued', $invoice->status);
        $this->assertSame($facility->id, $invoice->facility_id);
        $this->assertSame(1500 + 8500 + 2100, (int) round((float) $invoice->subtotal_amount));
        $this->assertSame(12100, (int) round((float) $invoice->balance_amount));
        $this->assertSame('12100.00', (string) $invoice->balance_amount, 'whole francs — no fractional part was invented');
        $this->assertCount(3, $invoice->items);
        $this->assertSame('LOCAL:'.$consult->code, $invoice->items->firstWhere('description', 'Consultation générale')->service_code);
        $this->assertNotEmpty($invoice->invoice_number);
    }

    public function test_a_catalogue_item_without_a_published_price_cannot_be_billed(): void
    {
        [$cashier, $facility] = $this->staff();
        $patient  = $this->patient();
        $unpriced = FacilityCatalogueItem::create([
            'facility_id' => $facility->id, 'system' => 'LOINC', 'code' => '718-7',
            'local_name' => 'Hémoglobine', 'price_xaf' => null, 'is_active' => true,
        ]);

        $this->actingAsStaff($cashier, $facility)
            ->from('/facility/billing/new')
            ->post('/facility/billing', [
                'patient_id' => $patient->id,
                'lines'      => [['catalogue_item_id' => $unpriced->id, 'quantity' => 1]],
            ])
            ->assertRedirect('/facility/billing/new')
            ->assertSessionHas('error', __('billing_desk.refuse.catalogue_item_unpriced', ['line' => 1]));

        $this->assertSame(0, Invoice::count());
    }

    public function test_a_free_line_without_a_price_is_refused_never_guessed(): void
    {
        [$cashier, $facility] = $this->staff();
        $patient = $this->patient();

        $this->actingAsStaff($cashier, $facility)
            ->from('/facility/billing/new')
            ->post('/facility/billing', [
                'patient_id' => $patient->id,
                'lines'      => [['description' => 'Radiographie', 'quantity' => 1]],
            ])
            ->assertRedirect('/facility/billing/new')
            ->assertSessionHas('error', __('billing_desk.refuse.price_required', ['line' => 1]));

        $this->assertSame(0, Invoice::count());
    }

    public function test_another_facilitys_catalogue_cannot_price_this_facilitys_invoice(): void
    {
        [$cashier, $facility] = $this->staff();
        $other   = Facility::factory()->create();
        $foreign = $this->catalogueItem($other, 999);

        $this->actingAsStaff($cashier, $facility)
            ->from('/facility/billing/new')
            ->post('/facility/billing', [
                'patient_id' => $this->patient()->id,
                'lines'      => [['catalogue_item_id' => $foreign->id, 'quantity' => 1]],
            ])
            ->assertSessionHas('error', __('billing_desk.refuse.catalogue_item_unknown', ['line' => 1]));

        $this->assertSame(0, Invoice::count());
    }

    // ── Payments ─────────────────────────────────────────────────────────

    public function test_a_cash_payment_yields_a_receipt_and_an_overpayment_is_refused(): void
    {
        [$cashier, $facility] = $this->staff();
        $desk    = app(BillingDeskService::class);
        $patient = $this->patient();
        $invoice = $desk->createInvoice($facility->id, $patient, [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $cashier->id);
        $this->openSession($cashier, $facility, 2000);

        // Over the balance: refused with the exact balance, nothing written.
        $this->actingAsStaff($cashier, $facility)
            ->from('/facility/billing/'.$invoice->id)
            ->post('/facility/billing/'.$invoice->id.'/pay', ['amount_xaf' => '3001', 'method' => 'cash'])
            ->assertRedirect('/facility/billing/'.$invoice->id)
            ->assertSessionHas('error', __('billing_desk.refuse.overpayment', [
                'amount' => "3\u{202F}001 FCFA", 'balance' => "3\u{202F}000 FCFA",
            ]));
        $this->assertSame(0, Payment::count());

        // Part payment.
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/'.$invoice->id.'/pay', ['amount_xaf' => '1000', 'method' => 'cash'])
            ->assertRedirect('/facility/billing/'.$invoice->id)
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame('part_paid', $invoice->status);
        $this->assertSame(2000, (int) round((float) $invoice->balance_amount));

        $payment = Payment::firstOrFail();
        $receipt = Receipt::where('payment_id', $payment->id)->firstOrFail();
        $this->assertSame('cash', $payment->method);
        $this->assertSame($cashier->id, $payment->cashier_id);
        $this->assertNotNull($payment->cashier_session_id);
        $this->assertStringStartsWith('RCP-', $receipt->receipt_number);

        // Now exactly the remainder: paid. One more franc would be refused.
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/'.$invoice->id.'/pay', ['amount_xaf' => '2001', 'method' => 'cash'])
            ->assertSessionHas('error');
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/'.$invoice->id.'/pay', ['amount_xaf' => '2000', 'method' => 'cash'])
            ->assertSessionHasNoErrors();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(0, (int) round((float) $invoice->balance_amount));
        $this->assertNotNull($invoice->paid_at);

        // The receipt page renders the number and the amount.
        $this->actingAsStaff($cashier, $facility)
            ->get('/facility/billing/receipts/'.$receipt->id)
            ->assertOk()
            ->assertSee($receipt->receipt_number)
            ->assertSee("1\u{202F}000 FCFA");
    }

    public function test_cash_cannot_be_taken_without_an_open_cashier_session(): void
    {
        [$cashier, $facility] = $this->staff();
        $invoice = app(BillingDeskService::class)->createInvoice($facility->id, $this->patient(), [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $cashier->id);

        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/'.$invoice->id.'/pay', ['amount_xaf' => '3000', 'method' => 'cash'])
            ->assertSessionHas('error', __('billing_desk.refuse.session_required'));

        $this->assertSame(0, Payment::count());
    }

    // ── Void ─────────────────────────────────────────────────────────────

    public function test_voiding_leaves_the_row_with_who_and_why_and_needs_a_reason(): void
    {
        [$cashier, $facility] = $this->staff();
        $invoice = app(BillingDeskService::class)->createInvoice($facility->id, $this->patient(), [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $cashier->id);

        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/'.$invoice->id.'/void', ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/'.$invoice->id.'/void', ['reason' => 'Raised against the wrong patient'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'id'          => $invoice->id,
            'status'      => 'void',
            'voided_by'   => $cashier->id,
            'void_reason' => 'Raised against the wrong patient',
        ]);
        $this->assertSame(1, Invoice::count(), 'voided, not deleted');

        // A void invoice takes no payment.
        $this->openSession($cashier, $facility);
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/'.$invoice->id.'/pay', ['amount_xaf' => '10', 'method' => 'cash'])
            ->assertSessionHas('error', __('billing_desk.refuse.not_payable', ['status' => 'void']));
    }

    public function test_an_invoice_with_money_against_it_cannot_be_voided(): void
    {
        [$cashier, $facility] = $this->staff();
        $desk    = app(BillingDeskService::class);
        $invoice = $desk->createInvoice($facility->id, $this->patient(), [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $cashier->id);
        $desk->recordCashPayment($invoice, 1000, $cashier->id, $this->openSession($cashier, $facility));

        $this->expectException(BillingRefusal::class);
        $desk->void($invoice, 'Trying to make a payment disappear', $cashier->id);
    }

    // ── Refunds ──────────────────────────────────────────────────────────

    public function test_a_refund_needs_a_reason_and_an_approver_and_the_requester_cannot_sign_it(): void
    {
        [$cashier, $facility] = $this->staff('cashier');
        [$finance]            = $this->staff('finance_manager', $facility);
        $desk    = app(BillingDeskService::class);
        $invoice = $desk->createInvoice($facility->id, $this->patient(), [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $cashier->id);
        $payment = $desk->recordCashPayment($invoice, 3000, $cashier->id, $this->openSession($cashier, $facility));

        // No reason: refused at validation.
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/payments/'.$payment->id.'/refund', ['amount_xaf' => '500', 'reason' => ''])
            ->assertSessionHasErrors('reason');
        $this->assertSame(0, Refund::count());

        // More than the payment holds: refused.
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/payments/'.$payment->id.'/refund', ['amount_xaf' => '3001', 'reason' => 'Charged twice'])
            ->assertSessionHas('error');
        $this->assertSame(0, Refund::count());

        // Requested properly: pending, with the requester's name.
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/payments/'.$payment->id.'/refund', ['amount_xaf' => '500', 'reason' => 'Charged twice'])
            ->assertSessionHasNoErrors();

        $refund = Refund::firstOrFail();
        $this->assertSame('pending', $refund->status);
        $this->assertSame($cashier->id, $refund->requested_by);

        // The cashier holds `billing` but not `refund_approve`: 403 role_forbidden.
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/refunds/'.$refund->id.'/approve')
            ->assertForbidden();
        $this->assertSame('pending', $refund->fresh()->status);

        // The finance manager signs it: recorded against payment and invoice.
        $this->actingAsStaff($finance, $facility)
            ->post('/facility/billing/refunds/'.$refund->id.'/approve')
            ->assertSessionHasNoErrors();

        $refund->refresh();
        $this->assertSame('processed', $refund->status);
        $this->assertSame($finance->id, $refund->approved_by);
        $this->assertNotNull($refund->approved_at);
        $this->assertSame(500, (int) round((float) $payment->fresh()->refunded_amount));

        $invoice->refresh();
        $this->assertSame(500, (int) round((float) $invoice->refunded_amount));
        $this->assertSame(500, (int) round((float) $invoice->balance_amount));
        $this->assertSame('part_paid', $invoice->status);

        // The payment row itself was never edited.
        $this->assertSame(3000, (int) round((float) $payment->fresh()->amount));
        $this->assertSame(1, Payment::count());
    }

    public function test_the_requester_cannot_approve_their_own_refund_even_with_the_authority(): void
    {
        [$finance, $facility] = $this->staff('finance_manager');
        $desk    = app(BillingDeskService::class);
        $invoice = $desk->createInvoice($facility->id, $this->patient(), [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $finance->id);
        $payment = $desk->recordCashPayment($invoice, 3000, $finance->id, $this->openSession($finance, $facility));
        $refund  = $desk->requestRefund($payment, 500, 'Charged twice', $finance->id);

        $this->actingAsStaff($finance, $facility)
            ->post('/facility/billing/refunds/'.$refund->id.'/approve')
            ->assertSessionHas('error', __('billing_desk.refuse.refund_self_approval'));

        $this->assertSame('pending', $refund->fresh()->status);
    }

    // ── Cashier sessions ─────────────────────────────────────────────────

    public function test_closing_a_session_records_the_variance_and_corrects_nothing(): void
    {
        [$cashier, $facility] = $this->staff();
        $desk    = app(BillingDeskService::class);
        $invoice = $desk->createInvoice($facility->id, $this->patient(), [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $cashier->id);

        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/sessions', ['float_xaf' => '5000'])
            ->assertRedirect('/facility/billing/sessions')
            ->assertSessionHasNoErrors();

        $session = CashierSession::firstOrFail();
        $this->assertSame('open', $session->status);

        // A second open session for the same cashier is refused.
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/sessions', ['float_xaf' => '0'])
            ->assertSessionHas('error', __('billing_desk.refuse.session_already_open'));

        $desk->recordCashPayment($invoice, 3000, $cashier->id, $session);

        // Expected 8 000; counted 7 500: the till is 500 short, and it says so.
        $this->actingAsStaff($cashier, $facility)
            ->post('/facility/billing/sessions/'.$session->id.'/close', ['counted_xaf' => '7500', 'note' => 'Coins missing'])
            ->assertRedirect('/facility/billing/sessions')
            ->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertSame('closed', $session->status);
        $this->assertSame(5000, (int) round((float) $session->opening_float_amount));
        $this->assertSame(3000, (int) round((float) $session->cash_total_amount));
        $this->assertSame(8000, (int) round((float) $session->expected_cash_amount));
        $this->assertSame(7500, (int) round((float) $session->counted_cash_amount));
        $this->assertSame(-500, (int) round((float) $session->variance_amount));
        $this->assertSame('Coins missing', $session->closing_note);

        // Nothing was adjusted to make the numbers agree.
        $this->assertSame(3000, (int) round((float) Payment::sum('amount')));
        $this->assertSame(3000, (int) round((float) $invoice->fresh()->paid_amount));

        // The page shows the variance rather than hiding it.
        $this->actingAsStaff($cashier, $facility)
            ->get('/facility/billing/sessions')
            ->assertOk()
            ->assertSee("-500 FCFA");
    }

    // ── Scope and roles ──────────────────────────────────────────────────

    public function test_another_facilitys_invoice_does_not_exist_for_this_desk(): void
    {
        [$cashier, $facility] = $this->staff();
        [$otherCashier, $otherFacility] = $this->staff('cashier');
        $foreign = app(BillingDeskService::class)->createInvoice($otherFacility->id, $this->patient(), [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $otherCashier->id);

        $this->actingAsStaff($cashier, $facility)->get('/facility/billing/'.$foreign->id)->assertNotFound();
        $this->actingAsStaff($cashier, $facility)->post('/facility/billing/'.$foreign->id.'/void', ['reason' => 'not mine'])->assertNotFound();
        $this->actingAsStaff($cashier, $facility)->post('/facility/billing/'.$foreign->id.'/pay', ['amount_xaf' => '1', 'method' => 'cash'])->assertNotFound();

        $this->actingAsStaff($cashier, $facility)
            ->get('/facility/billing')
            ->assertOk()
            ->assertDontSee($foreign->invoice_number);

        $this->assertSame('issued', $foreign->fresh()->status);
    }

    public function test_a_nurse_is_refused_with_role_forbidden_and_a_cashier_is_not(): void
    {
        [$nurse, $facility] = $this->staff('nurse');
        [$cashier]          = $this->staff('cashier', $facility);

        $this->actingAsStaff($nurse, $facility)->get('/facility/billing')->assertForbidden();
        $this->actingAsStaff($nurse, $facility)->getJson('/facility/billing')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden');

        $this->actingAsStaff($cashier, $facility)->get('/facility/billing')->assertOk();
        $this->actingAsStaff($cashier, $facility)->get('/facility/billing/new')->assertOk();
        $this->actingAsStaff($cashier, $facility)->get('/facility/billing/refunds')->assertOk();
        $this->actingAsStaff($cashier, $facility)->get('/facility/billing/sessions')->assertOk();
    }

    public function test_the_desk_is_linked_from_the_cashiers_sidebar(): void
    {
        [$cashier, $facility] = $this->staff('cashier');

        $this->actingAsStaff($cashier, $facility)
            ->get('/facility/billing')
            ->assertOk()
            ->assertSee(route('portals.billing.index'), false)
            ->assertSee(route('portals.billing.sessions'), false)
            ->assertSee(route('portals.billing.refunds'), false);
    }
}
