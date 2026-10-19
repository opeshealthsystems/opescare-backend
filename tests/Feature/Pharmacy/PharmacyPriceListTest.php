<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\MedicineCategory;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\InventoryItem;
use App\Models\InventoryPriceChange;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Role;
use App\Models\User;
use App\Services\Pharmacy\PharmacyStockOpsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pharmacy price list, and what it must never do:
 *
 *   1. A PRICE IS WHOLE FRANCS. A decimal or a negative is refused; nothing
 *      is written and nothing is published.
 *   2. EVERY CHANGE IS REMEMBERED. Setting a price appends an
 *      inventory_price_changes row (old → new) that cannot be edited.
 *   3. THE FINDER SHOWS THE PRICE LIST. Saving a price republishes the
 *      medicine's finder row, and a goods receipt for a priced item no
 *      longer publishes it without a price.
 *
 * Plus the scope rule every desk carries: another pharmacy's item 404s.
 */
class PharmacyPriceListTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private CareFacility $listing;
    private Medicine $medicine;

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

    /** The ledger row this pharmacy keeps for a medicine, exactly as a goods receipt would create it. */
    private function item(?Facility $facility = null, ?Medicine $medicine = null): InventoryItem
    {
        return app(PharmacyStockOpsService::class)
            ->itemFor(($facility ?? $this->facility)->id, $medicine ?? $this->medicine);
    }

    private function finderRow(): ?MedicinePharmacyStock
    {
        return MedicinePharmacyStock::query()
            ->where('medicine_id', $this->medicine->id)
            ->where('care_facility_id', $this->listing->id)
            ->first();
    }

    // ── Index: own items only ─────────────────────────────────────────────

    public function test_the_price_list_shows_this_pharmacys_items_and_not_anothers(): void
    {
        $mine = $this->item();

        [$otherFacility] = $this->pharmacy('Pharmacie du Wouri');
        $theirs = Medicine::create([
            'name' => 'Ibuprofen 400mg Tablet', 'generic_name' => 'Ibuprofen', 'strength' => '400mg', 'form' => 'tablet',
            'category' => MedicineCategory::PainRelief->value, 'atc_code' => 'M01AE01', 'prescription_required' => false, 'currency' => 'XAF',
        ]);
        $this->item($otherFacility, $theirs);

        $pharmacist = $this->staff('pharmacist');

        $this->actingAsStaff($pharmacist)->get('/pharmacy/prices')
            ->assertOk()
            ->assertSee('Amoxicillin 500mg Capsule')
            ->assertDontSee('Ibuprofen 400mg Tablet')
            ->assertSee(route('portals.pharmacy.prices.update', $mine->id), false)
            // No finder row yet for this medicine: the badge says so.
            ->assertSee(__('pharmacy_prices.not_published'));
    }

    public function test_the_search_box_narrows_the_list_by_name(): void
    {
        $this->item();
        $other = Medicine::create([
            'name' => 'Paracetamol 500mg Tablet', 'generic_name' => 'Paracetamol', 'strength' => '500mg', 'form' => 'tablet',
            'category' => MedicineCategory::PainRelief->value, 'atc_code' => 'N02BE01', 'prescription_required' => false, 'currency' => 'XAF',
        ]);
        $this->item(null, $other);

        $this->actingAsStaff($this->staff('pharmacist'))->get('/pharmacy/prices?q=paracet')
            ->assertOk()
            ->assertSee('Paracetamol 500mg Tablet')
            ->assertDontSee('Amoxicillin 500mg Capsule');
    }

    // ── Setting a price: item + history + finder ──────────────────────────

    public function test_setting_a_price_writes_the_item_appends_history_and_publishes_to_the_finder(): void
    {
        $item       = $this->item();
        $pharmacist = $this->staff('pharmacist');

        $this->actingAsStaff($pharmacist)
            ->post('/pharmacy/prices/' . $item->id . '/price', ['price_xaf' => 2500, 'reason' => 'Supplier increase'])
            ->assertRedirect(route('portals.pharmacy.prices.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2500, $item->fresh()->selling_price_xaf);

        $change = InventoryPriceChange::where('inventory_item_id', $item->id)->firstOrFail();
        $this->assertNull($change->old_price_xaf);
        $this->assertSame(2500, $change->new_price_xaf);
        $this->assertSame($pharmacist->id, $change->changed_by);
        $this->assertSame($this->facility->id, $change->facility_id);
        $this->assertSame('Supplier increase', $change->reason);

        // The finder row the patient reads carries the price, in whole francs.
        $row = $this->finderRow();
        $this->assertNotNull($row);
        $this->assertSame(2500, (int) $row->unit_price);

        // A second price keeps the first in history and moves the finder.
        $this->actingAsStaff($pharmacist)
            ->post('/pharmacy/prices/' . $item->id . '/price', ['price_xaf' => 2300])
            ->assertRedirect();

        $this->assertSame(2300, $item->fresh()->selling_price_xaf);
        $this->assertSame(2300, (int) $this->finderRow()->unit_price);
        $this->assertSame(2, InventoryPriceChange::where('inventory_item_id', $item->id)->count());
        $this->assertSame(2500, InventoryPriceChange::where('inventory_item_id', $item->id)->latest('created_at')->orderByDesc('id')->first()->old_price_xaf);
    }

    public function test_a_price_change_row_cannot_be_edited_or_deleted(): void
    {
        $item = $this->item();
        $this->actingAsStaff($this->staff('pharmacist'))->post('/pharmacy/prices/' . $item->id . '/price', ['price_xaf' => 1000]);

        $change = InventoryPriceChange::firstOrFail();

        $this->assertFalse($change->update(['new_price_xaf' => 1]));
        $this->assertFalse($change->delete());
        $this->assertSame(1000, $change->fresh()->new_price_xaf);
        $this->assertSame(1, InventoryPriceChange::count());
    }

    // ── Whole francs only ─────────────────────────────────────────────────

    public function test_a_decimal_or_negative_price_is_refused_and_nothing_is_written(): void
    {
        $item       = $this->item();
        $pharmacist = $this->staff('pharmacist');

        foreach (['15.50', '-100', 'abc', '', '100000000'] as $bad) {
            $this->actingAsStaff($pharmacist)
                ->from('/pharmacy/prices')
                ->post('/pharmacy/prices/' . $item->id . '/price', ['price_xaf' => $bad])
                ->assertRedirect('/pharmacy/prices')
                ->assertSessionHasErrors('price_xaf');
        }

        $this->assertNull($item->fresh()->selling_price_xaf);
        $this->assertSame(0, InventoryPriceChange::count());
        $this->assertNull($this->finderRow());
    }

    // ── Scope ─────────────────────────────────────────────────────────────

    public function test_another_pharmacys_item_does_not_exist_here(): void
    {
        [$otherFacility] = $this->pharmacy('Pharmacie du Wouri');
        $theirs = $this->item($otherFacility);

        $mine = $this->staff('pharmacist');

        $this->actingAsStaff($mine)->post('/pharmacy/prices/' . $theirs->id . '/price', ['price_xaf' => 500])->assertNotFound();
        $this->actingAsStaff($mine)->get('/pharmacy/prices/' . $theirs->id . '/history')->assertNotFound();

        $this->assertNull($theirs->fresh()->selling_price_xaf);
        $this->assertSame(0, InventoryPriceChange::count());
    }

    public function test_a_role_without_the_capability_is_refused(): void
    {
        $item = $this->item();

        $this->actingAsStaff($this->staff('receptionist'))->get('/pharmacy/prices')->assertForbidden();
        $this->actingAsStaff($this->staff('receptionist'))->post('/pharmacy/prices/' . $item->id . '/price', ['price_xaf' => 500])->assertForbidden();

        $this->assertSame(0, InventoryPriceChange::count());
    }

    // ── History page ──────────────────────────────────────────────────────

    public function test_the_history_page_shows_old_and_new_prices(): void
    {
        $item       = $this->item();
        $pharmacist = $this->staff('pharmacist');

        $this->actingAsStaff($pharmacist)->post('/pharmacy/prices/' . $item->id . '/price', ['price_xaf' => 2500]);
        $this->actingAsStaff($pharmacist)->post('/pharmacy/prices/' . $item->id . '/price', ['price_xaf' => 2750, 'reason' => 'New tariff']);

        $this->actingAsStaff($pharmacist)->get('/pharmacy/prices/' . $item->id . '/history')
            ->assertOk()
            ->assertSee('Amoxicillin 500mg Capsule')
            ->assertSee(\App\Services\Billing\Xaf::format(2500))
            ->assertSee(\App\Services\Billing\Xaf::format(2750))
            ->assertSee('New tariff')
            ->assertSee(__('pharmacy_prices.no_price_yet'))
            ->assertSee($pharmacist->name);
    }

    // ── Goods receipt publishes the price list's price ────────────────────

    public function test_a_goods_receipt_for_a_priced_item_publishes_that_price(): void
    {
        $item       = $this->item();
        $pharmacist = $this->staff('pharmacist');

        $this->actingAsStaff($pharmacist)->post('/pharmacy/prices/' . $item->id . '/price', ['price_xaf' => 3200])->assertRedirect();
        $this->assertSame(3200, (int) $this->finderRow()->unit_price);

        $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/receipts', [
            'received_date' => now()->toDateString(),
            'lines'         => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 30, 'lot_number' => 'AMX-2026-A', 'expiry_date' => now()->addYear()->toDateString(), 'unit_cost' => 1500],
            ],
        ])->assertRedirect();

        $row = $this->finderRow();
        $this->assertSame(30, (int) $row->packs_available);
        $this->assertSame(3200, (int) $row->unit_price, 'The receipt republished the price list price, not nothing.');
    }

    public function test_a_goods_receipt_for_an_unpriced_item_keeps_whatever_the_public_page_declared(): void
    {
        $pharmacist = $this->staff('pharmacist');

        // The public page had declared a price before the ledger existed.
        MedicinePharmacyStock::create([
            'medicine_id'      => $this->medicine->id,
            'care_facility_id' => $this->listing->id,
            'stock_status'     => 'in_stock',
            'packs_available'  => 5,
            'unit_price'       => 1800,
            'currency'         => 'XAF',
            'source_system'    => 'pharmacy_portal',
            'last_reported_at' => now(),
        ]);

        $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/receipts', [
            'received_date' => now()->toDateString(),
            'lines'         => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 10, 'lot_number' => 'AMX-2026-B', 'expiry_date' => now()->addYear()->toDateString(), 'unit_cost' => 1500],
            ],
        ])->assertRedirect();

        $this->assertSame(1800, (int) $this->finderRow()->unit_price);
    }
}
