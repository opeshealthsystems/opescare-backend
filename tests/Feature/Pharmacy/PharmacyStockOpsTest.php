<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\MedicineCategory;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Patient;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Pharmacy\PharmacyStockOpsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * The pharmacy back office, and the three ways it is allowed to hurt someone.
 *
 *   1. EXPIRED STOCK IS NEVER TOLD TO A PATIENT. The ledger republishes the
 *      usable quantity to `medicine_pharmacy_stocks` — the row the Medicine
 *      Finder reads — and a batch past its date contributes nothing to it,
 *      whether or not anyone has written it off.
 *   2. THE LEDGER IS APPEND-ONLY. A movement cannot be edited or deleted,
 *      through Eloquent or through any route.
 *   3. A DELIVERY IS ALL OR NOTHING. A receipt whose second line fails leaves
 *      no receipt, no batch and no movement behind from its first.
 *
 * Plus the scope rules every desk carries: another pharmacy's records 404,
 * a role without the capability 403s.
 */
class PharmacyStockOpsTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private const ORIGIN_LAT = 4.0511;
    private const ORIGIN_LNG = 9.7679;

    private Facility $facility;
    private CareFacility $listing;
    private Medicine $medicine;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        [$this->facility, $this->listing] = $this->pharmacy('PharmaPlus Akwa');

        $this->medicine = Medicine::create([
            'name'                  => 'Amoxicillin 500mg Capsule',
            'generic_name'          => 'Amoxicillin',
            'strength'              => '500mg',
            'form'                  => 'capsule',
            'category'              => MedicineCategory::PainRelief->value,
            'atc_code'              => 'J01CA04',
            'prescription_required' => true,
            'default_pack_size'     => '16 capsules',
            'currency'              => 'XAF',
        ]);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-7400-0001-01',
            'first_name'    => 'Ngozi',
            'last_name'     => 'Seeker',
            'sex'           => 'female',
            'date_of_birth' => '1990-02-02',
            'is_demo'       => false,
        ]);
    }

    /** A portal facility with a geocoded, linked public listing. @return array{0: Facility, 1: CareFacility} */
    private function pharmacy(string $name): array
    {
        $facility = Facility::create(['name' => $name, 'type' => 'pharmacy', 'status' => 'active']);

        $listing = CareFacility::create([
            'facility_id'    => $facility->id,
            'facility_name'  => $name,
            'facility_type'  => 'pharmacy',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Littoral',
            'city'           => 'Douala',
            'address'        => 'Rue Joss, Akwa',
            'latitude'       => 4.0520,
            'longitude'      => 9.7690,
            'phone_primary'  => '+237600000001',
        ]);

        return [$facility, $listing];
    }

    private function staff(string $roleName, ?Facility $facility = null): User
    {
        $facility ??= $this->facility;
        $role = Role::where('name', $roleName)->firstOrFail();

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

        return $user;
    }

    private function actingAsStaff(User $user, ?Facility $facility = null): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => ($facility ?? $this->facility)->id]);
    }

    /** @return array<string,mixed>|null the finder's row for this pharmacy */
    private function patientSeesPharmacy(): ?array
    {
        $res = $this->mobileGetJson($this->patient, '/api/mobile/pharmacy/nearby?' . http_build_query([
            'lat'         => self::ORIGIN_LAT,
            'lng'         => self::ORIGIN_LNG,
            'radius_km'   => 5,
            'medicine_id' => $this->medicine->id,
        ]));

        $res->assertOk();

        foreach ($res->json('data') as $row) {
            if ($row['id'] === $this->listing->id) {
                return $row;
            }
        }

        return null;
    }

    private function receive(User $user, array $lines, array $header = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAsStaff($user)->post('/pharmacy/stock/receipts', array_merge([
            'received_date' => now()->toDateString(),
            'lines'         => $lines,
        ], $header));
    }

    // ── Goods receipt: batch + movement, atomically ───────────────────────

    public function test_a_goods_receipt_creates_the_receipt_its_lines_a_batch_and_a_movement_together(): void
    {
        $pharmacist = $this->staff('pharmacist');

        $this->receive($pharmacist, [
            ['medicine_id' => $this->medicine->id, 'quantity' => 30, 'lot_number' => 'AMX-2026-A', 'expiry_date' => now()->addYear()->toDateString(), 'unit_cost' => 1500],
            ['medicine_id' => $this->medicine->id, 'quantity' => 12, 'lot_number' => 'AMX-2026-B', 'expiry_date' => now()->addMonths(18)->toDateString(), 'unit_cost' => 1500],
        ], ['receipt_number' => 'BL-0001'])->assertRedirect();

        $receipt = GoodsReceipt::where('facility_id', $this->facility->id)->firstOrFail();
        $this->assertSame('BL-0001', $receipt->receipt_number);
        $this->assertSame('verified', $receipt->status);
        $this->assertSame($pharmacist->id, $receipt->received_by);

        $this->assertSame(2, GoodsReceiptItem::where('goods_receipt_id', $receipt->id)->count());
        $this->assertSame(2, StockBatch::where('facility_id', $this->facility->id)->count());
        $this->assertSame(2, StockMovement::where('facility_id', $this->facility->id)->where('movement_type', 'receipt')->count());

        $item = InventoryItem::where('facility_id', $this->facility->id)->where('medicine_id', $this->medicine->id)->firstOrFail();
        $this->assertSame(42, (int) StockBatch::where('inventory_item_id', $item->id)->sum('quantity_available'));

        // Whole francs, never a float.
        $this->assertSame(1500, (int) StockBatch::where('lot_number', 'AMX-2026-A')->value('unit_cost'));

        // The patient sees what the ledger holds.
        $row = $this->patientSeesPharmacy();
        $this->assertSame(42, $row['stock']['packs_available']);
        $this->assertSame('in_stock', $row['stock']['status']);

        // And the receipt page opens for its own pharmacy.
        $this->actingAsStaff($pharmacist)->get('/pharmacy/stock/receipts/' . $receipt->id)
            ->assertOk()->assertSee('BL-0001')->assertSee('AMX-2026-A');
    }

    public function test_a_delivery_whose_second_line_fails_records_nothing_from_its_first(): void
    {
        $pharmacist = $this->staff('pharmacist');
        $service    = app(PharmacyStockOpsService::class);

        try {
            $service->receiveDelivery($this->facility->id, ['received_date' => now()->toDateString()], [
                ['medicine_id' => $this->medicine->id, 'quantity' => 30, 'lot_number' => 'GOOD', 'expiry_date' => now()->addYear()->toDateString()],
                // A medicine that is not in the catalogue: the service throws
                // AFTER line one has been written inside the transaction.
                ['medicine_id' => (string) Str::uuid(), 'quantity' => 5],
            ], $pharmacist->id);

            $this->fail('A line naming an unknown medicine must abort the delivery.');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertSame(0, GoodsReceipt::count());
        $this->assertSame(0, GoodsReceiptItem::count());
        $this->assertSame(0, StockBatch::count());
        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, MedicinePharmacyStock::where('care_facility_id', $this->listing->id)->count());
    }

    // ── Expired stock never reaches the patient ───────────────────────────

    public function test_an_expired_batch_never_surfaces_in_the_patient_finder(): void
    {
        $pharmacist = $this->staff('pharmacist');

        $this->receive($pharmacist, [
            ['medicine_id' => $this->medicine->id, 'quantity' => 10, 'lot_number' => 'OLD', 'expiry_date' => now()->subDay()->toDateString()],
            ['medicine_id' => $this->medicine->id, 'quantity' => 5,  'lot_number' => 'NEW', 'expiry_date' => now()->addYear()->toDateString()],
        ])->assertRedirect();

        // Only the unexpired lot is told to the patient, even though the
        // expired one still physically holds 10 packs and is still `active`.
        $row = $this->patientSeesPharmacy();
        $this->assertSame(5, $row['stock']['packs_available']);
        $this->assertTrue($row['stock']['is_available']);
        $this->assertSame('active', StockBatch::where('lot_number', 'OLD')->value('status'));

        // Dispense the good lot: the pharmacy is now OUT, not "10 in stock".
        $good = StockBatch::where('lot_number', 'NEW')->firstOrFail();

        $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/adjustments', [
            'batch_id' => $good->id,
            'reason'   => 'dispensed',
            'quantity' => 5,
        ])->assertRedirect(route('portals.pharmacy.stock.adjustments'));

        $row = $this->patientSeesPharmacy();
        $this->assertSame(0, $row['stock']['packs_available']);
        $this->assertSame('out_of_stock', $row['stock']['status']);
        $this->assertFalse($row['stock']['is_available']);

        // The batches screen names the expired lot as such.
        $this->actingAsStaff($pharmacist)->get('/pharmacy/stock/batches')
            ->assertOk()
            ->assertSee(__('pharmacy_ops.expired_badge'))
            ->assertSee('OLD');
    }

    public function test_a_movement_of_more_packs_than_the_lot_holds_is_refused(): void
    {
        $pharmacist = $this->staff('pharmacist');

        $this->receive($pharmacist, [
            ['medicine_id' => $this->medicine->id, 'quantity' => 3, 'lot_number' => 'L3', 'expiry_date' => now()->addYear()->toDateString()],
        ]);

        $batch = StockBatch::where('lot_number', 'L3')->firstOrFail();

        $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/adjustments', [
            'batch_id' => $batch->id,
            'reason'   => 'damaged',
            'quantity' => 4,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(3, $batch->fresh()->availableQty());
        $this->assertSame(1, StockMovement::count(), 'Only the receipt movement exists.');
    }

    // ── The ledger is append-only ─────────────────────────────────────────

    public function test_a_recorded_movement_can_neither_be_edited_nor_deleted(): void
    {
        $pharmacist = $this->staff('pharmacist');

        $this->receive($pharmacist, [
            ['medicine_id' => $this->medicine->id, 'quantity' => 7, 'lot_number' => 'L7'],
        ]);

        $movement = StockMovement::firstOrFail();

        $this->assertFalse($movement->update(['quantity' => 700, 'reason' => 'rewritten']));
        $this->assertSame(7, $movement->fresh()->quantity);
        $this->assertNotSame('rewritten', $movement->fresh()->reason);

        $this->assertFalse($movement->delete());
        $this->assertSame(1, StockMovement::count());

        // And no route offers an edit: nothing under /pharmacy/stock answers
        // PUT, PATCH or DELETE.
        foreach (Route::getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'pharmacy/stock')) {
                $this->assertEmpty(
                    array_intersect(['PUT', 'PATCH', 'DELETE'], $route->methods()),
                    "Route {$route->uri()} must not accept an edit or a delete.",
                );
            }
        }
    }

    // ── Reorder rules ─────────────────────────────────────────────────────

    public function test_a_medicine_below_its_minimum_is_listed_and_published_as_low(): void
    {
        $pharmacist = $this->staff('pharmacy_manager');

        $this->receive($pharmacist, [
            ['medicine_id' => $this->medicine->id, 'quantity' => 4, 'lot_number' => 'L4', 'expiry_date' => now()->addYear()->toDateString()],
        ]);

        $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/reorder', [
            'medicine_id'      => $this->medicine->id,
            'reorder_level'    => 20,
            'reorder_quantity' => 50,
        ])->assertRedirect(route('portals.pharmacy.stock.reorder'));

        $this->actingAsStaff($pharmacist)->get('/pharmacy/stock/reorder')
            ->assertOk()
            ->assertSee('Amoxicillin 500mg Capsule')
            ->assertDontSee(__('pharmacy_ops.below_empty'));

        $this->assertSame('low_stock', $this->patientSeesPharmacy()['stock']['status']);
    }

    // ── Scope: another pharmacy's records do not exist ────────────────────

    public function test_another_pharmacys_batch_and_receipt_are_not_found(): void
    {
        [$otherFacility] = $this->pharmacy('Pharmacie du Rond-Point');
        $otherPharmacist = $this->staff('pharmacist', $otherFacility);

        $this->actingAsStaff($otherPharmacist, $otherFacility)->post('/pharmacy/stock/receipts', [
            'received_date' => now()->toDateString(),
            'lines'         => [['medicine_id' => $this->medicine->id, 'quantity' => 9, 'lot_number' => 'THEIRS']],
        ])->assertRedirect();

        $theirBatch   = StockBatch::where('lot_number', 'THEIRS')->firstOrFail();
        $theirReceipt = GoodsReceipt::where('facility_id', $otherFacility->id)->firstOrFail();

        $mine = $this->staff('pharmacist');

        $this->actingAsStaff($mine)->post('/pharmacy/stock/adjustments', [
            'batch_id' => $theirBatch->id,
            'reason'   => 'dispensed',
            'quantity' => 1,
        ])->assertNotFound();

        $this->assertSame(9, $theirBatch->fresh()->availableQty());

        $this->actingAsStaff($mine)->get('/pharmacy/stock/receipts/' . $theirReceipt->id)->assertNotFound();

        // And their lots are not offered on my screens.
        $this->actingAsStaff($mine)->get('/pharmacy/stock/batches')->assertOk()->assertDontSee('THEIRS');
    }

    // ── Roles ─────────────────────────────────────────────────────────────

    public function test_pharmacy_roles_may_open_the_desk_and_the_front_desk_may_not(): void
    {
        foreach (['pharmacist', 'pharmacy_manager', 'medicine_stock'] as $roleName) {
            $user = $this->staff($roleName);

            foreach (['/pharmacy/stock/batches', '/pharmacy/stock/adjustments', '/pharmacy/stock/reorder', '/pharmacy/stock/receipts', '/pharmacy/stock/receipts/new'] as $path) {
                $this->actingAsStaff($user)->get($path)->assertOk();
            }
        }

        $receptionist = $this->staff('receptionist');

        $this->actingAsStaff($receptionist)->get('/pharmacy/stock/batches')->assertForbidden();
        $this->actingAsStaff($receptionist)->post('/pharmacy/stock/receipts', [
            'received_date' => now()->toDateString(),
            'lines'         => [['medicine_id' => $this->medicine->id, 'quantity' => 1]],
        ])->assertForbidden();

        $this->assertSame(0, GoodsReceipt::count());
    }
}
