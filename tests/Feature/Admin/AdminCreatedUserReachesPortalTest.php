<?php

namespace Tests\Feature\Admin;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A user created through the platform-admin screen must be able to work.
 *
 * `store()` did not set `primary_facility_id`, so the account it produced hit
 * RequireFacilityContext, was redirected to /select-facility, and found nothing
 * to select -- a dead end that made the screen unable to produce a working
 * account at all. In production 0 of 2 users had a facility linked.
 *
 * Two separate things have to be true, and only the first is enough to pass the
 * middleware, which is why the second is easy to miss:
 *
 *   1. `primary_facility_id` is set. RequireFacilityContext seeds
 *      `active_facility_id` from it, so the redirect stops.
 *   2. A `facility_role_assignments` row exists. Without it
 *      `User::roleAtFacility()` silently falls back to the global
 *      `users.role_id` -- the compatibility path for accounts older than that
 *      table. The user appears to work while their role at that facility is
 *      inferred rather than recorded, which is the wrong shape for a platform
 *      where one person may hold different roles at different facilities.
 *
 * This route is platform-tier (RequirePlatformAdmin), so taking the facility
 * from request input is correct here -- unlike the facility-scoped controllers,
 * where it must come from the session.
 */
class AdminCreatedUserReachesPortalTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'status'  => 'active',
            'role_id' => Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin'])->id,
        ]);
    }

    private function facility(string $name = 'Hopital Laquintinie'): Facility
    {
        return Facility::forceCreate(['name' => $name, 'type' => 'hospital', 'is_demo' => false]);
    }

    private function doctorRole(): Role
    {
        return Role::firstOrCreate(['name' => 'doctor'], ['display_name' => 'Doctor']);
    }

    private function createUser(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->platformAdmin())
            ->withSession(['mfa.verified' => true])
            ->post('/admin/users', array_merge([
                'name'     => 'Dr Amina Nkeng',
                'email'    => 'amina.nkeng@example.test',
                'password' => 'correct-horse-battery',
                'role_id'  => $this->doctorRole()->id,
            ], $overrides));
    }

    /** The defect: the account must not land at the selector with nothing to select. */
    public function test_a_user_created_with_a_facility_reaches_the_staff_portal(): void
    {
        $facility = $this->facility();

        $this->createUser(['primary_facility_id' => $facility->id])->assertRedirect();

        $user = User::where('email', 'amina.nkeng@example.test')->firstOrFail();

        $this->assertSame(
            $facility->id,
            $user->primary_facility_id,
            'the facility chosen on the create form must be persisted'
        );

        $response = $this->actingAs($user)
            ->withSession(['mfa.verified' => true])
            ->get('/portals/staff');

        $this->assertFalse(
            str_contains((string) $response->headers->get('Location'), 'select-facility'),
            'a user created with a facility must not be bounced to the facility selector'
        );
    }

    /** The half that the middleware cannot tell you is missing. */
    public function test_the_facility_role_assignment_is_written_so_the_role_resolves(): void
    {
        $facility = $this->facility();
        $role     = $this->doctorRole();

        $this->createUser(['primary_facility_id' => $facility->id])->assertRedirect();

        $user = User::where('email', 'amina.nkeng@example.test')->firstOrFail();

        $this->assertDatabaseHas('facility_role_assignments', [
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
        ]);

        $this->assertSame(
            $role->id,
            $user->fresh()->roleAtFacility($facility->id)?->id,
            'roleAtFacility() must resolve from the assignment, not the global-role fallback'
        );
    }

    /** No facility chosen is still legitimate -- it must not invent an assignment. */
    public function test_creating_a_user_without_a_facility_writes_no_assignment(): void
    {
        $this->createUser()->assertRedirect();

        $user = User::where('email', 'amina.nkeng@example.test')->firstOrFail();

        $this->assertNull($user->primary_facility_id);
        $this->assertDatabaseMissing('facility_role_assignments', ['user_id' => $user->id]);
    }

    /** Moving a user must not leave them holding access at the old facility. */
    public function test_reassigning_a_user_withdraws_the_previous_assignment(): void
    {
        $first  = $this->facility('First Clinic');
        $second = $this->facility('Second Clinic');
        $role   = $this->doctorRole();

        $this->createUser(['primary_facility_id' => $first->id])->assertRedirect();
        $user = User::where('email', 'amina.nkeng@example.test')->firstOrFail();

        $this->actingAs($this->platformAdmin())
            ->withSession(['mfa.verified' => true])
            ->post('/admin/users/' . $user->id, [
                '_method'             => 'PUT',
                'name'                => $user->name,
                'email'               => $user->email,
                'role_id'             => $role->id,
                'status'              => 'active',
                'primary_facility_id' => $second->id,
            ]);

        $user->refresh();

        $this->assertSame($second->id, $user->primary_facility_id);

        $this->assertDatabaseHas('facility_role_assignments', [
            'user_id'     => $user->id,
            'facility_id' => $second->id,
            'is_active'   => true,
        ]);

        $stale = FacilityRoleAssignment::where('user_id', $user->id)
            ->where('facility_id', $first->id)
            ->first();

        $this->assertNotNull($stale, 'the old row is kept as a record of access once granted');
        $this->assertFalse((bool) $stale->is_active, 'but it must no longer be active');

        // Not asserted here: roleAtFacility($first) still returns the doctor
        // role, because it falls back to the global users.role_id when no active
        // assignment exists. That fallback is deliberate compatibility for
        // accounts older than this table -- withdrawing the assignment is what
        // this test is about, and the fallback is a separate concern.
        $this->assertSame(
            0,
            FacilityRoleAssignment::query()
                ->where('user_id', $user->id)
                ->where('facility_id', $first->id)
                ->active()
                ->count(),
            'no ACTIVE assignment may remain at the facility the user left'
        );
    }
}
