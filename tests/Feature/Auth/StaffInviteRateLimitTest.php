<?php

namespace Tests\Feature\Auth;

use App\Models\Facility;
use App\Models\FacilityStaffInvite;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Brute-force limits on the staff invitation link.
 *
 * /invite/{token} had no limit on either verb. The POST creates a real staff
 * account at a real facility — the facility and the role come from the invite
 * row, so a successful guess is a working account inside somebody's hospital —
 * and the GET is just as good an oracle, because it renders the invite's
 * facility, role and inviter for a live token and an "expired" card for a dead
 * one. An unthrottled GET lets a caller find the live token first and spend the
 * POST only once.
 *
 * The counters are keyed by IP, because an enumerating caller supplies a
 * different token on every request; a per-token counter would never fill.
 */
class StaffInviteRateLimitTest extends TestCase
{
    use RefreshDatabase;

    /** A distinct, syntactically plausible token per attempt — this is the attack. */
    private function guess(int $i): string
    {
        return str_pad((string) $i, 64, 'a', STR_PAD_LEFT);
    }

    public function test_walking_invite_tokens_over_the_get_is_capped(): void
    {
        $statuses = [];
        for ($i = 0; $i < 14; $i++) {
            $statuses[] = $this->get('/invite/' . $this->guess($i))->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'the GET distinguishes a live invite token from a dead one, so an unlimited GET is a free enumeration oracle: '
                . implode(',', $statuses)
        );
    }

    public function test_walking_invite_tokens_over_the_post_is_capped(): void
    {
        $statuses = [];
        for ($i = 0; $i < 14; $i++) {
            $statuses[] = $this->post('/invite/' . $this->guess($i), [
                'name'             => 'Mallory',
                'password'         => 'correct-horse-8',
                'confirm_password' => 'correct-horse-8',
                'accept_terms'     => '1',
            ])->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'a successful guess on this endpoint mints a staff account at a facility: ' . implode(',', $statuses)
        );
    }

    /** Hammering one leaked link is capped too, not just the spread-out walk. */
    public function test_hammering_a_single_invite_token_is_capped(): void
    {
        $token = $this->guess(0);

        $statuses = [];
        for ($i = 0; $i < 8; $i++) {
            $statuses[] = $this->get('/invite/' . $token)->getStatusCode();
        }

        $this->assertContains(429, $statuses, 'one token, hammered: ' . implode(',', $statuses));
    }

    /**
     * The limit must not cost a real invitee their account. One person costs a
     * handful of requests — land on the page, submit the form — and that has to
     * keep working.
     */
    public function test_a_genuine_invitee_can_still_land_and_accept(): void
    {
        $facility = Facility::factory()->create();
        $role     = Role::firstOrCreate(['name' => 'doctor'], ['description' => 'Doctor']);
        $inviter  = User::factory()->create(['primary_facility_id' => $facility->id]);

        $token = FacilityStaffInvite::generateToken();

        FacilityStaffInvite::create([
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'email'       => 'real.invitee@example.test',
            'name'        => 'Ada Nkemcha',
            'token_hash'  => FacilityStaffInvite::hashToken($token),
            'invited_by'  => $inviter->id,
            'expires_at'  => now()->addDays(FacilityStaffInvite::TTL_DAYS),
        ]);

        $this->get('/invite/' . $token)->assertOk();

        $this->post('/invite/' . $token, [
            'name'             => 'Ada Nkemcha',
            'phone'            => '+237600000000',
            'password'         => 'correct-horse-8',
            'confirm_password' => 'correct-horse-8',
            'accept_terms'     => '1',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', ['email' => 'real.invitee@example.test']);
    }
}
