<?php

namespace Tests\Feature\Pharmacy;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Medicine;
use App\Models\MedicineReservation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Clinical\PrescriptionService;
use App\Services\Pharmacy\PharmacyDeskService;
use App\Services\Pharmacy\PharmacyStockOpsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Second-pass audit (docs/audits/PHARMACY_DASHBOARD_AUDIT_2.md): the seams
 * between the four slices that built the pharmacy dashboard.
 *
 *   PH2-1 web dispense went around the desk service (filled scripts routed to
 *         another pharmacy; no partial fill; routing status never closed)
 *   PH2-2 the web worklist + dashboard tiles scoped by the ISSUING facility,
 *         so a pharmacy's routed scripts never showed
 *   PH2-3 collecting a reservation never took packs off the ledger
 *   PH2-4 the "expired" tile and the batches page counted different lots
 *   PH2-5 an unlinked pharmacy's empty orders desk said nothing
 */
class PharmacyDashboardSeamsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: Facility, 1: ?CareFacility, 2: User} */
    private function pharmacyUser(string $name = 'Pharmacie du Marché', bool $linked = true, string $roleName = 'pharmacist'): array
    {
        $facility = Facility::factory()->create(['type' => 'pharmacy', 'name' => $name]);
        $listing = $linked ? CareFacility::create([
            'facility_id' => $facility->id, 'facility_name' => $name, 'facility_type' => 'pharmacy',
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala',
            'address' => 'Rue Joss, Akwa', 'latitude' => 4.0520, 'longitude' => 9.7690, 'phone_primary' => '+237600000001',
        ]) : null;
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id]);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$facility, $listing, $user];
    }

    private function medicine(string $name = 'Paracetamol 500mg'): Medicine
    {
        return Medicine::create([
            'name' => $name, 'generic_name' => $name, 'strength' => '500mg', 'form' => 'tablet',
            'category' => \App\Enums\MedicineCategory::PainRelief->value, 'prescription_required' => false,
            'default_pack_size' => '10 tablets', 'currency' => 'XAF',
        ]);
    }

    private function asStaff(User $user)
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $user->primary_facility_id]);
    }

    private function scriptFromAClinic(int $lines = 1): Prescription
    {
        $clinic = Facility::factory()->create(['type' => 'clinic', 'name' => 'Clinique Akwa']);
        $doctor = User::factory()->create(['primary_facility_id' => $clinic->id]);
        $patient = Patient::factory()->create(['first_name' => 'Ngono', 'last_name' => 'Ateba']);

        $items = [];
        for ($i = 0; $i < $lines; $i++) {
            $items[] = [
                'medicine_id' => $this->medicine('Medicine ' . $i)->id,
                'dose' => '500 mg', 'frequency' => 'twice daily', 'route' => 'oral',
                'duration_days' => 5, 'quantity' => 10,
            ];
        }

        return app(PrescriptionService::class)->issue([
            'patient_id' => $patient->id, 'facility_id' => $clinic->id, 'prescribed_by' => $doctor->id,
            'validity_days' => 30, 'items' => $items,
        ], $doctor->id);
    }

    // ── PH2-1 ────────────────────────────────────────────────────────────

    public function test_web_dispense_refuses_a_script_routed_to_another_pharmacy(): void
    {
        [, , $me] = $this->pharmacyUser('Pharmacie A');
        [$other] = $this->pharmacyUser('Pharmacie B');

        $rx = $this->scriptFromAClinic();
        app(PharmacyDeskService::class)->routeToFacility($rx, $other);

        $this->asStaff($me)->post(route('portals.pharmacy.dispense', $rx->id))->assertNotFound();
        $this->assertSame('active', $rx->fresh()->status);
    }

    public function test_web_dispense_claims_the_script_and_can_hand_over_part_of_it(): void
    {
        [$facility, , $me] = $this->pharmacyUser();
        $rx = $this->scriptFromAClinic(2);
        $first = $rx->items()->orderBy('drug_name')->firstOrFail();

        $this->asStaff($me)
            ->post(route('portals.pharmacy.dispense', $rx->id), ['item_ids' => [$first->id]])
            ->assertRedirect(route('portals.pharmacy.prescriptions'));

        $rx->refresh();
        $this->assertSame('partially_dispensed', $rx->status);
        $this->assertContains((string) $rx->pharmacy_route_id, app(PharmacyDeskService::class)->routeIds($facility->id));

        // Still on this counter's worklist — the patient is owed a line.
        $this->asStaff($me)->get(route('portals.pharmacy.prescriptions'))
            ->assertOk()
            ->assertViewHas('prescriptions', fn ($list) => $list->contains('id', $rx->id));

        // The rest, then the routing status closes too.
        $this->asStaff($me)->post(route('portals.pharmacy.dispense', $rx->id))->assertRedirect();
        $rx->refresh();
        $this->assertSame('dispensed', $rx->status);
        $this->assertSame(PharmacyDeskService::ROUTING_DISPENSED, $rx->pharmacy_routing_status);
    }

    // ── PH2-2 ────────────────────────────────────────────────────────────

    public function test_a_script_routed_here_shows_on_the_worklist_and_dashboard(): void
    {
        [$facility, , $me] = $this->pharmacyUser();
        [, , $stranger] = $this->pharmacyUser('Pharmacie Voisine');
        $rx = $this->scriptFromAClinic();
        app(PharmacyDeskService::class)->routeToFacility($rx, $facility);

        $this->asStaff($me)->get(route('portals.pharmacy.prescriptions'))
            ->assertOk()
            ->assertViewHas('prescriptions', fn ($list) => $list->contains('id', $rx->id));

        $this->asStaff($me)->get(route('portals.pharmacy.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['pending_rx'] === 1);

        // Another pharmacy never sees it.
        $this->asStaff($stranger)->get(route('portals.pharmacy.prescriptions'))
            ->assertOk()
            ->assertViewHas('prescriptions', fn ($list) => ! $list->contains('id', $rx->id));
    }

    // ── PH2-3 ────────────────────────────────────────────────────────────

    private function reservation(CareFacility $listing, Medicine $medicine, int $qty, string $status = MedicineReservation::STATUS_READY): MedicineReservation
    {
        return MedicineReservation::create([
            'reference' => 'OC-RX-' . strtoupper(Str::random(8)),
            'patient_id' => Patient::factory()->create()->id,
            'medicine_id' => $medicine->id, 'care_facility_id' => $listing->id,
            'quantity' => $qty, 'pack_size' => '10 tablets', 'unit_price' => 1500, 'total_price' => 1500 * $qty,
            'currency' => 'XAF', 'status' => $status, 'ready_at' => now(),
            'fulfilment_mode' => MedicineReservation::FULFILMENT_PICKUP, 'expires_at' => now()->addDay(),
        ]);
    }

    public function test_collecting_an_order_takes_the_packs_off_the_ledger_earliest_expiry_first(): void
    {
        [$facility, $listing, $me] = $this->pharmacyUser();
        $medicine = $this->medicine();
        $ops = app(PharmacyStockOpsService::class);
        $ops->receiveDelivery($facility->id, ['received_date' => now()->toDateString()], [
            ['medicine_id' => $medicine->id, 'quantity' => 2, 'lot_number' => 'SOON', 'expiry_date' => now()->addMonth()->toDateString()],
            ['medicine_id' => $medicine->id, 'quantity' => 10, 'lot_number' => 'LATER', 'expiry_date' => now()->addYear()->toDateString()],
        ], $me->id);

        $order = $this->reservation($listing, $medicine, 3);

        $this->asStaff($me)->post(route('portals.pharmacy.orders.collected', $order->id))
            ->assertRedirect(route('portals.pharmacy.orders.show', $order->id))
            ->assertSessionMissing('warning');

        $this->assertSame(0, (int) StockBatch::where('lot_number', 'SOON')->value('quantity_available'));
        $this->assertSame(9, (int) StockBatch::where('lot_number', 'LATER')->value('quantity_available'));
        $this->assertSame(2, StockMovement::where('reference_type', PharmacyStockOpsService::REFERENCE_RESERVATION)
            ->where('reference_id', $order->id)->where('movement_type', 'dispense')->count());

        // The public finder row now says 9, not 12.
        $this->assertSame(9, (int) \App\Models\MedicinePharmacyStock::where('care_facility_id', $listing->id)
            ->where('medicine_id', $medicine->id)->value('packs_available'));

        $this->asStaff($me)->get(route('portals.pharmacy.orders.show', $order->id))
            ->assertOk()->assertSee(__('pharmacy_orders.stock_recorded', ['count' => 3]));
    }

    public function test_a_hand_over_without_enough_ledger_stock_still_stands_and_says_so(): void
    {
        [, $listing, $me] = $this->pharmacyUser();
        $order = $this->reservation($listing, $this->medicine(), 2);

        $this->asStaff($me)->post(route('portals.pharmacy.orders.collected', $order->id))
            ->assertRedirect()
            ->assertSessionHas('warning', __('pharmacy_orders.flash_stock_not_recorded'));

        $this->assertSame(MedicineReservation::DESK_COLLECTED, $order->fresh()->deskStatus());
        $this->assertSame(0, StockMovement::count());
    }

    // ── PH2-4 ────────────────────────────────────────────────────────────

    public function test_the_expired_tile_counts_what_the_batches_page_lists(): void
    {
        [$facility, , $me] = $this->pharmacyUser();
        $medicine = $this->medicine();
        $ops = app(PharmacyStockOpsService::class);
        $ops->receiveDelivery($facility->id, ['received_date' => now()->toDateString()], [
            ['medicine_id' => $medicine->id, 'quantity' => 5, 'lot_number' => 'OLD', 'expiry_date' => now()->addYear()->toDateString()],
        ], $me->id);
        StockBatch::where('lot_number', 'OLD')->update(['expiry_date' => now()->subDay()->toDateString()]);

        $listed = $ops->expired($facility->id)->count();
        $this->assertSame(1, $listed);

        $this->asStaff($me)->get(route('portals.pharmacy.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['expired'] === $listed);
    }

    // ── PH2-5 ────────────────────────────────────────────────────────────

    public function test_an_unlinked_pharmacy_is_told_why_its_orders_desk_is_empty(): void
    {
        [, , $me] = $this->pharmacyUser('Pharmacie Sans Fiche', false);

        $this->asStaff($me)->get(route('portals.pharmacy.orders.index'))
            ->assertOk()
            ->assertSee(__('pharmacy_orders.no_listing'));

        [, , $linked] = $this->pharmacyUser();
        $this->asStaff($linked)->get(route('portals.pharmacy.orders.index'))
            ->assertOk()
            ->assertDontSee(__('pharmacy_orders.no_listing'));
    }

    public function test_the_dashboard_says_how_many_orders_are_waiting_and_links_the_desk(): void
    {
        [, $listing, $me] = $this->pharmacyUser();
        $this->reservation($listing, $this->medicine(), 1, MedicineReservation::STATUS_PENDING);
        [, $otherListing] = $this->pharmacyUser('Pharmacie Voisine');
        $this->reservation($otherListing, $this->medicine('Other'), 1, MedicineReservation::STATUS_PENDING);

        $this->asStaff($me)->get(route('portals.pharmacy.dashboard'))
            ->assertOk()
            ->assertViewHas('pendingOrders', 1)
            ->assertSee(route('portals.pharmacy.orders.index'), false);
    }

    public function test_the_new_order_notification_opens_that_order(): void
    {
        [, $listing, $me] = $this->pharmacyUser();
        $medicine = $this->medicine();
        \App\Models\MedicinePharmacyStock::create([
            'medicine_id' => $medicine->id, 'care_facility_id' => $listing->id, 'stock_status' => 'in_stock',
            'packs_available' => 5, 'unit_price' => 1500, 'reservation_enabled' => true,
            'source_system' => 'portal', 'last_reported_at' => now(),
        ]);

        $order = app(\App\Modules\Pharmacy\Services\MedicineReservationService::class)
            ->reserve((string) Patient::factory()->create()->id, $medicine, $listing, 1);

        $event = \App\Modules\Notifications\Models\NotificationEvent::query()
            ->where('event_type', 'pharmacy_order_received')
            ->where('recipient_user_id', $me->id)
            ->firstOrFail();

        $this->assertSame(route('portals.pharmacy.orders.show', $order->id), json_decode($event->payload_json, true)['url']);
    }

    public function test_the_order_page_shows_the_prescription_code_not_a_uuid(): void
    {
        [, $listing, $me] = $this->pharmacyUser();
        $rx = $this->scriptFromAClinic();
        $order = $this->reservation($listing, $this->medicine('Amoxicillin'), 1);
        $order->forceFill(['prescription_id' => $rx->id])->save();

        $this->asStaff($me)->get(route('portals.pharmacy.orders.show', $order->id))
            ->assertOk()
            ->assertSee((string) $rx->reference)
            ->assertDontSee((string) $rx->id);
    }
}
