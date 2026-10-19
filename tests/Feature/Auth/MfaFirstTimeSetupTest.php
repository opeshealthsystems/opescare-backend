<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Modules\Auth\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An account whose role requires MFA but has never enrolled used to dead-end
 * on "contact an administrator" — which locked out the very first admin. It
 * now enrols on the challenge page after the password is proven.
 */
class MfaFirstTimeSetupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'platform_admin'], ['display_name' => 'Platform admin']);
        $user = User::factory()->create(['email' => 'first.admin@example.org', 'password' => 'Correct-horse-9!', 'status' => 'active']);
        $user->role_id = $role->id;
        $user->is_demo = false;
        $user->save();

        return $user->fresh();
    }

    public function test_first_admin_enrols_mfa_after_the_password_and_is_signed_in(): void
    {
        $user = $this->admin();
        $this->assertTrue($user->requiresTwoFactor());
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'Correct-horse-9!'])
            ->assertRedirect(route('mfa.challenge'));
        $this->assertGuest();

        $page = $this->get(route('mfa.challenge'))->assertOk();
        $page->assertSee('data:image/svg+xml', false);
        $page->assertSee(route('mfa.setup.submit'), false);

        $secret = session('mfa.pending_secret');
        $this->assertNotEmpty($secret);

        // A wrong code is refused and enrols nothing.
        $this->post(route('mfa.setup.submit'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $code = app(TwoFactorService::class)->codeAt($secret);
        $this->post(route('mfa.setup.submit'), ['code' => $code])
            ->assertOk()
            ->assertSee(__('finder_auth.mfa.recovery_title'));

        $this->assertAuthenticatedAs($user);
        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasTwoFactorEnabled());
        $this->assertCount((int) config('mfa.recovery_code_count', 8), $fresh->two_factor_recovery_codes);
        $this->assertNull(session('mfa.pending_secret'));
    }

    public function test_setup_cannot_be_reached_without_the_password_step(): void
    {
        $this->post(route('mfa.setup.submit'), ['code' => '123456'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_enrolled_admin_gets_the_normal_code_challenge(): void
    {
        $user = $this->admin();
        $tf = app(TwoFactorService::class);
        $user->two_factor_secret = $tf->generateSecret();
        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'Correct-horse-9!'])
            ->assertRedirect(route('mfa.challenge'));
        $this->get(route('mfa.challenge'))->assertOk()->assertDontSee(route('mfa.setup.submit'), false);
    }
}
