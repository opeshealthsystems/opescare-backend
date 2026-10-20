<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use App\Modules\Auth\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Readiness OB-3 (2026-10-20): facility managers hold a whole facility's
 * staff, stock, prices and money, yet production's MFA_REQUIRED_ROLES lists
 * only super_admin,admin. They are now in User::ALWAYS_MFA_ROLES, so the env
 * value cannot leave them out, and they enrol after the password on first use.
 */
class FacilityManagersNeedMfaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $enforceMfaInActingAs = true;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mfa.required_roles' => ['super_admin', 'admin']]); // the production value
    }

    private function user(string $role, ?string $email = null): User
    {
        $r = Role::query()->firstOrCreate(['name' => $role], ['display_name' => $role]);
        $u = User::factory()->create([
            'email' => $email ?? $role.'@example.org',
            'password' => 'Correct-horse-9!',
            'status' => 'active',
        ]);
        $u->forceFill(['role_id' => $r->id, 'is_demo' => false])->save();

        return $u->fresh();
    }

    public function test_every_facility_manager_role_requires_mfa_under_the_production_env_list(): void
    {
        foreach (['facility_admin', 'pharmacy_manager', 'lab_manager', 'multi_facility_admin',
                  'facility_executive', 'finance_manager', 'hospital_admin', 'clinic_admin'] as $role) {
            $this->assertContains($role, User::ALWAYS_MFA_ROLES);
            $this->assertTrue($this->user($role)->requiresTwoFactor(), "$role must require MFA");
        }

        $this->assertFalse($this->user('pharmacist')->requiresTwoFactor(), 'front-line staff stay on the env list');
    }

    public function test_a_pharmacy_manager_enrols_mfa_after_the_password_on_first_sign_in(): void
    {
        $user = $this->user('pharmacy_manager');
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'Correct-horse-9!'])
            ->assertRedirect(route('mfa.challenge'));
        $this->assertGuest();

        $this->get(route('mfa.challenge'))->assertOk()->assertSee(route('mfa.setup.submit'), false);
        $secret = session('mfa.pending_secret');
        $this->assertNotEmpty($secret);

        $this->post(route('mfa.setup.submit'), ['code' => app(TwoFactorService::class)->codeAt($secret)])
            ->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function test_a_facility_admin_session_without_mfa_is_sent_to_the_challenge(): void
    {
        $admin = $this->user('facility_admin');

        $this->actingAs($admin)->get('/facility/billing')->assertRedirect(route('mfa.challenge'));
    }
}
