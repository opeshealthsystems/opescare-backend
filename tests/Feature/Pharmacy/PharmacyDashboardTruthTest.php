<?php

namespace Tests\Feature\Pharmacy;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pharmacy landing page used to read `pharmacy_inventories`, a table no
 * pharmacy screen writes, and fell back to "the first facility in the table"
 * when the user had no facility context. These hold the corrected behaviour:
 * KPIs come from the published stock rows, an unscoped user sees an empty
 * desk, patient names render, and a pharmacy manager can reach the team page.
 */
class PharmacyDashboardTruthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Real roles with their dashboard profiles, so the layout picks the
        // pharmacy sidebars and @capability resolves from the one map.
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function pharmacyUser(string $roleName = 'pharmacist'): array
    {
        $facility = Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Pharmacie du Marché']);
        $listing = $this->listing($facility, 'Pharmacie du Marché');
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

    private function stockRow(CareFacility $listing, string $status, string $name): MedicinePharmacyStock
    {
        $medicine = Medicine::create([
            'name' => $name, 'generic_name' => $name, 'strength' => '500mg', 'form' => 'tablet',
            'category' => \App\Enums\MedicineCategory::PainRelief->value, 'prescription_required' => false,
            'default_pack_size' => '10 tablets', 'currency' => 'XAF',
        ]);

        return MedicinePharmacyStock::create([
            'medicine_id' => $medicine->id,
            'care_facility_id' => $listing->id,
            'stock_status' => $status,
            'packs_available' => $status === 'out_of_stock' ? 0 : 3,
            'source_system' => 'portal',
            'last_reported_at' => now(),
        ]);
    }

    public function test_dashboard_counts_the_published_stock_rows(): void
    {
        [, $listing, $user] = $this->pharmacyUser();
        $this->stockRow($listing, 'in_stock', 'Paracetamol 500mg');
        $this->stockRow($listing, 'low_stock', 'Amoxicillin 500mg');
        $this->stockRow($listing, 'out_of_stock', 'Artemether-Lumefantrine');

        // Another pharmacy's rows must not leak into these numbers.
        $other = $this->listing(Facility::factory()->create(['type' => 'pharmacy']), 'Pharmacie Voisine');
        $this->stockRow($other, 'out_of_stock', 'Metformin 500mg');

        $html = $this->actingAs($user)->withSession(['active_facility_id' => $user->primary_facility_id])
            ->get(route('portals.pharmacy.dashboard'))
            ->assertOk()
            ->assertSee('Amoxicillin 500mg')
            ->assertSee('Artemether-Lumefantrine')
            ->assertDontSee('Metformin 500mg')
            ->getContent();

        // Three drug lines, one low, one out — read off the tiles in order.
        preg_match_all('/stat-card__value">(\d+)</', $html, $m);
        $this->assertSame(['0', '0', '3', '1', '1', '0'], $m[1]);
    }

    public function test_a_user_with_no_facility_sees_an_empty_desk_not_a_strangers(): void
    {
        [$facility, , $owner] = $this->pharmacyUser();
        $patient = Patient::factory()->create(['first_name' => 'Ngono', 'last_name' => 'Ateba']); // is_demo follows the running mode (IsDemoRecord), so the row stays visible
        Prescription::factory()->create([
            'facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'active',
        ]);

        // The owner sees their queue with the patient's name rendered.
        $this->actingAs($owner)->withSession(['active_facility_id' => $facility->id])
            ->get(route('portals.pharmacy.dashboard'))
            ->assertOk()
            ->assertSee('Ngono Ateba');

        // A pharmacist with no facility at all used to be shown this queue.
        $role = Role::where('name', 'pharmacist')->first();
        $stray = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => null]);
        // withSession() merges, so the owner's active_facility_id would carry
        // over into this request and fake a facility context. Start clean.
        $this->flushSession();
        $this->actingAs($stray)
            ->get(route('portals.pharmacy.dashboard'))
            ->assertDontSee('Ngono Ateba');
    }

    public function test_pharmacy_manager_reaches_the_team_page(): void
    {
        [$facility, , $manager] = $this->pharmacyUser('pharmacy_manager');

        $this->actingAs($manager)->withSession(['active_facility_id' => $facility->id])
            ->get(route('portals.facility.team'))
            ->assertOk();

        // And the link is on the sidebar of the pharmacy dashboard.
        $this->actingAs($manager)->withSession(['active_facility_id' => $facility->id])
            ->get(route('portals.pharmacy.dashboard'))
            ->assertSee(route('portals.facility.team'), false);
    }

    public function test_a_pharmacist_is_still_kept_out_of_the_team_page(): void
    {
        [$facility, , $pharmacist] = $this->pharmacyUser('pharmacist');

        $this->actingAs($pharmacist)->withSession(['active_facility_id' => $facility->id])
            ->get(route('portals.facility.team'))
            ->assertStatus(403);
    }
}
