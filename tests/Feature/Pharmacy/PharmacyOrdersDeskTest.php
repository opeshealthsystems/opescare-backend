<?php

namespace Tests\Feature\Pharmacy;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Medicine;
use App\Models\MedicineReservation;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The pharmacy orders desk on the web (/pharmacy/orders): a pharmacy sees
 * only the reservations placed at its own listing, walks a hold forward
 * through the same state machine the mobile desk uses, must give a reason
 * to cancel, and never deletes a row. A reservation placed elsewhere does
 * not exist here (404), and a role without `dispense` is refused (403).
 */
class PharmacyOrdersDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: Facility, 1: CareFacility, 2: User} */
    private function pharmacyUser(string $roleName = 'pharmacist', string $name = 'Pharmacie du Marché'): array
    {
        $facility = Facility::factory()->create(['type' => 'pharmacy', 'name' => $name]);
        $listing = $this->listing($facility, $name);
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id]);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$facility, $listing, $user];
    }

    private function listing(Facility $facility, string $name): CareFacility
    {
        return CareFacility::create([
            'facility_id' => $facility->id, 'facility_name' => $name, 'facility_type' => 'pharmacy',
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala',
            'address' => 'Rue Joss, Akwa', 'latitude' => 4.0520, 'longitude' => 9.7690, 'phone_primary' => '+237600000001',
        ]);
    }

    private function medicine(string $name = 'Paracetamol 500mg'): Medicine
    {
        return Medicine::create([
            'name' => $name, 'generic_name' => $name, 'strength' => '500mg', 'form' => 'tablet',
            'category' => \App\Enums\MedicineCategory::PainRelief->value, 'prescription_required' => false,
            'default_pack_size' => '10 tablets', 'currency' => 'XAF',
        ]);
    }

    private function reservation(CareFacility $listing, array $overrides = []): MedicineReservation
    {
        $patient = Patient::factory()->create(['first_name' => 'Ngono', 'last_name' => 'Ateba']);

        return MedicineReservation::create(array_merge([
            'reference'        => 'OC-RX-' . strtoupper(Str::random(8)),
            'patient_id'       => $patient->id,
            'medicine_id'      => $this->medicine()->id,
            'care_facility_id' => $listing->id,
            'quantity'         => 2,
            'pack_size'        => '10 tablets',
            'unit_price'       => 1500,
            'total_price'      => 3000,
            'currency'         => 'XAF',
            'status'           => MedicineReservation::STATUS_PENDING,
            'fulfilment_mode'  => MedicineReservation::FULFILMENT_PICKUP,
            'expires_at'       => now()->addDay(),
        ], $overrides));
    }

    private function asStaff(User $user)
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $user->primary_facility_id]);
    }

    public function test_index_lists_only_this_pharmacys_reservations(): void
    {
        [, $listing, $user] = $this->pharmacyUser();
        $mine = $this->reservation($listing, ['reference' => 'OC-RX-MINE0001']);

        [, $otherListing] = $this->pharmacyUser('pharmacist', 'Pharmacie Voisine');
        $this->reservation($otherListing, ['reference' => 'OC-RX-THEIRS01']);

        $this->asStaff($user)
            ->get(route('portals.pharmacy.orders.index'))
            ->assertOk()
            ->assertSee('OC-RX-MINE0001')
            ->assertSee('Ngono Ateba')
            ->assertSee('3 000 FCFA')
            ->assertSee($mine->patient->health_id)
            ->assertDontSee('OC-RX-THEIRS01');
    }

    public function test_pending_accept_ready_collected_happy_path(): void
    {
        [, $listing, $user] = $this->pharmacyUser();
        $r = $this->reservation($listing);

        $this->asStaff($user)->post(route('portals.pharmacy.orders.accept', $r->id))
            ->assertRedirect(route('portals.pharmacy.orders.show', $r->id));
        $this->assertSame(MedicineReservation::DESK_ACCEPTED, $r->fresh()->deskStatus());

        $this->asStaff($user)->post(route('portals.pharmacy.orders.ready', $r->id))->assertRedirect();
        $this->assertSame(MedicineReservation::DESK_READY, $r->fresh()->deskStatus());

        $this->asStaff($user)->post(route('portals.pharmacy.orders.collected', $r->id))->assertRedirect();
        $this->assertSame(MedicineReservation::DESK_COLLECTED, $r->fresh()->deskStatus());
        $this->assertNotNull($r->fresh()->collected_at);

        // The closed order is on the Done tab and the page still renders it.
        $this->asStaff($user)->get(route('portals.pharmacy.orders.index', ['tab' => 'done']))
            ->assertOk()->assertSee($r->reference);
        $this->asStaff($user)->get(route('portals.pharmacy.orders.show', $r->id))->assertOk();
    }

    public function test_a_wrong_step_is_refused_without_changing_the_row(): void
    {
        [, $listing, $user] = $this->pharmacyUser();
        $r = $this->reservation($listing);

        // ready straight from pending is not a transition the desk knows.
        $this->asStaff($user)->post(route('portals.pharmacy.orders.ready', $r->id))
            ->assertRedirect(route('portals.pharmacy.orders.show', $r->id))
            ->assertSessionHas('error');
        $this->assertSame(MedicineReservation::DESK_PENDING, $r->fresh()->deskStatus());
    }

    public function test_cancel_requires_a_reason_and_keeps_the_row(): void
    {
        [, $listing, $user] = $this->pharmacyUser();
        $r = $this->reservation($listing);

        $this->asStaff($user)->from(route('portals.pharmacy.orders.show', $r->id))
            ->post(route('portals.pharmacy.orders.cancel', $r->id), [])
            ->assertSessionHasErrors('reason');
        $this->assertSame(MedicineReservation::DESK_PENDING, $r->fresh()->deskStatus());

        $this->asStaff($user)
            ->post(route('portals.pharmacy.orders.cancel', $r->id), ['reason' => 'Out of stock until Thursday'])
            ->assertRedirect(route('portals.pharmacy.orders.show', $r->id));

        $row = MedicineReservation::query()->find($r->id);
        $this->assertNotNull($row, 'a cancelled reservation is never deleted');
        $this->assertSame(MedicineReservation::DESK_CANCELLED, $row->deskStatus());
        $this->assertSame('Out of stock until Thursday', $row->cancelled_reason);
        $this->assertNotNull($row->cancelled_at);
    }

    public function test_a_reservation_placed_elsewhere_does_not_exist_here(): void
    {
        [, , $user] = $this->pharmacyUser();
        [, $otherListing] = $this->pharmacyUser('pharmacist', 'Pharmacie Voisine');
        $foreign = $this->reservation($otherListing);

        $this->asStaff($user)->get(route('portals.pharmacy.orders.show', $foreign->id))->assertNotFound();
        $this->asStaff($user)->post(route('portals.pharmacy.orders.accept', $foreign->id))->assertNotFound();
        $this->assertSame(MedicineReservation::DESK_PENDING, $foreign->fresh()->deskStatus());
    }

    public function test_a_role_without_dispense_is_refused(): void
    {
        [, $listing, $stockKeeper] = $this->pharmacyUser('medicine_stock');
        $r = $this->reservation($listing);

        $this->asStaff($stockKeeper)->get(route('portals.pharmacy.orders.index'))->assertForbidden();
        $this->asStaff($stockKeeper)->post(route('portals.pharmacy.orders.accept', $r->id))->assertForbidden();
        $this->assertSame(MedicineReservation::DESK_PENDING, $r->fresh()->deskStatus());
    }

    public function test_a_delivery_goes_out_and_is_delivered(): void
    {
        [, $listing, $user] = $this->pharmacyUser();
        $r = $this->reservation($listing, [
            'fulfilment_mode'  => MedicineReservation::FULFILMENT_DELIVERY,
            'delivery_address' => 'Bonapriso, Douala',
            'delivery_phone'   => '+237699000000',
        ]);

        $this->asStaff($user)->post(route('portals.pharmacy.orders.accept', $r->id))->assertRedirect();
        $this->asStaff($user)->post(route('portals.pharmacy.orders.ready', $r->id))->assertRedirect();

        // A delivery is not handed over a counter.
        $this->asStaff($user)->post(route('portals.pharmacy.orders.collected', $r->id))->assertSessionHas('error');
        $this->assertSame(MedicineReservation::DESK_READY, $r->fresh()->deskStatus());

        $this->asStaff($user)->post(route('portals.pharmacy.orders.dispatch', $r->id))->assertRedirect();
        $this->assertSame(MedicineReservation::DESK_DISPATCHED, $r->fresh()->deskStatus());

        $this->asStaff($user)->get(route('portals.pharmacy.orders.show', $r->id))
            ->assertOk()->assertSee('Bonapriso, Douala');

        $this->asStaff($user)->post(route('portals.pharmacy.orders.delivered', $r->id))->assertRedirect();
        $this->assertSame(MedicineReservation::DESK_DELIVERED, $r->fresh()->deskStatus());
    }
}
