<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\RequirePlatformAdmin;
use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Assigning users.primary_facility_id from the admin user-management screen.
 *
 * Why this suite exists: every staff portal now resolves its acting facility
 * through PortalContextService::facilityId() and fails closed when nothing
 * resolves. That removed a Facility::value('id') fallback which used to hand a
 * facility-less user somebody else's facility — but it also means an account
 * created without a facility can open no portal at all. The admin screen had no
 * way to set the column, so onboarding a member of staff was impossible.
 *
 * The privilege boundary is the point of the suite. A facility-tier admin may
 * only ever name the facility they are already acting in; naming any other
 * facility hands out a route to another facility's patient data and must be
 * refused. Platform-tier admins (the OpesCare company roles listed in
 * RequirePlatformAdmin::PLATFORM_ROLES) may assign any facility.
 */
class AdminUserFacilityAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Facility $ownFacility;
    private Facility $otherFacility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        $this->ownFacility   = Facility::factory()->create(['name' => 'Bamenda Regional Hospital']);
        $this->otherFacility = Facility::factory()->create(['name' => 'Douala General Hospital']);
    }

    private function userWithRole(string $roleName, ?string $facilityId = null): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        $user = User::factory()->create([
            'status'              => 'active',
            'primary_facility_id' => $facilityId,
        ]);

        $user->role_id = $role->id;   // role_id is guarded — assign explicitly
        $user->save();

        return $user->fresh();
    }

    /**
     * A platform-tier admin. `platform_admin` rather than `super_admin`:
     * super_admin is in config('mfa.required_roles') and would be bounced to
     * the MFA challenge before reaching the controller.
     */
    private function platformAdmin(): User
    {
        return $this->userWithRole('platform_admin');
    }

    private function facilityAdmin(Facility $facility): User
    {
        return $this->userWithRole('facility_admin', $facility->id);
    }

    private function doctorRoleId(): string
    {
        return (string) Role::where('name', 'doctor')->firstOrFail()->id;
    }

    /**
     * A facility-tier admin cannot reach /admin/users at all today —
     * RequirePlatformAdmin 403s them at the route. That guard is asserted
     * separately below. These cases disable only that middleware so the
     * CONTROLLER's own authorisation is what is under test: a route-level gate
     * that is the single thing standing between a facility admin and another
     * facility's data is one route registration away from being gone.
     */
    private function actingAsFacilityAdminAtControllerLevel(User $admin): self
    {
        $this->actingAs($admin)->withoutMiddleware(RequirePlatformAdmin::class);

        return $this;
    }

    // ── Platform tier: may assign any facility ───────────────────────────

    public function test_platform_admin_can_assign_a_primary_facility_when_creating_a_user(): void
    {
        $response = $this->actingAs($this->platformAdmin())->post('/admin/users', [
            'name'                => 'Dr Ada Nkemcha',
            'email'               => 'ada.nkemcha@example.test',
            'password'            => 'correct-horse-8',
            'role_id'             => $this->doctorRoleId(),
            'primary_facility_id' => $this->otherFacility->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'ada.nkemcha@example.test')->first();

        $this->assertNotNull($created, 'the admin screen must create the user');
        $this->assertSame(
            $this->otherFacility->id,
            $created->primary_facility_id,
            'a platform admin must be able to assign any facility'
        );
    }

    public function test_platform_admin_can_change_an_existing_users_primary_facility(): void
    {
        $staff = $this->userWithRole('doctor', $this->ownFacility->id);

        $this->actingAs($this->platformAdmin())
            ->put('/admin/users/' . $staff->id, [
                'name'                => $staff->name,
                'email'               => $staff->email,
                'role_id'             => $staff->role_id,
                'status'              => 'active',
                'primary_facility_id' => $this->otherFacility->id,
            ])
            ->assertRedirect();

        $this->assertSame($this->otherFacility->id, $staff->fresh()->primary_facility_id);
    }

    public function test_platform_admin_can_clear_a_primary_facility(): void
    {
        $staff = $this->userWithRole('doctor', $this->ownFacility->id);

        $this->actingAs($this->platformAdmin())
            ->put('/admin/users/' . $staff->id, [
                'name'                => $staff->name,
                'email'               => $staff->email,
                'role_id'             => $staff->role_id,
                'status'              => 'active',
                'primary_facility_id' => '',
            ]);

        $this->assertNull($staff->fresh()->primary_facility_id);
    }

    // ── Facility tier: may only ever name its OWN facility ───────────────

    public function test_a_facility_admin_cannot_create_a_user_in_a_facility_it_does_not_administer(): void
    {
        $admin = $this->facilityAdmin($this->ownFacility);

        $this->actingAsFacilityAdminAtControllerLevel($admin)
            ->post('/admin/users', [
                'name'                => 'Planted Account',
                'email'               => 'planted@example.test',
                'password'            => 'correct-horse-8',
                'role_id'             => $this->doctorRoleId(),
                'primary_facility_id' => $this->otherFacility->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'planted@example.test']);
    }

    public function test_a_facility_admin_cannot_move_an_existing_user_into_another_facility(): void
    {
        $admin = $this->facilityAdmin($this->ownFacility);
        $staff = $this->userWithRole('doctor', $this->ownFacility->id);

        $this->actingAsFacilityAdminAtControllerLevel($admin)
            ->put('/admin/users/' . $staff->id, [
                'name'                => $staff->name,
                'email'               => $staff->email,
                'role_id'             => $staff->role_id,
                'status'              => 'active',
                'primary_facility_id' => $this->otherFacility->id,
            ])
            ->assertForbidden();

        $this->assertSame(
            $this->ownFacility->id,
            $staff->fresh()->primary_facility_id,
            'the user must not have been moved'
        );
    }

    public function test_a_facility_admin_can_staff_its_own_facility(): void
    {
        $admin = $this->facilityAdmin($this->ownFacility);

        $this->actingAsFacilityAdminAtControllerLevel($admin)
            ->post('/admin/users', [
                'name'                => 'Dr Ada Nkemcha',
                'email'               => 'ada.own@example.test',
                'password'            => 'correct-horse-8',
                'role_id'             => $this->doctorRoleId(),
                'primary_facility_id' => $this->ownFacility->id,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(
            $this->ownFacility->id,
            User::where('email', 'ada.own@example.test')->first()?->primary_facility_id
        );
    }

    public function test_a_facility_admin_with_no_facility_context_cannot_assign_at_all(): void
    {
        $admin = $this->userWithRole('facility_admin');   // no primary facility

        $this->actingAsFacilityAdminAtControllerLevel($admin)
            ->post('/admin/users', [
                'name'                => 'Contextless',
                'email'               => 'contextless@example.test',
                'password'            => 'correct-horse-8',
                'role_id'             => $this->doctorRoleId(),
                'primary_facility_id' => $this->ownFacility->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'contextless@example.test']);
    }

    /** The route-level gate must stay in place too — belt as well as braces. */
    public function test_the_route_middleware_still_blocks_facility_admins_from_the_godmode_screen(): void
    {
        $this->actingAs($this->facilityAdmin($this->ownFacility))
            ->get('/admin/users')
            ->assertForbidden();
    }

    // ── The picker itself ────────────────────────────────────────────────

    public function test_the_edit_screen_renders_and_offers_a_facility_control(): void
    {
        $staff = $this->userWithRole('doctor', $this->ownFacility->id);

        $this->actingAs($this->platformAdmin())
            ->get('/admin/users/' . $staff->id)
            ->assertOk()
            ->assertSee('name="primary_facility_id"', false);
    }

    public function test_the_facility_picker_is_bounded_not_a_dump_of_the_whole_register(): void
    {
        Facility::factory()->count(60)->create();

        $html = $this->actingAs($this->platformAdmin())
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('name="primary_facility_id"', false)
            ->getContent();

        // Count only the picker's own options, not every <option> on the page.
        $picker = $this->extractSelect($html, 'primary_facility_id');

        $this->assertNotSame('', $picker, 'the create form must offer a facility picker');
        $this->assertLessThan(
            Facility::count(),
            substr_count($picker, '<option'),
            'the picker must not render the entire facility register'
        );
    }

    public function test_the_facility_picker_is_search_driven(): void
    {
        $html = $this->actingAs($this->platformAdmin())
            ->get('/admin/users?facility_q=Douala')
            ->assertOk()
            ->getContent();

        $picker = $this->extractSelect($html, 'primary_facility_id');

        $this->assertStringContainsString('Douala General Hospital', $picker);
        $this->assertStringNotContainsString('Bamenda Regional Hospital', $picker);
    }

    public function test_a_facility_admin_is_only_offered_its_own_facility(): void
    {
        $admin = $this->facilityAdmin($this->ownFacility);

        $html = $this->actingAsFacilityAdminAtControllerLevel($admin)
            ->get('/admin/users')
            ->assertOk()
            ->getContent();

        $picker = $this->extractSelect($html, 'primary_facility_id');

        $this->assertStringContainsString('Bamenda Regional Hospital', $picker);
        $this->assertStringNotContainsString('Douala General Hospital', $picker);
    }

    // ── Audit ────────────────────────────────────────────────────────────

    public function test_assigning_a_facility_is_written_to_the_audit_trail(): void
    {
        $admin = $this->platformAdmin();
        $staff = $this->userWithRole('doctor', $this->ownFacility->id);

        $this->actingAs($admin)->put('/admin/users/' . $staff->id, [
            'name'                => $staff->name,
            'email'               => $staff->email,
            'role_id'             => $staff->role_id,
            'status'              => 'active',
            'primary_facility_id' => $this->otherFacility->id,
        ]);

        $event = AuditEvent::where('action_type', 'admin_user_facility_assigned')
            ->where('resource_id', $staff->id)
            ->first();

        $this->assertNotNull($event, 'changing a user\'s facility must be audited');
        $this->assertSame($admin->id, $event->actor_id);
        $this->assertSame($this->otherFacility->id, $event->facility_id);
    }

    public function test_an_edit_that_does_not_touch_the_facility_writes_no_assignment_event(): void
    {
        $staff = $this->userWithRole('doctor', $this->ownFacility->id);

        $this->actingAs($this->platformAdmin())->put('/admin/users/' . $staff->id, [
            'name'                => 'Renamed Only',
            'email'               => $staff->email,
            'role_id'             => $staff->role_id,
            'status'              => 'active',
            'primary_facility_id' => $this->ownFacility->id,
        ]);

        $this->assertSame(0, AuditEvent::where('action_type', 'admin_user_facility_assigned')->count());
    }

    // ── Validation ───────────────────────────────────────────────────────

    public function test_an_unknown_facility_id_is_rejected_by_validation(): void
    {
        $this->actingAs($this->platformAdmin())
            ->post('/admin/users', [
                'name'                => 'Bad Facility',
                'email'               => 'badfacility@example.test',
                'password'            => 'correct-horse-8',
                'role_id'             => $this->doctorRoleId(),
                'primary_facility_id' => '00000000-0000-4000-8000-000000000000',
            ])
            ->assertSessionHasErrors('primary_facility_id');

        $this->assertDatabaseMissing('users', ['email' => 'badfacility@example.test']);
    }

    /** A non-UUID must fail validation, not blow up Postgres with a 22P02. */
    public function test_a_non_uuid_facility_id_is_rejected_without_a_database_error(): void
    {
        $this->actingAs($this->platformAdmin())
            ->post('/admin/users', [
                'name'                => 'Not A Uuid',
                'email'               => 'notauuid@example.test',
                'password'            => 'correct-horse-8',
                'role_id'             => $this->doctorRoleId(),
                'primary_facility_id' => 'not-a-uuid',
            ])
            ->assertSessionHasErrors('primary_facility_id');
    }

    /** Pull one named <select> out of the rendered page. */
    private function extractSelect(string $html, string $name): string
    {
        if (! preg_match('/<select[^>]*name="' . preg_quote($name, '/') . '"[^>]*>(.*?)<\/select>/s', $html, $m)) {
            return '';
        }

        return $m[0];
    }
}
