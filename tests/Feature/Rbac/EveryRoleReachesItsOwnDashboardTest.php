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
 * A role must be able to open the portal its own sidebar points at.
 *
 * The dashboard audit found ten roles whose sidebar links were all refused by
 * EnsurePortalAccess — the role was listed under a different portal prefix
 * (or none), so its own "Dashboard" link redirected it away, or to /login,
 * every time. A dashboard nobody can open is not a dashboard.
 *
 * Three different outcomes are pinned here, because they are three different
 * facts:
 *   - a misfiled HUMAN role now reaches its portal;
 *   - a role whose module was REMOVED lands on the "portal unavailable" page,
 *     not on /login, because a successful sign-in that shows the login page
 *     again is a dead end dressed as a bug;
 *   - a DEVICE role is still denied, on purpose. A kiosk credential must not
 *     be admitted to the clinical desk just because it has a sidebar file.
 */
class EveryRoleReachesItsOwnDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    private function as(string $roleName): static
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'status'              => 'active',
            'primary_facility_id' => Facility::factory()->create()->id,
        ]);
        $user->role_id = $role->id;
        $user->save();

        return $this->actingAs($user->fresh())->withSession(['mfa.verified' => true]);
    }

    /** Not a redirect away from the portal the sidebar targets. */
    private function assertReaches(string $roleName, string $path): void
    {
        $response = $this->as($roleName)->get($path);

        $this->assertNotSame(302, $response->getStatusCode(),
            "{$roleName} must not be redirected away from {$path}, which its own sidebar links to"
            . ' (got 302 → ' . ($response->headers->get('Location') ?? '?') . ')');
        $this->assertNotSame(403, $response->getStatusCode(),
            "{$roleName} must not be refused at {$path}");
    }

    public function test_finance_reaches_the_operations_desk_its_sidebar_points_at(): void
    {
        // Renders the receptionist sidebar (billing), was listed under /admin only.
        $this->assertReaches('finance', '/facility');
    }

    public function test_partner_technical_reaches_the_developer_portal(): void
    {
        // dashboard_profile_key = developer, was listed under /admin only.
        $this->assertReaches('partner_technical', '/developer');
    }

    public function test_public_health_api_reaches_the_developer_portal(): void
    {
        // Was in no portal list at all.
        $this->assertReaches('public_health_api', '/developer');
    }

    public function test_bridge_admin_reaches_the_admin_landing(): void
    {
        $this->assertReaches('bridge_admin', '/admin');
    }

    public function test_bridge_admin_is_still_refused_beneath_the_admin_landing(): void
    {
        // Admitted to the landing only; /admin/* stays platform tier.
        $this->as('bridge_admin')->get('/admin/users')->assertForbidden();
    }

    public function test_a_role_whose_module_was_removed_lands_on_portal_unavailable_not_login(): void
    {
        foreach (['insurance_admin', 'lite_staff', 'outreach_team'] as $role) {
            $this->as($role)
                ->get('/facility')
                ->assertRedirect(route('portal.unavailable'));
        }
    }

    public function test_a_device_role_is_still_denied_the_clinical_desk(): void
    {
        // A sidebar file is not a grant. kiosk renders `device_kiosk`, whose
        // links point at /facility; admitting it would hand a device key the
        // whole desk. Denied stays denied.
        $this->as('kiosk')
            ->get('/facility')
            ->assertRedirect('/login');
    }

    public function test_developer_tier_sidebars_no_longer_link_to_the_admin_portal(): void
    {
        // Both "Dashboard" links pointed at /admin, which EnsurePortalAccess
        // silently redirected back to /developer — a link that never showed a
        // dashboard. And "Webhooks" was the same route as "My Apps".
        foreach (['api_partner_admin', 'webhook_manager'] as $partial) {
            $blade = file_get_contents(resource_path("views/partials/sidebars/{$partial}.blade.php"));

            $this->assertStringNotContainsString("route('portals.admin')", $blade, $partial);
            $this->assertStringContainsString("route('portals.developer.dashboard')", $blade, $partial);
            // The per-app route needs a {clientId}; a bare route() call on it threw on
            // every page. Either the overview (portals.developer.webhooks) or a
            // parameterised per-app link is a real Webhooks link.
            $this->assertMatchesRegularExpression("/route\('portals\.developer\.webhook(s|_deliveries', )/", $blade, $partial);
        }
    }
}
