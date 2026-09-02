<?php

namespace Tests\Feature\Staff;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\FacilityStaffInvite;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Facility-scoped staff invitations.
 *
 * The privilege boundary is the point of this suite: a facility administrator
 * must be able to staff their own facility and must not be able to do anything
 * else with that power — no platform roles, no other facilities, no invite link
 * that outlives its single use.
 */
class FacilityStaffInviteTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name], ['description' => ucfirst($name)]);
    }

    private function facilityAdmin(Facility $facility): User
    {
        $user = User::factory()->create(['primary_facility_id' => $facility->id]);
        $user->role_id = $this->role('facility_admin')->id;
        $user->save();

        return $user->fresh();
    }

    private function issueInvite(User $admin, string $roleName = 'doctor', string $email = 'new.doctor@example.test'): string
    {
        $response = $this->actingAs($admin)->post(route('portals.facility.team.invite'), [
            'email' => $email,
            'name'  => 'Dr Ada Nkemcha',
            'role'  => $roleName,
        ]);

        $response->assertRedirect(route('portals.facility.team'));
        $response->assertSessionHas('invite_link');

        $link = $response->getSession()->get('invite_link');

        // The raw token is only ever in the link; the row keeps a sha256 of it.
        return (string) str($link)->afterLast('/');
    }

    // ── Happy path ───────────────────────────────────────────────────────

    public function test_an_invite_creates_an_account_linked_to_the_inviting_facility(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('doctor');

        $token = $this->issueInvite($admin);

        $this->post(route('invite.accept.submit', $token), [
            'name'             => 'Ada Nkemcha',
            'phone'            => '+237600000000',
            'password'         => 'correct-horse-8',
            'confirm_password' => 'correct-horse-8',
            'accept_terms'     => '1',
        ])->assertRedirect(route('login'));

        $created = User::where('email', 'new.doctor@example.test')->first();

        $this->assertNotNull($created, 'the invite must create a real user');
        $this->assertSame($facility->id, $created->primary_facility_id, 'the account must be linked to the INVITING facility');
        $this->assertSame('doctor', $created->role?->name);
        $this->assertSame('active', $created->status);

        // The per-facility RBAC row is written too, so roleAtFacility() resolves
        // without falling back to the global role.
        $this->assertDatabaseHas('facility_role_assignments', [
            'user_id'     => $created->id,
            'facility_id' => $facility->id,
            'role_id'     => $created->role_id,
            'is_active'   => true,
        ]);
        $this->assertTrue(FacilityRoleAssignment::where('user_id', $created->id)->exists());
    }

    public function test_the_created_account_can_log_in(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('doctor');

        $token = $this->issueInvite($admin);

        $this->post(route('invite.accept.submit', $token), [
            'name'             => 'Ada Nkemcha',
            'password'         => 'correct-horse-8',
            'confirm_password' => 'correct-horse-8',
            'accept_terms'     => '1',
        ]);

        $created = User::where('email', 'new.doctor@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('correct-horse-8', $created->password));

        $this->assertTrue(
            auth()->attempt(['email' => 'new.doctor@example.test', 'password' => 'correct-horse-8']),
            'the invited user must be able to authenticate with the password they chose'
        );
    }

    public function test_the_new_account_has_facility_context_and_is_not_bounced_to_the_selector(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('doctor');

        $token = $this->issueInvite($admin);

        $this->post(route('invite.accept.submit', $token), [
            'name'             => 'Ada Nkemcha',
            'password'         => 'correct-horse-8',
            'confirm_password' => 'correct-horse-8',
            'accept_terms'     => '1',
        ]);

        $created = User::where('email', 'new.doctor@example.test')->firstOrFail();

        // The original defect: a created doctor with no primary_facility_id met
        // RequireFacilityContext and was redirected to /select-facility with
        // nothing to select.
        $response = $this->actingAs($created)->get(route('portals.staff'));

        $this->assertFalse(
            $response->isRedirect(route('select-facility')),
            'the invited account must NOT be bounced to the empty facility selector'
        );

        $response->assertSessionHas('active_facility_id', $facility->id);
    }

    // ── Single use and expiry ────────────────────────────────────────────

    public function test_a_reused_invite_is_refused(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('doctor');

        $token = $this->issueInvite($admin);

        $payload = [
            'name'             => 'Ada Nkemcha',
            'password'         => 'correct-horse-8',
            'confirm_password' => 'correct-horse-8',
            'accept_terms'     => '1',
        ];

        $this->post(route('invite.accept.submit', $token), $payload)->assertRedirect(route('login'));

        // Second redemption of the same link.
        $second = $this->post(route('invite.accept.submit', $token), array_merge($payload, [
            'name' => 'Someone Else',
        ]));

        $second->assertOk();
        $second->assertSee(__('onboarding.invite.errors.used'));

        $this->assertSame(1, User::where('email', 'new.doctor@example.test')->count());
    }

    public function test_an_expired_invite_is_refused(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('doctor');

        $token = $this->issueInvite($admin);

        FacilityStaffInvite::query()->update(['expires_at' => now()->subMinute()]);

        $this->get(route('invite.accept', $token))
            ->assertOk()
            ->assertSee(__('onboarding.invite.errors.expired'));

        $this->post(route('invite.accept.submit', $token), [
            'name'             => 'Ada Nkemcha',
            'password'         => 'correct-horse-8',
            'confirm_password' => 'correct-horse-8',
            'accept_terms'     => '1',
        ])->assertOk()->assertSee(__('onboarding.invite.errors.expired'));

        $this->assertDatabaseMissing('users', ['email' => 'new.doctor@example.test']);
    }

    public function test_a_revoked_invite_is_refused(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('doctor');

        $token  = $this->issueInvite($admin);
        $invite = FacilityStaffInvite::firstOrFail();

        $this->actingAs($admin)
            ->post(route('portals.facility.team.invite.revoke', $invite->id))
            ->assertRedirect(route('portals.facility.team'));

        $this->post(route('invite.accept.submit', $token), [
            'name'             => 'Ada Nkemcha',
            'password'         => 'correct-horse-8',
            'confirm_password' => 'correct-horse-8',
            'accept_terms'     => '1',
        ])->assertOk()->assertSee(__('onboarding.invite.errors.revoked'));

        $this->assertDatabaseMissing('users', ['email' => 'new.doctor@example.test']);
    }

    public function test_the_raw_token_is_never_stored(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('doctor');

        $token  = $this->issueInvite($admin);
        $invite = FacilityStaffInvite::firstOrFail();

        $this->assertNotSame($token, $invite->token_hash);
        $this->assertSame(hash('sha256', $token), $invite->token_hash);
        $this->assertArrayNotHasKey('token_hash', $invite->toArray());
    }

    public function test_reissuing_invalidates_the_previous_link(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('doctor');

        $oldToken = $this->issueInvite($admin);
        $invite   = FacilityStaffInvite::firstOrFail();

        $response = $this->actingAs($admin)->post(route('portals.facility.team.invite.reissue', $invite->id));
        $response->assertRedirect(route('portals.facility.team'));
        $response->assertSessionHas('invite_link');

        $newToken = (string) str($response->getSession()->get('invite_link'))->afterLast('/');

        $this->assertNotSame($oldToken, $newToken);
        $this->assertNull(FacilityStaffInvite::findByToken($oldToken));
        $this->assertNotNull(FacilityStaffInvite::findByToken($newToken));
    }

    // ── Privilege boundary ───────────────────────────────────────────────

    public function test_a_facility_admin_cannot_assign_a_platform_admin_role(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);
        $this->role('super_admin');
        $this->role('platform_admin');

        foreach (['super_admin', 'platform_admin', 'facility_admin', 'compliance_officer'] as $forbidden) {
            $this->role($forbidden);

            $this->actingAs($admin)
                ->post(route('portals.facility.team.invite'), [
                    'email' => 'escalation@example.test',
                    'role'  => $forbidden,
                ])
                ->assertSessionHasErrors('role');
        }

        $this->assertSame(0, FacilityStaffInvite::count());
        $this->assertNotContains('super_admin', FacilityStaffInvite::INVITABLE_ROLES);
        $this->assertNotContains('platform_admin', FacilityStaffInvite::INVITABLE_ROLES);
    }

    public function test_a_facility_admin_cannot_invite_into_another_facility(): void
    {
        $mine     = Facility::factory()->create();
        $theirs   = Facility::factory()->create();
        $admin    = $this->facilityAdmin($mine);
        $this->role('doctor');

        // The form has no facility field; smuggling one in must change nothing.
        $this->actingAs($admin)->post(route('portals.facility.team.invite'), [
            'email'       => 'new.doctor@example.test',
            'role'        => 'doctor',
            'facility_id' => $theirs->id,
        ])->assertRedirect(route('portals.facility.team'));

        $invite = FacilityStaffInvite::firstOrFail();

        $this->assertSame($mine->id, $invite->facility_id, 'the facility must come from the session context, not the request');
        $this->assertNotSame($theirs->id, $invite->facility_id);
    }

    public function test_a_facility_admin_cannot_revoke_another_facilitys_invite(): void
    {
        $mine   = Facility::factory()->create();
        $theirs = Facility::factory()->create();
        $admin  = $this->facilityAdmin($mine);

        $foreign = FacilityStaffInvite::create([
            'facility_id' => $theirs->id,
            'role_id'     => $this->role('doctor')->id,
            'email'       => 'elsewhere@example.test',
            'token_hash'  => FacilityStaffInvite::hashToken('some-other-token'),
            'expires_at'  => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('portals.facility.team.invite.revoke', $foreign->id))
            ->assertNotFound();

        $this->assertNull($foreign->fresh()->revoked_at);
    }

    public function test_the_team_page_only_lists_this_facilitys_staff_and_invites(): void
    {
        $mine   = Facility::factory()->create(['name' => 'Douala General']);
        $theirs = Facility::factory()->create(['name' => 'Yaounde Central']);
        $admin  = $this->facilityAdmin($mine);
        $this->role('doctor');

        $ours = User::factory()->create(['primary_facility_id' => $mine->id, 'name' => 'Our Own Clinician']);
        $ours->role_id = $this->role('doctor')->id;
        $ours->save();

        User::factory()->create(['primary_facility_id' => $theirs->id, 'name' => 'Foreign Clinician']);

        FacilityStaffInvite::create([
            'facility_id' => $theirs->id,
            'role_id'     => $this->role('doctor')->id,
            'email'       => 'foreign.invite@example.test',
            'token_hash'  => FacilityStaffInvite::hashToken('foreign'),
            'expires_at'  => now()->addDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('portals.facility.team'));

        $response->assertOk();
        $response->assertSee('Our Own Clinician');
        $response->assertDontSee('Foreign Clinician');
        $response->assertDontSee('foreign.invite@example.test');
    }

    public function test_a_clinician_cannot_reach_the_team_page(): void
    {
        $facility = Facility::factory()->create();

        $doctor = User::factory()->create(['primary_facility_id' => $facility->id]);
        $doctor->role_id = $this->role('doctor')->id;
        $doctor->save();

        // EnsurePortalAccess routes a non-facility-admin role away from the
        // portals/facility prefix rather than serving the page.
        $this->actingAs($doctor->fresh())
            ->get(route('portals.facility.team'))
            ->assertRedirect('/portals/staff');
    }

    public function test_an_unknown_token_is_indistinguishable_from_an_expired_one(): void
    {
        $this->get(route('invite.accept', 'not-a-real-token'))
            ->assertOk()
            ->assertSee(__('onboarding.invite.errors.expired'));
    }

    // ── The session context the invite boundary rests on ─────────────────

    /**
     * "Resolve the facility from the session" is only a boundary if the session
     * cannot be set to an arbitrary facility. The selector used to write
     * whatever facility id the browser posted straight into the session.
     */
    public function test_the_facility_selector_refuses_a_facility_the_user_is_not_assigned_to(): void
    {
        $mine   = Facility::factory()->create();
        $theirs = Facility::factory()->create();
        $admin  = $this->facilityAdmin($mine);

        $this->actingAs($admin)
            ->post(route('select-facility.submit'), ['facility' => $theirs->id])
            ->assertRedirect(route('select-facility'))
            ->assertSessionHas('error');

        $this->assertNotSame($theirs->id, session('active_facility_id'));
    }

    public function test_switching_facility_cannot_be_used_to_invite_elsewhere(): void
    {
        $mine   = Facility::factory()->create();
        $theirs = Facility::factory()->create();
        $admin  = $this->facilityAdmin($mine);
        $this->role('doctor');

        $this->actingAs($admin)->post(route('select-facility.submit'), ['facility' => $theirs->id]);

        $this->actingAs($admin)->post(route('portals.facility.team.invite'), [
            'email' => 'new.doctor@example.test',
            'role'  => 'doctor',
        ]);

        $invite = FacilityStaffInvite::first();

        if ($invite !== null) {
            $this->assertSame($mine->id, $invite->facility_id, 'a forged facility switch must not redirect the invite');
        }
    }

    public function test_a_user_can_select_a_facility_they_are_assigned_to(): void
    {
        $facility = Facility::factory()->create();
        $admin    = $this->facilityAdmin($facility);

        $this->actingAs($admin)
            ->post(route('select-facility.submit'), ['facility' => $facility->id])
            ->assertSessionMissing('error');

        $this->assertSame($facility->id, session('active_facility_id'));
    }
}
