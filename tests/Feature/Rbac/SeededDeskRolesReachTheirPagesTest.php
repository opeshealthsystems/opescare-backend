<?php

namespace Tests\Feature\Rbac;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Services\Billing\BillingDeskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * refund_approver, wallet_ops, appointment_coordinator and queue_manager were
 * named in config/staff_capabilities.php and given demo accounts, but had no
 * roles row until 2026-10-19, so their users carried role_id = null and could
 * open nothing. Each must now be a real role that reaches its own desk.
 */
class SeededDeskRolesReachTheirPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName, ?Facility $facility = null): array
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

    private function as(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    public function test_the_four_roles_are_seeded_with_a_dashboard_profile(): void
    {
        foreach (['refund_approver', 'wallet_ops', 'appointment_coordinator', 'queue_manager'] as $name) {
            $role = Role::where('name', $name)->first();
            $this->assertNotNull($role, "$name is not seeded");
            $this->assertSame('receptionist', $role->dashboard_profile_key);
            $this->assertNotNull($role->account_category_id);
        }
    }

    public function test_refund_approver_reaches_the_refunds_desk_and_signs_a_refund(): void
    {
        [$cashier, $facility] = $this->staff('cashier');
        [$approver]           = $this->staff('refund_approver', $facility);

        $desk    = app(BillingDeskService::class);
        $invoice = $desk->createInvoice($facility->id, Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]), [
            ['description' => 'Consultation', 'unit_price_xaf' => 3000, 'quantity' => 1],
        ], $cashier->id);
        $payment = $desk->recordCashPayment($invoice, 3000, $cashier->id, $desk->openSession($facility->id, $cashier->id, 5000));

        $this->as($cashier, $facility)
            ->post('/facility/billing/payments/'.$payment->id.'/refund', ['amount_xaf' => '500', 'reason' => 'Charged twice'])
            ->assertSessionHasNoErrors();
        $refund = Refund::firstOrFail();

        $this->as($approver, $facility)->get(route('portals.billing.refunds'))->assertOk();

        $this->as($approver, $facility)
            ->post(route('portals.billing.refund.approve', $refund->id))
            ->assertSessionHasNoErrors();
        $this->assertSame('processed', $refund->fresh()->status);
    }

    public function test_wallet_ops_reaches_its_billing_pages(): void
    {
        [$user, $facility] = $this->staff('wallet_ops');

        foreach (['portals.billing.index', 'portals.billing.sessions', 'portals.billing.refunds'] as $route) {
            $this->as($user, $facility)->get(route($route))->assertOk();
        }
    }

    public function test_front_desk_roles_reach_queue_and_appointments(): void
    {
        foreach (['appointment_coordinator', 'queue_manager'] as $name) {
            [$user, $facility] = $this->staff($name);

            $this->as($user, $facility)->get(route('portals.queue.index'))->assertOk();
            $this->as($user, $facility)->get(route('portals.staff.appointments'))->assertOk();
        }
    }

    public function test_the_doctor_family_reaches_the_imaging_desk_from_its_sidebar(): void
    {
        foreach (['doctor', 'resident', 'specialist', 'medical_supervisor'] as $name) {
            [$user, $facility] = $this->staff($name);

            $this->as($user, $facility)->get(route('portals.imaging.index'))->assertOk();
            $this->as($user, $facility)->get(route('portals.staff'))
                ->assertOk()
                ->assertSee(route('portals.imaging.index'), false);
        }
    }
}
