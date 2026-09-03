<?php

namespace Tests\Feature\Rbac;

use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RBAC: facility admins must NOT reach platform (god-mode) areas.
 *
 * Regression guard for the authorization bug where a clinic/hospital admin
 * could reach the platform Control Center and the cross-facility /admin/*
 * god-mode routes. RequirePlatformAdmin now restricts those to the platform tier.
 */
class PlatformAdminIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->facility = Facility::factory()->create();
    }

    private function userWithRole(string $roleName, bool $withFacility = true): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'status'              => 'active',
            'primary_facility_id' => $withFacility ? $this->facility->id : null,
        ]);
        $user->role_id = $role->id;   // role_id is guarded; assign explicitly
        $user->save();

        return $user->fresh();
    }

    public function test_facility_admin_blocked_from_godmode_user_management(): void
    {
        $this->actingAs($this->userWithRole('clinic_admin'))
            ->get('/admin/users')
            ->assertForbidden(); // 403
    }

    public function test_facility_admin_blocked_from_godmode_facilities(): void
    {
        $this->actingAs($this->userWithRole('hospital_admin'))
            ->get('/admin/facilities')
            ->assertForbidden();
    }

    public function test_non_admin_role_blocked_from_godmode(): void
    {
        $this->actingAs($this->userWithRole('doctor'))
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_platform_super_admin_allowed_godmode(): void
    {
        $res = $this->actingAs($this->userWithRole('super_admin', false))
            ->get('/admin/users');

        $this->assertNotSame(403, $res->getStatusCode(), 'super_admin must NOT be blocked from god-mode');
    }

    public function test_facility_admin_blocked_from_control_center(): void
    {
        $res = $this->actingAs($this->userWithRole('clinic_admin'))
            ->get('/admin/cc');

        // Must be blocked (403 from platform.admin, or redirected) — never 200.
        $this->assertNotSame(200, $res->getStatusCode(), 'facility admin must NOT reach the platform control center');
    }

    /**
     * Regression: clicking a platform-tier menu item must not silently show
     * the god-mode view.
     *
     * The platform's list (onboarding, kpi, go-live, security, legal,
     * subscription, reports/minsante-monthly) was deleted with those modules
     * in the Finder extraction; this now sweeps the platform-tier subpaths
     * that SURVIVE. A 404 would also be acceptable isolation, but every one
     * of these pages exists — so anything but 403 is a leak.
     */
    public function test_facility_admin_blocked_from_every_godmode_subpath(): void
    {
        $admin = $this->userWithRole('clinic_admin');
        // Full paths: the admin console moved to the clean /admin/* prefix, while
        // the marketing "leads" inbox keeps its /portals/admin/leads URL. Each is
        // asserted at its real current location so this stays a genuine 403 gate,
        // not an assertion satisfied by a 301 to a page that would itself 403.
        foreach ([
            '/admin/cc/audit',
            '/admin/cc/feature-flags',
            '/admin/cc/health',
            '/admin/cc/maintenance',
            '/admin/cc/modules',
            '/admin/cc/settings',
            '/admin/users',
            '/admin/roles',
            '/portals/admin/leads',
            '/admin/developer/accounts',
            '/admin/developer/production-requests',
        ] as $path) {
            $res = $this->actingAs($admin)->get($path);
            $this->assertSame(403, $res->getStatusCode(), "facility admin must get 403 on {$path}");
        }
    }

    /** The bare facility dashboard stays reachable for a facility admin. */
    public function test_facility_admin_can_open_scoped_dashboard(): void
    {
        $res = $this->actingAs($this->userWithRole('clinic_admin'))->get('/admin');
        $this->assertNotSame(403, $res->getStatusCode(), 'facility admin keeps their scoped dashboard');
    }
}
