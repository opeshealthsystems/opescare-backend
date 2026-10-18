<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every admin page is in the admin navigation.
 *
 * The rule in CLAUDE.md is route + nav link + route:list — a page reachable by
 * URL only is a page nobody uses. This audit found two such pages (the
 * governance log and the terminology log: each had a route, a view and a
 * button on its parent desk, and no entry in the nav). It is frozen here so
 * the next route added under /admin without a link fails the suite.
 *
 * The nav is rendered from `portals.admin._admin_nav`, which is the single
 * source of truth: `control_center/_sidebar` includes it rather than listing
 * links of its own, and that is asserted too.
 */
class AdminNavCompletenessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Parameterless admin GET routes that are deliberately NOT in the nav.
     * Each one is reached from the page it belongs to; none is a destination
     * on its own.
     */
    private const NOT_IN_NAV = [
        'portals.admin.kpi.export'            => 'CSV download, submitted from the form on /admin/kpi',
        'portals.admin.kpi.trend'             => 'drill-down from a KPI tile on /admin/kpi (needs ?metric)',
        'portals.admin.certifications.create' => 'the "new certification" button on its index',
        'portals.admin.data_import.create'    => 'the "new import" button on its index',
        'portals.admin.partners.create'       => 'the "new key" button on its index',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    private function platformAdmin(): User
    {
        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $user->role_id = Role::where('name', 'super_admin')->firstOrFail()->id;
        $user->save();

        return $user;
    }

    /** @return array<string, \Illuminate\Routing\Route> name => route, for parameterless admin GETs */
    private function adminIndexRoutes(): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            if (! preg_match('#^(admin|portals/admin)(/|$)#', $uri)) {
                continue;
            }
            if (str_contains($uri, '{')) {
                continue;
            }
            // Legacy aliases (routes/web.php: 'portals/admin' => 'admin') are
            // redirects, not pages; the page they land on is checked instead.
            if (str_contains($route->getActionName(), 'RedirectController')) {
                continue;
            }
            $out[$route->getName() ?: $uri] = $route;
        }
        ksort($out);

        return $out;
    }

    private function renderedNav(): string
    {
        $this->actingAs($this->platformAdmin());

        return view('portals.admin._admin_nav')->render();
    }

    public function test_the_control_center_sidebar_delegates_to_the_shared_nav(): void
    {
        $sidebar = file_get_contents(resource_path('views/portals/admin/control_center/_sidebar.blade.php'));

        $this->assertStringContainsString("@include('portals.admin._admin_nav')", $sidebar);
        $this->assertStringNotContainsString('<a href=', $sidebar, 'links belong in _admin_nav, not in the sidebar wrapper');
    }

    public function test_every_parameterless_admin_get_route_has_a_nav_link_or_is_allow_listed(): void
    {
        $routes = $this->adminIndexRoutes();
        $this->assertNotEmpty($routes, 'no admin routes were found at all');

        $nav = $this->renderedNav();
        preg_match_all('/href="([^"]+)"/', $nav, $m);
        $hrefs = array_map(fn ($h) => parse_url(html_entity_decode($h), PHP_URL_PATH), $m[1]);

        // Some pages answer under two URIs with two names (admin/users is also
        // portals/admin/users): one link to the ACTION is enough.
        $urisByAction = [];
        foreach ($routes as $route) {
            $urisByAction[$route->getActionName()][] = '/' . ltrim($route->uri(), '/');
        }

        $missing = [];
        foreach ($routes as $name => $route) {
            if (array_key_exists($name, self::NOT_IN_NAV)) {
                continue;
            }
            $candidates = $urisByAction[$route->getActionName()] ?? ['/' . ltrim($route->uri(), '/')];
            if (array_intersect($candidates, $hrefs) === []) {
                $missing[] = "{$name} ({$route->uri()})";
            }
        }

        $this->assertSame([], $missing, "Admin routes with no link in _admin_nav:\n" . implode("\n", $missing));
    }

    public function test_the_allow_list_only_names_routes_that_still_exist(): void
    {
        $routes = $this->adminIndexRoutes();

        foreach (array_keys(self::NOT_IN_NAV) as $name) {
            $this->assertArrayHasKey($name, $routes, "allow-listed route {$name} no longer exists; drop it from NOT_IN_NAV");
        }
    }

    public function test_every_nav_link_opens_for_a_platform_admin(): void
    {
        $admin = $this->platformAdmin();
        $this->actingAs($admin);
        $nav = view('portals.admin._admin_nav')->render();

        preg_match_all('/href="([^"]+)"/', $nav, $m);
        $paths = array_values(array_unique(array_filter(array_map(
            fn ($h) => parse_url(html_entity_decode($h), PHP_URL_PATH),
            $m[1]
        ))));

        $this->assertNotEmpty($paths);

        $failures = [];
        foreach ($paths as $path) {
            $res = $this->actingAs($admin)->withSession(['mfa.verified' => true])->get($path);
            // follow in-app redirects (a desk may bounce to its default tab)
            $hops = 0;
            while ($res->isRedirect() && $hops++ < 3) {
                $res = $this->actingAs($admin)->withSession(['mfa.verified' => true])->get($res->headers->get('Location'));
            }
            // The import desk fails CLOSED without a facility in context (409,
            // pinned by DataImportDeskTest): a platform admin with no active
            // facility is meant to see that, not a page.
            if ($path === '/admin/data-import' && $res->status() === 409) {
                continue;
            }
            if ($res->status() !== 200) {
                $failures[] = "{$path} -> {$res->status()}";
            }
        }

        $this->assertSame([], $failures, "Nav links a platform admin cannot open:\n" . implode("\n", $failures));
    }

    public function test_every_kpi_tile_on_the_dashboard_is_a_link(): void
    {
        $admin = $this->platformAdmin();
        $html = $this->actingAs($admin)->withSession(['mfa.verified' => true])->get(route('portals.admin'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/<div class="od-kpi[ "]/', $html, 'a KPI tile that goes nowhere');
        $this->assertMatchesRegularExpression('/<a href="[^"]+" class="od-kpi[ "]/', $html);
    }
}
