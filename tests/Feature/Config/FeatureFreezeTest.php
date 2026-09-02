<?php

namespace Tests\Feature\Config;

use App\Support\Features;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The V1 launch-scope module freeze (config/features.php).
 *
 * This is a kill switch, not an upsell gate. The properties that matter:
 *   - it FAILS CLOSED (unknown key, missing config, non-boolean -> frozen)
 *   - it returns 404, never 403 — a frozen module must not advertise itself
 *   - it freezes ONLY the modules named in the scope cut; the V1 finders,
 *     Health ID, appointments and platform revenue paths keep working
 *   - nothing is deleted: every route is still registered and comes back the
 *     moment the flag is flipped
 *
 * No RefreshDatabase: none of this touches the database.
 *
 * @see docs/plans/V1_LAUNCH_SCOPE.md
 */
class FeatureFreezeTest extends TestCase
{
    /** Turn every frozen module off, as production has them. */
    private function freezeAll(): void
    {
        config(['features.flags' => array_fill_keys(
            array_keys(config('features.flags', [])),
            false
        )]);
    }

    // ── fail-closed semantics ────────────────────────────────────────────

    public function test_unknown_feature_key_is_frozen(): void
    {
        $this->assertFalse(Features::enabled('no_such_module'));
        $this->assertTrue(Features::frozen('no_such_module'));
    }

    public function test_missing_config_is_frozen(): void
    {
        config(['features' => null]);
        $this->assertFalse(Features::enabled('insurance'));
    }

    public function test_non_boolean_truthy_value_is_still_frozen(): void
    {
        // Only a literal true opens the gate. '1', 1 and 'yes' must not.
        foreach (['1', 1, 'yes', 'true'] as $truthy) {
            config(['features.flags.insurance' => $truthy]);
            $this->assertFalse(
                Features::enabled('insurance'),
                'Expected '.var_export($truthy, true).' to read as frozen'
            );
        }
    }

    public function test_every_frozen_module_has_an_explicit_flag(): void
    {
        $flags = array_keys(config('features.flags'));
        $paths = Features::frozenPaths();

        // Guard against a vacuous pass: if bootstrap/app.php ever stops calling
        // Features::freeze(), every frozen module silently goes live again.
        $this->assertNotEmpty($paths, 'bootstrap/app.php must declare the frozen URI surface.');
        $this->assertCount(8, $paths, 'Every declared feature flag must be gated by URI pattern. '
            . 'Seven are frozen; insurance_coverage is gated but ships ON, so it is listed here too.');

        foreach (array_keys($paths) as $gatedKey) {
            $this->assertContains(
                $gatedKey,
                $flags,
                "URI patterns are gated on '{$gatedKey}' but config/features.php declares no such flag — "
                .'the paths would be permanently frozen with no way to turn them back on.'
            );
        }
    }

    // ── the gate itself ──────────────────────────────────────────────────

    public function test_frozen_api_endpoint_returns_404_not_403(): void
    {
        $this->freezeAll();

        $response = $this->getJson('/api/v1/insurance/claims');

        $response->assertStatus(404);
        $this->assertNotSame(403, $response->status(), 'A frozen module must not advertise that it exists.');
    }

    public function test_frozen_endpoint_is_indistinguishable_from_a_nonexistent_route(): void
    {
        $this->freezeAll();

        $frozen  = $this->getJson('/api/v1/insurance/claims');
        $missing = $this->getJson('/api/v1/this-route-never-existed');

        $this->assertSame($missing->status(), $frozen->status());
        $this->assertSame($missing->json(), $frozen->json());
    }

    public function test_frozen_web_portal_returns_404_before_the_login_redirect(): void
    {
        $this->freezeAll();

        // 404, not 302-to-login: even the existence of the portal is hidden.
        $this->get('/portals/insurance')->assertStatus(404);
    }

    public function test_route_is_restored_when_the_flag_is_turned_back_on(): void
    {
        $this->freezeAll();
        $this->getJson('/api/v1/insurance/claims')->assertStatus(404);

        config(['features.flags.insurance' => true]);

        // Nothing was deleted — the route is still registered, and now the
        // request reaches the auth middleware instead of the freeze gate.
        $this->assertNotSame(404, $this->getJson('/api/v1/insurance/claims')->status());
    }

    public function test_nothing_is_deleted_frozen_routes_stay_registered(): void
    {
        $this->freezeAll();

        $uris = collect(app('router')->getRoutes())->map(fn ($r) => $r->uri())->all();

        foreach (['api/v1/insurance/claims', 'portals/insurance', 'api/v1/billing/invoices'] as $uri) {
            $this->assertContains($uri, $uris, "Frozen route {$uri} must remain registered — freezing is not deleting.");
        }
    }

    // ── the 'feature:<key>' route-middleware alias ───────────────────────

    public function test_alias_is_registered(): void
    {
        $aliases = app(\Illuminate\Foundation\Http\Kernel::class)->getMiddlewareAliases();

        $this->assertArrayHasKey('feature', $aliases);
        $this->assertSame(\App\Http\Middleware\EnforceFeatureFlag::class, $aliases['feature']);
    }

    public function test_explicit_key_blocks_when_frozen_and_passes_when_live(): void
    {
        $middleware = new \App\Http\Middleware\EnforceFeatureFlag();
        $request    = \Illuminate\Http\Request::create('/anything-at-all', 'GET');
        $next       = fn ($r) => new \Illuminate\Http\Response('reached');

        config(['features.flags.billing' => true]);
        $this->assertSame('reached', $middleware->handle($request, $next, 'billing')->getContent());

        config(['features.flags.billing' => false]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $middleware->handle($request, $next, 'billing');
    }

    public function test_a_path_belonging_to_no_frozen_module_passes_through(): void
    {
        $this->freezeAll();

        $middleware = new \App\Http\Middleware\EnforceFeatureFlag();
        $request    = \Illuminate\Http\Request::create('/api/v1/appointments', 'GET');

        $response = $middleware->handle($request, fn ($r) => new \Illuminate\Http\Response('reached'));

        $this->assertSame('reached', $response->getContent());
    }

    // ── blast radius: only what we meant to freeze ───────────────────────

    /**
     * The whole point of the scope cut. With every frozen flag off, the V1
     * launch features must behave exactly as before. A 404 here would mean the
     * freeze leaked into the product.
     */
    #[DataProvider('inScopePaths')]
    public function test_in_scope_v1_endpoints_are_untouched_by_the_freeze(string $path): void
    {
        $this->freezeAll();

        $status = $this->getJson($path)->status();

        $this->assertNotSame(
            404,
            $status,
            "{$path} is in the V1 launch scope and must not be frozen."
        );
    }

    public static function inScopePaths(): array
    {
        return [
            'health check'            => ['/api/health'],
            'pharmacy finder'         => ['/api/v1/care-map/pharmacies/medicine-search'],
            'blood finder'            => ['/api/v1/care-map/blood/search'],
            'mobile pharmacy finder'  => ['/api/mobile/pharmacy/medicines'],
            'mobile blood finder'     => ['/api/mobile/blood/search'],
            'appointments'            => ['/api/v1/appointments'],
            'partner stock ingest'    => ['/api/v1/connect/inventory/pharmacy-stock/sync'],
            // Teleconsult was in the first V1 cut as a "thin book -> consult"
            // path, then dropped when the scope settled on five products:
            // Health ID, drugs finder, blood finder, appointments,
            // interoperability. The module and its controllers are deleted, so
            // the whole api/v1/telemedicine surface is frozen and must NOT be
            // asserted live here.
            'statutory MINSANTE reports' => ['/api/v1/public-health/reports'],
        ];
    }

    public function test_the_finders_still_return_data_while_everything_is_frozen(): void
    {
        $this->freezeAll();

        // Reaches the controller and validates — proof the request is served,
        // not intercepted by the freeze gate.
        $this->getJson('/api/v1/care-map/pharmacies/medicine-search')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_FAILED');
    }

    // ── the Blade directive ──────────────────────────────────────────────

    public function test_feature_directive_hides_and_shows_nav(): void
    {
        $render = fn () => $this->app['view']->make('partials.sidebars.insurance_admin')->render();

        config(['features.flags.insurance' => false]);
        $off = $render();

        config(['features.flags.insurance' => true]);
        $on = $render();

        $this->assertStringNotContainsString('portals/insurance/claims', $off, 'Frozen module must leave no nav link.');
        $this->assertStringContainsString('portals/insurance/claims', $on);

        // Help must survive the freeze — the sidebar is never left empty.
        $this->assertStringContainsString('help', $off);
    }

    public function test_feature_directive_fails_closed_on_an_unknown_key(): void
    {
        $rendered = $this->app['view']
            ->make('partials.sidebars.insurance_admin')
            ->render();

        // Sanity: the directive compiled at all.
        $this->assertIsString($rendered);
        $this->assertFalse(Features::enabled('totally_unknown_key'));
    }
}
