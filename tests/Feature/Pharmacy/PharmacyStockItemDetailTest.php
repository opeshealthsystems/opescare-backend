<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\MedicineCategory;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\InventoryItem;
use App\Models\Medicine;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The stock item page and the movement page: who may open them, what they
 * show, and that another pharmacy's records do not exist here.
 */
class PharmacyStockItemDetailTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        [$this->facility] = $this->pharmacy('PharmaPlus Akwa');

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
    }

    /** @return array{0: Facility, 1: CareFacility} */
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

    /** Receive one lot from a named supplier and write one movement off it; returns [item, batch, adjustment movement]. */
    private function ledger(User $user, Facility $facility, string $lot = 'AMX-2026-A'): array
    {
        $supplier = Supplier::create(['facility_id' => $facility->id, 'name' => 'Laborex Douala', 'status' => 'active']);

        $this->actingAsStaff($user, $facility)->post('/pharmacy/stock/receipts', [
            'supplier_id'   => $supplier->id,
            'received_date' => now()->toDateString(),
            'receipt_number' => 'BL-' . $lot,
            'lines'         => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 30, 'lot_number' => $lot, 'expiry_date' => now()->addYear()->toDateString(), 'unit_cost' => 1500],
            ],
        ])->assertRedirect();

        $item  = InventoryItem::where('facility_id', $facility->id)->where('medicine_id', $this->medicine->id)->firstOrFail();
        $batch = StockBatch::where('lot_number', $lot)->firstOrFail();

        $this->actingAsStaff($user, $facility)->post('/pharmacy/stock/adjustments', [
            'batch_id' => $batch->id,
            'reason'   => 'damaged',
            'quantity' => 4,
            'note'     => 'Water damage on the bottom shelf',
        ])->assertRedirect();

        $movement = StockMovement::where('batch_id', $batch->id)->where('movement_type', 'write_off')->firstOrFail();

        return [$item, $batch, $movement];
    }

    public function test_the_stock_roles_open_the_item_page_and_see_its_ledger(): void
    {
        $pharmacist = $this->staff('pharmacist');
        [$item, $batch, $movement] = $this->ledger($pharmacist, $this->facility);

        foreach (['pharmacist', 'medicine_stock', 'pharmacy_manager'] as $roleName) {
            $this->actingAsStaff($this->staff($roleName))
                ->get('/pharmacy/stock/items/' . $item->id)
                ->assertOk()
                ->assertSee('Amoxicillin 500mg Capsule')
                ->assertSee('J01CA04')
                ->assertSee('AMX-2026-A')
                ->assertSee('Laborex Douala')
                ->assertSee(__('pharmacy_stock_desk.finder.published'))
                ->assertSee(route('portals.pharmacy.stock.adjustments.show', $movement->id), false)
                ->assertSee(route('portals.pharmacy.prices.history', $item->id), false)
                ->assertSee(route('portals.pharmacy.stock.receipts.create', ['medicine_id' => $this->medicine->id]), false);
        }

        // 30 received, 4 written off: the header says what patients are told.
        $this->assertSame(26, $batch->fresh()->availableQty());
        $this->actingAsStaff($pharmacist)->get('/pharmacy/stock/items/' . $item->id)->assertSee('26');
    }

    public function test_the_movement_page_decodes_the_reason_and_replays_before_and_after(): void
    {
        $pharmacist = $this->staff('pharmacist');
        [$item, $batch, $movement] = $this->ledger($pharmacist, $this->facility);

        $this->actingAsStaff($pharmacist)
            ->get('/pharmacy/stock/adjustments/' . $movement->id)
            ->assertOk()
            ->assertSee(__('pharmacy_ops.reason.damaged'))
            ->assertSee('Water damage on the bottom shelf')
            ->assertSee($pharmacist->name)
            ->assertSee('AMX-2026-A')
            ->assertSee(__('pharmacy_stock_desk.col_before'))
            ->assertSeeInOrder(['30', '26']);
    }

    public function test_list_rows_and_the_dashboard_link_to_the_item_page(): void
    {
        $pharmacist = $this->staff('pharmacist');
        [$item, $batch, $movement] = $this->ledger($pharmacist, $this->facility);

        $itemUrl = route('portals.pharmacy.stock.items.show', $item->id);

        $this->actingAsStaff($pharmacist)->get('/pharmacy/stock/batches')->assertOk()->assertSee($itemUrl, false);
        $this->actingAsStaff($pharmacist)->get('/pharmacy/stock')->assertOk()->assertSee($itemUrl, false);
        $this->actingAsStaff($pharmacist)->get('/pharmacy/stock/adjustments')->assertOk()
            ->assertSee(route('portals.pharmacy.stock.adjustments.show', $movement->id), false);

        // The dashboard alert panel only lists low/out rows: set the minimum
        // above what is on the shelf so the medicine becomes an alert.
        $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/reorder', [
            'medicine_id' => $this->medicine->id, 'reorder_level' => 100, 'reorder_quantity' => 50,
        ])->assertRedirect();

        $this->actingAsStaff($pharmacist)->get('/pharmacy')->assertOk()->assertSee($itemUrl, false);
    }

    public function test_the_front_desk_is_refused_and_another_pharmacys_item_is_not_found(): void
    {
        $pharmacist = $this->staff('pharmacist');
        [$item, $batch, $movement] = $this->ledger($pharmacist, $this->facility);

        $receptionist = $this->staff('receptionist');
        $this->actingAsStaff($receptionist)->get('/pharmacy/stock/items/' . $item->id)->assertForbidden();
        $this->actingAsStaff($receptionist)->get('/pharmacy/stock/adjustments/' . $movement->id)->assertForbidden();

        [$other] = $this->pharmacy('Pharmacie du Rond-Point');
        $theirs  = $this->staff('pharmacist', $other);

        $this->actingAsStaff($theirs, $other)->get('/pharmacy/stock/items/' . $item->id)->assertNotFound();
        $this->actingAsStaff($theirs, $other)->get('/pharmacy/stock/adjustments/' . $movement->id)->assertNotFound();
    }
}
