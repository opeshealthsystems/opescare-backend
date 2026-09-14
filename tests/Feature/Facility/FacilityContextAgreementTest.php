<?php

namespace Tests\Feature\Facility;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The four facility-admin pages resolve the facility by ONE rule.
 *
 * Before 2026-09-14, /facility/team and /facility/catalogue ran the
 * `facility.context` middleware (RequireFacilityContext: silent for a
 * single-facility user, /select-facility for a multi-facility one) while
 * /facility/team/overview and /facility/settings did not — their controllers
 * read PortalContextService::facilityId(), which falls back to
 * primary_facility_id. The demo facility admin, posted at two hospitals by a
 * seeder re-run, therefore got the overview at one hospital and the selector
 * on the team page. The rule chosen is the honest one: no silent guess for a
 * user posted at more than one DISTINCT facility, and duplicate postings at
 * the same facility are not "multi-facility" (FacilityMembershipService
 * already de-duplicates). Both cases must answer the same on all four pages.
 */
class FacilityContextAgreementTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        '/facility/team',
        '/facility/catalogue',
        '/facility/team/overview',
        '/facility/settings',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function admin(Facility $primary): User
    {
        $role = Role::where('name', 'facility_admin')->firstOrFail();

        return User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $primary->id,
            'status'              => 'active',
        ]);
    }

    private function postAt(User $user, Facility $facility): void
    {
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id,
            'role_id' => $user->role_id, 'is_active' => true, 'assigned_at' => now(),
        ]);
    }

    /** A fresh session per request: the middleware must decide every time, not once. */
    private function open(User $user, string $path)
    {
        $this->flushSession();

        return $this->actingAs($user)->get($path);
    }

    public function test_two_postings_at_the_same_facility_are_one_facility_and_every_page_opens(): void
    {
        $facility = Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital Laquintinie']);
        $admin    = $this->admin($facility);
        // The seeder-ran-twice shape: primary_facility_id AND an assignment,
        // both at the same facility.
        $this->postAt($admin, $facility);

        foreach (self::PAGES as $path) {
            $this->open($admin, $path)->assertOk();
        }
    }

    public function test_postings_at_two_different_facilities_send_every_page_to_the_selector(): void
    {
        $here  = Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital A']);
        $there = Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital B']);
        $admin = $this->admin($here);
        $this->postAt($admin, $here);
        $this->postAt($admin, $there);

        foreach (self::PAGES as $path) {
            $this->open($admin, $path)
                ->assertStatus(302)
                ->assertRedirect(route('select-facility'));
        }
    }

    public function test_once_a_facility_is_chosen_every_page_opens_at_that_facility(): void
    {
        $here  = Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital A']);
        $there = Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital B']);
        $admin = $this->admin($here);
        $this->postAt($admin, $here);
        $this->postAt($admin, $there);

        foreach (self::PAGES as $path) {
            $this->flushSession();
            $this->actingAs($admin)->withSession(['active_facility_id' => $there->id])
                ->get($path)->assertOk();
        }
    }
}
