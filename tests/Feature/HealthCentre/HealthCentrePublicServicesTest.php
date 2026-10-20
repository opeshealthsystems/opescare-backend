<?php

namespace Tests\Feature\HealthCentre;

use App\Models\CareFacility;
use App\Models\CareFacilityService;
use App\Models\ClinicalCodeDictionary;
use App\Models\Facility;
use App\Models\FacilityCatalogueItem;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A health centre (Centre de Sante Integre) publishes what it offers where
 * patients see it: the Finder profile, which lists care_facility_services.
 *
 * Since 2026-10-20 (owner's decision) that list is DERIVED from the facility
 * service catalogue by PublicServicesSync: the catalogue is the single
 * editor, and the settings page only shows the list and links to it.
 */
class HealthCentrePublicServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: Facility, 1: User} */
    private function tenant(string $roleName, string $name): array
    {
        $facility = Facility::factory()->create(['type' => 'health_center', 'name' => $name]);
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$facility, $user];
    }

    private function listing(Facility $facility): CareFacility
    {
        return CareFacility::create([
            'facility_id' => $facility->id, 'facility_name' => $facility->name, 'facility_type' => 'health_center',
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Centre', 'city' => 'Yaoundé',
            'address' => 'Nkolbisson', 'latitude' => null, 'longitude' => null, 'phone_primary' => 'N/A',
        ]);
    }

    private function as(User $user, Facility $facility)
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function item(Facility $facility, string $system, string $code, array $extra = []): FacilityCatalogueItem
    {
        return FacilityCatalogueItem::create(array_merge([
            'facility_id' => $facility->id, 'system' => $system, 'code' => $code, 'is_active' => true,
        ], $extra));
    }

    private function catalogueRows(CareFacility $listing)
    {
        return CareFacilityService::where('facility_id', $listing->id)
            ->where('source', CareFacilityService::SOURCE_CATALOGUE)->get();
    }

    public function test_a_catalogue_item_appears_on_the_public_profile(): void
    {
        [$facility] = $this->tenant('facility_admin', 'CSI Nkolbisson');
        $listing = $this->listing($facility);

        ClinicalCodeDictionary::create([
            'id' => (string) Str::uuid(), 'system' => 'CVX', 'code' => '08', 'display' => 'Hepatitis B vaccine, pediatric',
            'kind' => 'vaccine', 'is_active' => true, 'source' => 'CDC CVX', 'source_version' => '2026',
        ]);

        $item = $this->item($facility, 'CVX', '08');
        $named = $this->item($facility, 'ICHI', 'XYZ.AA.ZZ', ['local_name' => 'Vaccination des enfants (PEV)']);

        $rows = $this->catalogueRows($listing);
        $this->assertCount(2, $rows);
        $hepB = $rows->firstWhere('catalogue_item_id', $item->id);
        $this->assertSame('Hepatitis B vaccine, pediatric', $hepB->service_name);
        $this->assertSame('preventive', $hepB->service_category);
        $this->assertSame('CVX:08', $hepB->service_code);
        $this->assertNull($hepB->price_range, 'No price band is ever invented');
        $this->assertSame('Vaccination des enfants (PEV)', $rows->firstWhere('catalogue_item_id', $named->id)->service_name);

        $this->get(route('public.care-map.profile', $listing->id))
            ->assertOk()
            ->assertSee('Vaccination des enfants (PEV)')
            ->assertSee('Hepatitis B vaccine, pediatric');
    }

    public function test_renaming_updates_and_retiring_or_deleting_removes_the_public_row(): void
    {
        [$facility] = $this->tenant('facility_admin', 'CSI Mvog-Ada');
        $listing = $this->listing($facility);

        $a = $this->item($facility, 'ICHI', 'AAA.AA.ZZ', ['local_name' => 'Consultation prénatale']);
        $b = $this->item($facility, 'ICHI', 'BBB.AA.ZZ', ['local_name' => 'TDR paludisme']);
        $this->assertCount(2, $this->catalogueRows($listing));

        $a->update(['local_name' => 'CPN']);
        $this->assertSame('CPN', $this->catalogueRows($listing)->firstWhere('catalogue_item_id', $a->id)->service_name);

        $a->update(['is_active' => false, 'deactivated_at' => now()]);
        $this->assertSame([$b->id], $this->catalogueRows($listing)->pluck('catalogue_item_id')->all());

        $a->update(['is_active' => true, 'deactivated_at' => null]);
        $this->assertCount(2, $this->catalogueRows($listing));

        $b->delete();
        $this->assertSame([$a->id], $this->catalogueRows($listing)->pluck('catalogue_item_id')->all());

        $this->get(route('public.care-map.profile', $listing->id))
            ->assertOk()->assertSee('CPN')->assertDontSee('TDR paludisme');
    }

    public function test_another_facilitys_catalogue_never_reaches_this_listing(): void
    {
        [$facilityA] = $this->tenant('facility_admin', 'CSI A');
        $listingA = $this->listing($facilityA);
        [$facilityB] = $this->tenant('facility_admin', 'CSI B');
        $listingB = $this->listing($facilityB);

        $this->item($facilityB, 'ICHI', 'BBB.AA.ZZ', ['local_name' => 'Hémodialyse']);

        $this->assertCount(0, $this->catalogueRows($listingA));
        $this->assertCount(1, $this->catalogueRows($listingB));
        $this->get(route('public.care-map.profile', $listingA->id))->assertOk()->assertDontSee('Hémodialyse');
    }

    public function test_manual_rows_stay_until_the_catalogue_has_an_item_then_are_replaced_and_audited(): void
    {
        [$facility] = $this->tenant('facility_admin', 'CSI legacy');
        $listing = $this->listing($facility);

        $manual = CareFacilityService::create([
            'facility_id' => $listing->id, 'service_name' => 'Ancienne ligne', 'service_category' => 'maternity',
            'availability_status' => 'available', 'last_updated_at' => now(),
        ]);
        $this->assertSame(CareFacilityService::SOURCE_MANUAL, $manual->fresh()->source);

        // A retired item does not count: the catalogue has nothing ACTIVE yet.
        $this->item($facility, 'ICHI', 'OLD.AA.ZZ', ['is_active' => false]);
        $this->assertDatabaseHas('care_facility_services', ['id' => $manual->id]);

        $this->item($facility, 'ICHI', 'NEW.AA.ZZ', ['local_name' => 'Accouchement']);
        $this->assertDatabaseMissing('care_facility_services', ['id' => $manual->id]);
        $this->assertTrue(DB::table('facility_update_audits')
            ->where('facility_id', $listing->id)
            ->where('field_changed', 'service:' . $manual->id)
            ->where('old_value', 'Ancienne ligne')
            ->exists());
    }

    public function test_the_backfill_command_is_idempotent_and_honours_dry_run(): void
    {
        [$facility] = $this->tenant('facility_admin', 'CSI backfill');
        // Written BEFORE the listing was linked: nothing to sync to yet.
        $this->item($facility, 'ICHI', 'AAA.AA.ZZ', ['local_name' => 'Petite chirurgie']);
        $listing = $this->listing($facility);
        $this->assertCount(0, $this->catalogueRows($listing));

        $this->artisan('caremap:sync-public-services', ['--dry-run' => true])->assertSuccessful();
        $this->assertCount(0, $this->catalogueRows($listing));

        $this->artisan('caremap:sync-public-services')->assertSuccessful();
        $this->assertCount(1, $this->catalogueRows($listing));
        $stamp = $this->catalogueRows($listing)->first()->updated_at;

        $this->artisan('caremap:sync-public-services')->assertSuccessful();
        $this->assertCount(1, $this->catalogueRows($listing));
        $this->assertEquals($stamp, $this->catalogueRows($listing)->first()->updated_at, 'A second run writes nothing');
    }

    public function test_the_settings_page_shows_the_list_read_only_and_links_to_the_catalogue(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin', 'CSI page');
        $this->listing($facility);
        $this->item($facility, 'ICHI', 'AAA.AA.ZZ', ['local_name' => 'Planification familiale']);

        $this->as($admin, $facility)->get(route('portals.facility.settings.index'))
            ->assertOk()
            ->assertSee(__('facility_settings.services_from_catalogue'))
            ->assertSee(route('portals.facility.catalogue'))
            ->assertSee('Planification familiale')
            ->assertDontSee('name="service_name"', false);
    }

    public function test_the_old_service_write_routes_are_gone(): void
    {
        foreach ([
            'portals.facility.settings.services.store', 'portals.facility.settings.services.destroy',
            'portals.listing.services.store', 'portals.listing.services.destroy',
        ] as $name) {
            $this->assertFalse(Route::has($name), $name);
        }
    }
}
