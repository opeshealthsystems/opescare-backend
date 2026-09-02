<?php

namespace Tests\Feature\Navigation;

use App\Http\Middleware\RequirePlatformAdmin;
use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use App\Support\Features;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * A nav link the signed-in user cannot open is a bug, not a hint.
 *
 * This is the audit that found 12 of them, frozen into a test. Two separate
 * enforcement changes had each been applied at the middleware layer while the
 * navigation was left pointing at the old world:
 *
 *   - the V1 launch-scope freeze (EnforceFeatureFlag, 404), and
 *   - the platform/facility privilege split (RequirePlatformAdmin, 403).
 *
 * The first was already handled by @feature in the sidebars. The second had no
 * equivalent, so facility admins, facility executives, API partners and webhook
 * managers all saw menu items that only ever produced 403. @platformadmin is
 * that equivalent, and this test is what keeps both honest.
 *
 * Note it asserts against RENDERED html. Scanning the Blade source counts links
 * that @feature already hides, which is how the first pass of this audit
 * overcounted by a factor of four.
 */
class SidebarLinksAreReachableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles are not seeded globally for the suite, and every assertion
        // here is about what a given ROLE sees — without them the test would
        // exercise nothing and pass vacuously.
        // Same order DatabaseSeeder uses — roles carry FKs onto both.
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /**
     * One user per dashboard profile, built rather than seeded so the test does
     * not depend on demo data being present.
     */
    private function userForRole(string $roleName): ?User
    {
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return null;
        }

        return User::factory()->create([
            'role_id'            => $role->id,
            'primary_facility_id' => Facility::factory()->create()->id,
            'status'             => 'active',
        ]);
    }

    public static function rolesUnderTest(): array
    {
        // A spread across the tiers that actually differ: facility admin vs
        // platform admin, a developer-tier role, and ordinary clinical staff.
        return ['facility_admin', 'hospital_admin', 'doctor', 'nurse', 'pharmacist', 'labtech', 'receptionist'];
    }

    public function test_no_sidebar_offers_a_link_the_user_cannot_open(): void
    {
        $failures = [];
        $checked  = 0;

        foreach (self::rolesUnderTest() as $roleName) {
            $user = $this->userForRole($roleName);
            if (! $user) {
                continue;
            }

            $home = $this->actingAs($user)->get('/portals/staff');
            while ($home->isRedirect()) {
                $home = $this->actingAs($user)->get($home->headers->get('Location'));
            }
            if ($home->status() !== 200) {
                continue;   // covered by the home-page test below
            }

            // EVERY internal link on the landing page, not just the sidebar.
            // The last six of these hid in the dashboard body: the facility
            // admin's 'Platform overview' stat cards linked into six
            // platform-tier pages and 403'd on all of them, while also showing
            // cross-tenant totals. A sidebar-only check would still miss them.
            preg_match_all('/<a\b[^>]*href="([^"]+)"/i', $home->getContent(), $m);

            $hrefs = array_unique(array_filter($m[1]));

            foreach ($hrefs as $href) {
                $path = parse_url($href, PHP_URL_PATH);

                // Skip anchors-only, external hosts, and logout (a GET on it
                // would end the session mid-sweep).
                $host = parse_url($href, PHP_URL_HOST);
                if (! $path || ($host && $host !== 'localhost') || str_starts_with($path, '/logout')) {
                    continue;
                }

                $res = $this->actingAs($user)->get($path);
                $checked++;

                if ($res->status() >= 400) {
                    $failures[] = "{$roleName} sees {$path} which returns {$res->status()}";
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'no sidebar links were exercised at all');
        $this->assertSame([], $failures, "\n" . implode("\n", $failures) . "\n");
    }

    public function test_a_frozen_portal_never_becomes_a_login_destination(): void
    {
        // Insurance staff signed in successfully and were redirected onto
        // /portals/insurance, which EnforceFeatureFlag 404s. A correct login
        // that ends on a dead page is still a broken flow.
        config(['features.flags.insurance' => false]);

        $user = $this->userForRole('insurance_reviewer');
        if (! $user) {
            $this->markTestSkipped('insurance_reviewer role is not seeded');
        }

        $landing = app(\App\Services\Dashboard\DashboardProfileService::class)->landingUrlForUser($user);

        $this->assertStringContainsString(
            'portal-unavailable',
            $landing,
            'login still lands a user on a portal that is frozen off'
        );

        $this->actingAs($user)->get('/portal-unavailable')->assertOk();
    }

    public function test_a_frozen_landing_page_falls_back_to_the_portal_not_a_dead_end(): void
    {
        // The pharmacy dashboards are configured to open on
        // /portals/staff/inventory/pharmacy, which inventory_ops freezes — while
        // /portals/staff itself is alive and is their own portal. Sending them
        // to 'portal unavailable' would be technically true of that one page
        // and completely wrong about the user, who has a working portal.
        config(['features.flags.inventory_ops' => false]);

        $user = $this->userForRole('pharmacist');
        if (! $user) {
            $this->markTestSkipped('pharmacist role is not seeded');
        }

        $landing = app(\App\Services\Dashboard\DashboardProfileService::class)->landingUrlForUser($user);

        $this->assertStringNotContainsString('portal-unavailable', $landing,
            'a frozen landing page sent a user with a working portal to a dead end');
        $this->assertStringContainsString('/portals/', $landing);

        $this->actingAs($user)->get(parse_url($landing, PHP_URL_PATH))->assertOk();
    }

    public function test_freezing_a_module_still_conceals_it_completely(): void
    {
        // The redirect above must not have softened the 404. A frozen path has
        // to stay byte-identical to a route that never existed, or it tells an
        // enumerating client exactly which modules to come back for.
        config(['features.flags.insurance' => false]);

        $user = $this->userForRole('insurance_reviewer');
        if (! $user) {
            $this->markTestSkipped('insurance_reviewer role is not seeded');
        }

        $this->actingAs($user)->get('/portals/insurance')->assertNotFound();
        $this->actingAs($user)->get('/portals/insurance/claims')->assertNotFound();
    }

    public function test_platform_only_paths_and_the_nav_gate_agree(): void
    {
        // @platformadmin delegates to RequirePlatformAdmin so the nav and the
        // middleware cannot drift. If someone reimplements either side, this
        // fails rather than quietly reintroducing a menu full of 403s.
        $this->assertTrue(RequirePlatformAdmin::isPlatformOnlyPath('portals/admin/kpi'));
        $this->assertTrue(RequirePlatformAdmin::isPlatformOnlyPath('portals/admin/subscription'));
        $this->assertTrue(RequirePlatformAdmin::isPlatformOnlyPath('admin/users'));

        // The bare facility dashboard is explicitly NOT platform-only.
        $this->assertFalse(RequirePlatformAdmin::isPlatformOnlyPath('portals/admin'));
        $this->assertFalse(RequirePlatformAdmin::isPlatformOnlyPath('portals/staff'));

        $facilityAdmin = $this->userForRole('hospital_admin');
        if ($facilityAdmin) {
            $this->assertFalse(
                RequirePlatformAdmin::isPlatformTier($facilityAdmin),
                'a facility admin must not read as platform tier'
            );
        }
    }

    public function test_every_named_public_and_portal_route_in_a_sidebar_exists(): void
    {
        // Cheap guard against a renamed route leaving a sidebar pointing at
        // nothing — route() would throw at render time.
        $known = [];
        foreach (RouteFacade::getRoutes() as $r) {
            if ($n = $r->getName()) {
                $known[$n] = true;
            }
        }

        $missing = [];
        foreach (glob(resource_path('views/partials/sidebars/*.blade.php')) as $file) {
            preg_match_all('/route\(\s*[\'"]([A-Za-z0-9_.\-]+)[\'"]/', file_get_contents($file), $m);
            foreach (array_unique($m[1]) as $name) {
                if (! isset($known[$name])) {
                    $missing[] = basename($file) . ' -> ' . $name;
                }
            }
        }

        $this->assertSame([], $missing, "\n" . implode("\n", $missing) . "\n");
    }
}
