<?php

namespace Tests\Feature\Facility;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The shared "My facility" settings page (routes/facility_settings.php) for
 * every facility that is not a pharmacy: hospitals, clinics, health centres,
 * laboratories… It is a thin caller of the same FacilitySettingsService the
 * pharmacy page uses (PharmacySettingsTest holds the write rules in detail);
 * these hold the gate for the roles that reach THIS page — a facility admin
 * lives under /admin, a lab manager on the /facility desk, and both must
 * arrive here while a doctor or a pharmacist is refused with a readable 403
 * — the type-aware title, one reviewed write, and the unlinked-tenant rule.
 */
class FacilitySettingsTest extends TestCase
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
    private function tenant(string $roleName, string $type = 'hospital', string $name = 'Hôpital Laquintinie'): array
    {
        $facility = Facility::factory()->create(['type' => $type, 'name' => $name]);
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$facility, $user];
    }

    private function listing(Facility $facility, string $type = 'hospital', array $overrides = []): CareFacility
    {
        return CareFacility::create(array_merge([
            'facility_id' => $facility->id, 'facility_name' => $facility->name, 'facility_type' => $type,
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala',
            'address' => 'Boulevard de la Liberté', 'latitude' => null, 'longitude' => null, 'phone_primary' => 'N/A',
        ], $overrides));
    }

    private function as(User $user, Facility $facility)
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    // ── Gate ────────────────────────────────────────────────────────────────

    public function test_a_facility_admin_of_a_hospital_opens_the_page_titled_for_a_hospital_and_saves_a_position(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin');
        $listing = $this->listing($facility);

        $this->as($admin, $facility)->get(route('portals.facility.settings.index'))
            ->assertOk()
            ->assertSee(__('facility_settings.title_by_type.hospital'))
            ->assertSee('Hôpital Laquintinie')
            ->assertSee(__('facility_settings.warn_no_coordinates'))
            ->assertSee('data-lucide="hospital"', false)
            ->assertSee(route('portals.facility.settings.location'));

        $this->as($admin, $facility)->post(route('portals.facility.settings.location'), [
            'address' => 'Boulevard de la Liberté', 'city' => 'Douala', 'region' => 'Littoral',
            'latitude' => '4.048000', 'longitude' => '9.706000',
        ])->assertRedirect(route('portals.facility.settings.index'))->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertEqualsWithDelta(4.048, $listing->latitude, 1e-6);
        $this->assertEqualsWithDelta(9.706, $listing->longitude, 1e-6);
        $this->assertNotNull($listing->last_profile_update_at);
        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id' => $listing->id, 'actor_id' => $admin->id, 'actor_type' => 'facility',
            'field_changed' => 'latitude', 'new_value' => '4.048', 'requires_review' => true,
        ]);
    }

    public function test_a_lab_manager_of_a_laboratory_opens_the_page_titled_for_a_laboratory(): void
    {
        [$facility, $manager] = $this->tenant('lab_manager', 'laboratory', 'Laboratoire Central');
        $this->listing($facility, 'laboratory');

        $this->as($manager, $facility)->get(route('portals.facility.settings.index'))
            ->assertOk()
            ->assertSee(__('facility_settings.title_by_type.laboratory'))
            ->assertSee('Laboratoire Central')
            ->assertSee('data-lucide="flask-conical"', false);
    }

    public function test_a_doctor_and_a_pharmacist_are_refused_with_403_not_redirected(): void
    {
        foreach (['doctor', 'pharmacist'] as $roleName) {
            [$facility, $user] = $this->tenant($roleName);
            $this->listing($facility);

            $this->as($user, $facility)->get(route('portals.facility.settings.index'))->assertForbidden();
            $this->as($user, $facility)->post(route('portals.facility.settings.location'), [
                'address' => 'x', 'city' => 'y', 'latitude' => 4, 'longitude' => 9,
            ])->assertForbidden();
            $this->as($user, $facility)->post(route('portals.facility.settings.identity'), [
                'facility_name' => 'Renamed by ' . $roleName,
            ])->assertForbidden();

            $this->assertSame('Hôpital Laquintinie', $facility->refresh()->name);
        }
    }

    // ── Identity through the shared service ─────────────────────────────────

    public function test_renaming_a_verified_hospital_drops_it_to_self_reported_and_the_tenant_follows(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin');
        $listing = $this->listing($facility, 'hospital', ['verification_status' => 'government_verified']);

        $this->as($admin, $facility)->post(route('portals.facility.settings.identity'), [
            'facility_name' => 'Hôpital Général de Douala', 'license_number' => 'MINSANTE-HGD',
        ])->assertRedirect(route('portals.facility.settings.index'))->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertSame('Hôpital Général de Douala', $listing->facility_name);
        $this->assertSame('Hôpital Général de Douala', $facility->refresh()->name);
        $this->assertSame('self_reported', $listing->verification_status);
        $this->assertSame('active', $listing->listing_status, 'fixing a name never removes a hospital from search');
        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id' => $listing->id, 'field_changed' => 'verification_status', 'new_value' => 'self_reported',
        ]);
    }

    // ── Unlinked tenant ─────────────────────────────────────────────────────

    public function test_an_unlinked_tenant_is_explained_to_and_never_auto_linked(): void
    {
        [$facility, $admin] = $this->tenant('facility_admin', 'clinic', 'Clinique Sans Fiche');

        $this->as($admin, $facility)->get(route('portals.facility.settings.index'))
            ->assertOk()
            ->assertSee('Clinique Sans Fiche')
            ->assertSee(__('facility_settings.title_by_type.clinic'))
            ->assertSee(__('facility_settings.not_linked_title'))
            ->assertSee(route('portals.listing.claims'));

        $this->as($admin, $facility)->post(route('portals.facility.settings.identity'), ['facility_name' => 'Anything'])
            ->assertNotFound();
        $this->as($admin, $facility)->post(route('portals.facility.settings.hours'), ['hours' => [1 => ['opens_at' => '08:00', 'closes_at' => '17:00']]])
            ->assertNotFound();

        $this->assertSame(0, CareFacility::count(), 'no listing may be created or linked from this page');
        $this->assertSame('Clinique Sans Fiche', $facility->refresh()->name);
    }
}
