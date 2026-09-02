<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use App\Modules\Auth\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebMfaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_required_role_is_redirected_to_mfa_challenge_after_password_login(): void
    {
        $user = $this->mfaUser('admin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('mfa.challenge'));
        $response->assertSessionHas('mfa.user_id', $user->id);
        $this->assertGuest();
    }

    public function test_mfa_challenge_rejects_invalid_code(): void
    {
        $user = $this->mfaUser('admin');

        $response = $this
            ->withSession(['mfa.user_id' => $user->id])
            ->post('/mfa/challenge', ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_mfa_challenge_accepts_current_totp_code(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $user = $this->mfaUser('admin', $secret);
        $code = app(TwoFactorService::class)->codeAt($secret);

        $response = $this
            ->withSession(['mfa.user_id' => $user->id])
            ->post('/mfa/challenge', ['code' => $code]);

        $response->assertRedirect('/portals/patient');
        $response->assertSessionMissing('mfa.user_id');
        $this->assertAuthenticatedAs($user);
    }

    public function test_required_role_without_enrollment_is_blocked_for_mfa_setup(): void
    {
        config()->set('mfa.required_roles', ['admin']);

        $role = Role::create(['name' => 'admin', 'description' => 'Admin']);
        $user = User::factory()->create([
            'email' => 'admin-no-mfa@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $user->role_id = $role->id;
        $user->save();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('mfa.challenge'));
        $response->assertSessionHas('mfa.user_id', $user->id);
        $response->assertSessionHas('mfa.setup_required', true);
        $this->assertGuest();
    }

    private function mfaUser(string $roleName, ?string $secret = null): User
    {
        config()->set('mfa.required_roles', [$roleName]);

        $role = Role::create(['name' => $roleName, 'description' => ucfirst($roleName)]);
        $user = User::factory()->create([
            'email' => $roleName . '-mfa@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $user->role_id = $role->id;
        $user->forceFill([
            'two_factor_secret' => $secret ?? app(TwoFactorService::class)->generateSecret(),
            'two_factor_confirmed_at' => now(),
        ]);
        $user->save();

        return $user;
    }
}
