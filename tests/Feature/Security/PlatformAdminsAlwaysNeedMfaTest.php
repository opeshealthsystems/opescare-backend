<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security scan 2026-09-15, item 3. Production's MFA_REQUIRED_ROLES listed only
 * super_admin,admin, so platform_admin ran with no second factor, and the
 * imported /admin routes (routes/imports_ops.php) did not ask for MFA at all.
 */
class PlatformAdminsAlwaysNeedMfaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
        // The production env value that left platform_admin out.
        config(['mfa.required_roles' => ['super_admin', 'admin']]);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['role_id' => Role::where('name', $role)->firstOrFail()->id, 'status' => 'active']);
        $u->forceFill(['is_demo' => false])->save();

        return $u->fresh();
    }

    public function test_god_mode_roles_require_mfa_even_when_the_env_list_omits_them(): void
    {
        foreach (User::ALWAYS_MFA_ROLES as $role) {
            if (Role::where('name', $role)->exists()) {
                $this->assertTrue($this->user($role)->requiresTwoFactor(), "$role must require MFA");
            }
        }

        $this->assertFalse($this->user('doctor')->requiresTwoFactor(), 'the env list still governs other roles');
    }

    public function test_an_admin_session_without_mfa_is_sent_to_the_challenge_on_imported_admin_routes(): void
    {
        $admin = $this->user('platform_admin');

        foreach (['/admin/onboarding', '/admin/security/access-review', '/admin/security/compliance-export'] as $path) {
            $this->actingAs($admin)->get($path)->assertRedirect(route('mfa.challenge'));
        }

        $this->actingAs($admin)->withSession(['mfa.verified' => true])->get('/admin/onboarding')->assertOk();
    }
}
