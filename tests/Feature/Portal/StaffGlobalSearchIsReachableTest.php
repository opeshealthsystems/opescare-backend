<?php

namespace Tests\Feature\Portal;

use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The staff global-search screen existed — controller method, a complete
 * 133-line view, lang keys in both languages — with no route. The dashboard
 * audit listed it as the platform's only unreachable finished screen.
 *
 * It searches facilities, documents, medicines, lab tests, partners and the
 * caller's own messages. It does NOT search patients: that branch was a
 * name-prefix LIKE across the register and was deleted outright (S7), so no
 * capability beyond the portal is needed and none is applied.
 */
class StaffGlobalSearchIsReachableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    private function facilityUser(string $roleName): array
    {
        $facility = Facility::factory()->create();
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => $facility->id]);
        $user->role_id = $role->id;
        $user->save();

        return [$user->fresh(), $facility];
    }

    public function test_a_facility_role_can_open_the_search_screen(): void
    {
        [$user, $facility] = $this->facilityUser('receptionist');

        $this->actingAs($user)
            ->withSession(['active_facility_id' => $facility->id, 'mfa.verified' => true])
            ->get('/facility/search?q=amox')
            ->assertOk()
            ->assertSee(__('public.staff_portal.page_heading_search'));
    }

    public function test_the_search_link_is_in_every_human_facility_sidebar(): void
    {
        // Sixteen sidebars land on /facility. device_kiosk and lite are not
        // human roles and demo_access belongs to no role — none of those
        // three should carry the link, and none does.
        $expected = ['data_steward', 'doctor', 'lab_manager', 'labtech', 'medical_supervisor', 'midwife',
            'nurse', 'nurse_supervisor', 'receptionist', 'records_officer', 'resident_doctor',
            'sample_collection', 'specialist_doctor', 'student_clinical', 'triage_nurse', 'ward_nurse'];

        foreach ($expected as $partial) {
            $blade = file_get_contents(resource_path("views/partials/sidebars/{$partial}.blade.php"));
            $this->assertStringContainsString("route('portals.staff.search')", $blade, $partial);
        }

        foreach (['device_kiosk', 'lite', 'demo_access'] as $partial) {
            $blade = file_get_contents(resource_path("views/partials/sidebars/{$partial}.blade.php"));
            $this->assertStringNotContainsString("route('portals.staff.search')", $blade, $partial);
        }
    }

    public function test_a_patient_role_cannot_open_it(): void
    {
        $role = Role::where('name', 'patient')->firstOrFail();
        $user = User::factory()->create(['status' => 'active']);
        $user->role_id = $role->id;
        $user->save();

        $response = $this->actingAs($user->fresh())
            ->withSession(['mfa.verified' => true])
            ->get('/facility/search');

        $this->assertNotSame(200, $response->getStatusCode());
    }
}
