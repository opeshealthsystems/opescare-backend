<?php

namespace Tests\Feature\Payments;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Billing\BillingDeskService;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Exceptions\PaymentException;
use App\Services\Payments\InvoiceService;
use App\Services\Payments\PaymentChargeService;
use App\Support\Payments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Owner decision 2026-10-20: production has no live MTN MoMo / Orange Money
 * provider, so every pay-online control is hidden behind "pay at the
 * facility" until one is configured. Cash at the desk keeps working, and the
 * mobile API reports the state cleanly instead of erroring.
 */
class OnlinePaymentsAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** Production with no live credentials: the simulator is refused. */
    private function production(): void
    {
        config(['app.env' => 'production']);
    }

    private function cashierWithInvoice(): array
    {
        $role     = Role::where('name', 'cashier')->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $cashier  = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create([
            'user_id' => $cashier->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        $desk    = app(BillingDeskService::class);
        $patient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $invoice = $desk->createInvoice($facility->id, $patient, [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $cashier->id);
        if ($invoice->status === 'draft') {
            $desk->issue($invoice);
        }
        $desk->openSession($facility->id, $cashier->id, 0);

        return [$cashier, $facility, $invoice->fresh()];
    }

    public function test_the_helper_follows_the_environment(): void
    {
        $this->assertTrue(Payments::onlineAvailable(), 'testing may use the simulator');
        $this->production();
        $this->assertFalse(Payments::onlineAvailable(), 'production with no live provider cannot take money online');
    }

    public function test_billing_desk_hides_mobile_money_and_keeps_cash_on_production(): void
    {
        $this->production();
        [$cashier, $facility, $invoice] = $this->cashierWithInvoice();
        $as = fn () => $this->actingAs($cashier)->withSession(['active_facility_id' => $facility->id]);

        $as()->get('/facility/billing/'.$invoice->id)->assertOk()
            ->assertSee(__('billing_desk.momo.offline_note'))
            ->assertDontSee('value="'.PaymentMethod::MtnMomo->value.'"', false)
            ->assertDontSee('id="pay-phone"', false);

        // A forged mobile money post is refused with the translated note.
        $as()->from('/facility/billing/'.$invoice->id)
            ->post('/facility/billing/'.$invoice->id.'/pay', ['amount_xaf' => '3000', 'method' => PaymentMethod::MtnMomo->value, 'phone' => '670000000'])
            ->assertRedirect()->assertSessionHas('error', __('billing_desk.refuse.momo_unavailable'));

        // Cash still works.
        $as()->post('/facility/billing/'.$invoice->id.'/pay', ['amount_xaf' => '3000', 'method' => 'cash'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(0, (int) round((float) $invoice->fresh()->balance_amount));
    }

    public function test_billing_desk_offers_mobile_money_where_a_provider_can_take_it(): void
    {
        [$cashier, $facility, $invoice] = $this->cashierWithInvoice();

        $this->actingAs($cashier)->withSession(['active_facility_id' => $facility->id])
            ->get('/facility/billing/'.$invoice->id)->assertOk()
            ->assertSee('value="'.PaymentMethod::MtnMomo->value.'"', false)
            ->assertDontSee(__('billing_desk.momo.offline_note'));
    }

    public function test_app_config_reports_the_flag_in_both_modes(): void
    {
        $this->getJson('/api/mobile/app-config')->assertOk()
            ->assertJsonPath('payments.online_available', true);

        $this->production();
        $this->getJson('/api/mobile/app-config')->assertOk()
            ->assertJsonPath('payments.online_available', false)
            ->assertJsonPath('payments.pay_at_facility_note', __('payments.errors.provider_unavailable'));
    }

    public function test_initiating_a_charge_on_production_is_a_clean_409_with_a_translated_message(): void
    {
        $patient = Patient::factory()->create(['identity_status' => 'verified', 'is_demo' => (bool) config('demo.enabled')]);
        $invoice = app(InvoiceService::class)->issueFor(
            patientId: $patient->id, amount: Money::ofMinor(1500, 'XAF'),
            description: 'Consultation', subjectType: 'self_service',
        );
        $this->production();

        try {
            app(PaymentChargeService::class)->start($invoice, PaymentMethod::MtnMomo, Msisdn::parse('670000001'), 'k-1', hash('sha256', 'fp'));
            $this->fail('Expected a PaymentException.');
        } catch (PaymentException $e) {
            $this->assertSame(409, $e->status);
            $this->assertSame('provider_unavailable', $e->errorCode);
            $this->assertSame(__('payments.errors.provider_unavailable'), $e->toPayload()['message']);
        }
    }
}
