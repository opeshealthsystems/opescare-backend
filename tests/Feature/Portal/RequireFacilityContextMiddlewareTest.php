<?php

namespace Tests\Feature\Portal;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for RequireFacilityContext middleware.
 *
 * Verifies the three resolution paths:
 *  1. Session already has active_facility_id → pass through.
 *  2. User has primary_facility_id → auto-set session and pass through.
 *  3. No facility available → redirect to select-facility.
 *
 * Also verifies exempt paths bypass the redirect.
 */
class RequireFacilityContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => false]);
    }

    // ── Unauthenticated ──────────────────────────────────────────────────────

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $middleware = new \App\Http\Middleware\RequireFacilityContext();
        $request    = \Illuminate\Http\Request::create('/portals/patient');

        $response = $middleware->handle($request, function () {
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }

    // ── Session already set ──────────────────────────────────────────────────

    public function test_request_with_active_facility_session_passes_through(): void
    {
        $facility = Facility::forceCreate([
            'id'      => 'afc00000-0000-0000-0000-000000000001',
            'name'    => 'Session Facility',
            'type'    => 'hospital',
            'is_demo' => false,
        ]);

        $user = User::forceCreate([
            'name'     => 'Session User',
            'email'    => 'rfc_session@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);
        session(['active_facility_id' => $facility->id]);

        $middleware = new \App\Http\Middleware\RequireFacilityContext();
        $request    = \Illuminate\Http\Request::create('/portals/patient');
        $request->setLaravelSession(app('session.store'));

        $called   = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertTrue($called, 'Next should be called when active_facility_id is in session');
        $this->assertEquals(200, $response->getStatusCode());
    }

    // ── Auto-resolve from primary_facility_id ────────────────────────────────

    public function test_user_with_primary_facility_auto_sets_session_and_passes_through(): void
    {
        $facility = Facility::forceCreate([
            'id'      => 'afc00000-0000-0000-0000-000000000002',
            'name'    => 'Primary Facility',
            'type'    => 'clinic',
            'is_demo' => false,
        ]);

        $user = User::forceCreate([
            'name'                => 'Primary User',
            'email'               => 'rfc_primary@test.com',
            'password'            => bcrypt('secret'),
            'primary_facility_id' => $facility->id,
            'is_demo'             => false,
        ]);

        $this->actingAs($user);

        // Ensure session does NOT pre-contain the facility
        session()->forget('active_facility_id');

        $middleware = new \App\Http\Middleware\RequireFacilityContext();
        $request    = \Illuminate\Http\Request::create('/portals/patient');
        $request->setLaravelSession(app('session.store'));

        $called   = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertTrue($called, 'Next should be called when primary_facility_id resolves');
        $this->assertEquals($facility->id, session('active_facility_id'),
            'Middleware should store primary_facility_id in session');
    }

    // ── No facility → redirect ───────────────────────────────────────────────

    public function test_user_without_facility_is_redirected_to_select_facility(): void
    {
        $user = User::forceCreate([
            'name'     => 'No Facility User',
            'email'    => 'rfc_nofac@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);
        session()->forget('active_facility_id');

        $middleware = new \App\Http\Middleware\RequireFacilityContext();
        // Use a path that requires facility context (provider portal, not patient portal)
        $request    = \Illuminate\Http\Request::create('/portals/provider');
        $request->setLaravelSession(app('session.store'));

        $response = $middleware->handle($request, function () {
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('select-facility', $location,
            'Should redirect to facility selector when no facility is available');
    }

    // ── Exempt paths ─────────────────────────────────────────────────────────

    public function test_select_facility_path_is_exempt_from_redirect(): void
    {
        $user = User::forceCreate([
            'name'     => 'Exempt User',
            'email'    => 'rfc_exempt@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);
        session()->forget('active_facility_id');

        $middleware = new \App\Http\Middleware\RequireFacilityContext();
        $request    = \Illuminate\Http\Request::create('/select-facility');
        $request->setLaravelSession(app('session.store'));

        $called   = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertTrue($called, 'select-facility route must bypass facility check');
    }

    public function test_portals_admin_path_is_exempt_from_redirect(): void
    {
        $user = User::forceCreate([
            'name'     => 'Admin User',
            'email'    => 'rfc_admin@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $this->actingAs($user);
        session()->forget('active_facility_id');

        $middleware = new \App\Http\Middleware\RequireFacilityContext();
        $request    = \Illuminate\Http\Request::create('/portals/admin/overview');
        $request->setLaravelSession(app('session.store'));

        $called   = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertTrue($called, 'portals/admin* routes must bypass facility check');
    }
}
