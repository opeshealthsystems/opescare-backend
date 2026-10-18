<?php

namespace Tests\Feature\Pharmacy;

use App\Enums\MedicineCategory;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The supplier register: list, card, edit — facility-scoped, stock roles only.
 */
class PharmacySuppliersDeskTest extends TestCase
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
            'name'                  => 'Paracetamol 500mg Tablet',
            'generic_name'          => 'Paracetamol',
            'strength'              => '500mg',
            'form'                  => 'tablet',
            'category'              => MedicineCategory::PainRelief->value,
            'atc_code'              => 'N02BE01',
            'prescription_required' => false,
            'default_pack_size'     => '20 tablets',
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

    private function supplier(Facility $facility, string $name = 'Laborex Douala'): Supplier
    {
        return Supplier::create([
            'facility_id'    => $facility->id,
            'name'           => $name,
            'contact_person' => 'Mme Ngo',
            'phone'          => '+237699000111',
            'status'         => 'active',
        ]);
    }

    public function test_the_stock_roles_see_the_register_and_a_card_with_its_deliveries_items_and_open_orders(): void
    {
        $pharmacist = $this->staff('pharmacist');
        $supplier   = $this->supplier($this->facility);

        $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/receipts', [
            'supplier_id'    => $supplier->id,
            'received_date'  => now()->toDateString(),
            'receipt_number' => 'BL-7001',
            'lines'          => [['medicine_id' => $this->medicine->id, 'quantity' => 12, 'lot_number' => 'PCM-1']],
        ])->assertRedirect();

        PurchaseOrder::create([
            'facility_id' => $this->facility->id,
            'supplier_id' => $supplier->id,
            'po_number'   => 'PO-0042',
            'status'      => 'sent',
            'order_date'  => now()->toDateString(),
        ]);

        foreach (['pharmacist', 'medicine_stock', 'pharmacy_manager'] as $roleName) {
            $user = $this->staff($roleName);

            $this->actingAsStaff($user)->get('/pharmacy/stock/suppliers')
                ->assertOk()
                ->assertSee('Laborex Douala')
                ->assertSee(route('portals.pharmacy.stock.suppliers.show', $supplier->id), false);

            $this->actingAsStaff($user)->get('/pharmacy/stock/suppliers/' . $supplier->id)
                ->assertOk()
                ->assertSee('Mme Ngo')
                ->assertSee('BL-7001')
                ->assertSee('Paracetamol 500mg Tablet')
                ->assertSee('PO-0042');
        }
    }

    public function test_a_supplier_update_persists_and_lands_back_on_the_card(): void
    {
        $manager  = $this->staff('pharmacy_manager');
        $supplier = $this->supplier($this->facility);

        $this->actingAsStaff($manager)->post('/pharmacy/stock/suppliers/' . $supplier->id, [
            'name'           => 'Laborex Douala SA',
            'contact_person' => 'M. Tchoupo',
            'phone'          => '+237699000222',
            'email'          => 'orders@laborex.example',
            'address'        => 'Zone portuaire, Douala',
            'notes'          => 'Delivers on Tuesdays',
            'status'         => 'inactive',
        ])->assertRedirect(route('portals.pharmacy.stock.suppliers.show', $supplier->id));

        $fresh = $supplier->fresh();
        $this->assertSame('Laborex Douala SA', $fresh->name);
        $this->assertSame('M. Tchoupo', $fresh->contact_person);
        $this->assertSame('+237699000222', $fresh->phone);
        $this->assertSame('orders@laborex.example', $fresh->email);
        $this->assertSame('Zone portuaire, Douala', $fresh->address);
        $this->assertSame('Delivers on Tuesdays', $fresh->notes);
        $this->assertSame('inactive', $fresh->status);

        // A status the desk does not offer is refused, not silently mapped.
        $this->actingAsStaff($manager)->post('/pharmacy/stock/suppliers/' . $supplier->id, [
            'name' => 'Laborex Douala SA', 'status' => 'blacklisted',
        ])->assertSessionHasErrors('status');
        $this->assertSame('inactive', $supplier->fresh()->status);
    }

    public function test_creating_from_the_register_lands_on_the_new_card(): void
    {
        $pharmacist = $this->staff('pharmacist');

        $res = $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/suppliers', ['name' => 'UCPharm']);

        $created = Supplier::where('facility_id', $this->facility->id)->where('name', 'UCPharm')->firstOrFail();
        $res->assertRedirect(route('portals.pharmacy.stock.suppliers.show', $created->id));

        // From the receipt form the pharmacist is sent back to the receipt.
        $this->actingAsStaff($pharmacist)->post('/pharmacy/stock/suppliers', ['name' => 'Sopharma', 'return' => 'receipt'])
            ->assertRedirect(route('portals.pharmacy.stock.receipts.create'));
    }

    public function test_the_front_desk_is_refused_and_another_pharmacys_supplier_is_not_found(): void
    {
        $supplier = $this->supplier($this->facility);

        $receptionist = $this->staff('receptionist');
        $this->actingAsStaff($receptionist)->get('/pharmacy/stock/suppliers')->assertForbidden();
        $this->actingAsStaff($receptionist)->get('/pharmacy/stock/suppliers/' . $supplier->id)->assertForbidden();
        $this->actingAsStaff($receptionist)->post('/pharmacy/stock/suppliers/' . $supplier->id, ['name' => 'X', 'status' => 'active'])->assertForbidden();

        [$other] = $this->pharmacy('Pharmacie du Rond-Point');
        $theirs  = $this->staff('pharmacist', $other);

        $this->actingAsStaff($theirs, $other)->get('/pharmacy/stock/suppliers/' . $supplier->id)->assertNotFound();
        $this->actingAsStaff($theirs, $other)->post('/pharmacy/stock/suppliers/' . $supplier->id, ['name' => 'Hijacked', 'status' => 'active'])->assertNotFound();
        $this->assertSame('Laborex Douala', $supplier->fresh()->name);

        // And their register does not list mine.
        $this->actingAsStaff($theirs, $other)->get('/pharmacy/stock/suppliers')->assertOk()->assertDontSee('Laborex Douala');
    }
}
