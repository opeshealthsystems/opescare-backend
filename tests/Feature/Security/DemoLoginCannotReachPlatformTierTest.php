<?php

namespace Tests\Feature\Security;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security scan 2026-09-15, Vuln 1. The public one-click demo login handed out
 * a platform_admin session with no password or MFA, and admin screens that
 * read across the demo boundary let it act on real facilities. Demo accounts
 * must never be platform tier — at login, at the door, and in the nav.
 */
class DemoLoginCannotReachPlatformTierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
        config(['demo.enabled' => true, 'demo.allowed_ips' => '']);
    }

    private function user(string $roleName, bool $isDemo, ?Facility $facility = null): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility?->id,
            'status'              => 'active',
        ]);
        // IsDemoRecord stamps is_demo from config on create; set it explicitly.
        $user->forceFill(['is_demo' => $isDemo])->save();

        if ($facility) {
            FacilityRoleAssignment::create([
                'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
                'is_active' => true, 'assigned_at' => now(),
            ]);
        }

        return $user->fresh();
    }

    public function test_demo_login_refuses_a_platform_role_whatever_the_form_says(): void
    {
        $admin = $this->user('platform_admin', true);

        $this->post(route('demo.login-as'), ['email' => $admin->email, 'role' => 'doctor'])
            ->assertForbidden();

        $this->assertGuest();
    }

    public function test_demo_login_still_works_for_a_facility_role(): void
    {
        $facility = Facility::factory()->create(['type' => 'clinic']);
        $clinicAdmin = $this->user('clinic_admin', true, $facility);

        $this->post(route('demo.login-as'), ['email' => $clinicAdmin->email, 'role' => 'clinic_admin'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($clinicAdmin);
    }

    public function test_an_open_demo_platform_session_is_refused_at_platform_pages_and_is_not_platform_tier(): void
    {
        $demoAdmin = $this->user('platform_admin', true);

        $this->assertFalse(\App\Http\Middleware\RequirePlatformAdmin::isPlatformTier($demoAdmin));

        $this->actingAs($demoAdmin)
            ->withSession(['mfa.verified' => true])
            ->get('/admin/onboarding')
            ->assertForbidden();
    }

    public function test_a_real_platform_admin_is_unaffected(): void
    {
        $realAdmin = $this->user('platform_admin', false);

        $this->assertTrue(\App\Http\Middleware\RequirePlatformAdmin::isPlatformTier($realAdmin));

        $this->actingAs($realAdmin)
            ->withSession(['mfa.verified' => true])
            ->get('/admin/onboarding')
            ->assertOk();
    }
}
