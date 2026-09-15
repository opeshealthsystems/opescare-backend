<?php

namespace Tests\Feature\HealthCentre;

use App\Models\CareFacility;
use App\Models\CareFacilityService;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A health centre (Centre de Santé Intégré) must be able to publish what it
 * offers — child immunisation, antenatal care, malaria RDT — where patients
 * see it: the Finder profile, which lists care_facility_services. Before
 * this, that table was writable only from the claim-based /portals/listing
 * editor, which a facility admin has no sidebar link to and no claim for.
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

    public function test_a_health_centre_admin_publishes_immunisation_and_patients_see_it_on_the_profile(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin', 'CSI Nkolbisson');
        $listing = $this->listing($facility);

        $this->as($admin, $facility)->get(route('portals.facility.settings.index'))
            ->assertOk()
            ->assertSee(__('facility_settings.services_intro'))
            ->assertSee(route('portals.facility.settings.services.store'));

        $this->as($admin, $facility)->post(route('portals.facility.settings.services.store'), [
            'service_name' => 'Vaccination des enfants (PEV)', 'service_category' => 'preventive',
            'availability_status' => 'available', 'walk_in_allowed' => '1',
            // A body facility id must be ignored.
            'facility_id' => 'not-this-one',
        ])->assertRedirect(route('portals.facility.settings.index'));

        $this->assertDatabaseHas('care_facility_services', [
            'facility_id' => $listing->id, 'service_name' => 'Vaccination des enfants (PEV)', 'service_category' => 'preventive',
        ]);

        $this->get(route('public.care-map.profile', $listing->id))
            ->assertOk()
            ->assertSee('Vaccination des enfants (PEV)');
    }

    public function test_an_unknown_category_is_refused(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin', 'CSI Mvog-Ada');
        $this->listing($facility);

        $this->as($admin, $facility)->post(route('portals.facility.settings.services.store'), [
            'service_name' => 'X', 'service_category' => 'wards', 'availability_status' => 'available',
        ])->assertSessionHasErrors('service_category');

        $this->assertDatabaseCount('care_facility_services', 0);
    }

    public function test_another_centres_service_cannot_be_removed(): void
    {
        [$facilityA, $adminA] = $this->tenant('facility_admin', 'CSI A');
        $this->listing($facilityA);
        [$facilityB] = $this->tenant('facility_admin', 'CSI B');
        $listingB = $this->listing($facilityB);

        $svc = CareFacilityService::create([
            'facility_id' => $listingB->id, 'service_name' => 'Consultation prénatale', 'service_category' => 'maternity',
            'availability_status' => 'available', 'last_updated_at' => now(),
        ]);

        $this->as($adminA, $facilityA)->delete(route('portals.facility.settings.services.destroy', $svc->id))
            ->assertNotFound();

        $this->assertDatabaseHas('care_facility_services', ['id' => $svc->id]);
    }

    public function test_a_nurse_or_midwife_cannot_publish_services(): void
    {
        foreach (['nurse', 'midwife'] as $roleName) {
            [$facility, $user] = $this->tenant($roleName, 'CSI ' . $roleName);
            $this->listing($facility);

            $this->as($user, $facility)->post(route('portals.facility.settings.services.store'), [
                'service_name' => 'TDR paludisme', 'service_category' => 'diagnostic', 'availability_status' => 'available',
            ])->assertForbidden();
        }

        $this->assertDatabaseCount('care_facility_services', 0);
    }

    public function test_an_unlinked_centre_cannot_publish(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin', 'CSI sans fiche');

        $this->as($admin, $facility)->get(route('portals.facility.settings.index'))->assertOk();

        $this->as($admin, $facility)->post(route('portals.facility.settings.services.store'), [
            'service_name' => 'TDR paludisme', 'service_category' => 'diagnostic', 'availability_status' => 'available',
        ])->assertNotFound();
    }
}
