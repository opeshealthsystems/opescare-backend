<?php

namespace Tests\Feature\Portal;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Feature tests for DemoDataScope middleware.
 *
 * Verifies that the middleware activates demo isolation (config demo.enabled)
 * per-request for is_demo users, and leaves it unchanged for real users.
 */
class DemoDataScopeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => false]);
    }

    public function test_demo_user_triggers_demo_enabled_config(): void
    {
        config(['demo.enabled' => true]);

        $user = User::forceCreate([
            'name'     => 'Demo User',
            'email'    => 'ddscope_demo@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => true,
        ]);

        config(['demo.enabled' => false]); // reset before request

        $this->actingAs($user)
             ->get('/select-facility'); // any middleware-covered route

        // After the middleware ran during the test request, we check the
        // side-effect by confirming the middleware logic would have activated demo.
        // We verify this by calling the middleware directly on a fake request.
        $request  = \Illuminate\Http\Request::create('/test');
        $request->setLaravelSession(app('session.store'));
        Auth::setUser($user);

        config(['demo.enabled' => false]);
        $middleware = new \App\Http\Middleware\DemoDataScope();
        $called = false;
        $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertTrue($called);
        $this->assertTrue(config('demo.enabled'), 'Middleware must set demo.enabled=true for is_demo users');
    }

    public function test_real_user_does_not_activate_demo_mode(): void
    {
        config(['demo.enabled' => false]);

        $user = User::forceCreate([
            'name'     => 'Real User',
            'email'    => 'ddscope_real@test.com',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);

        $request = \Illuminate\Http\Request::create('/test');
        Auth::setUser($user);

        $middleware = new \App\Http\Middleware\DemoDataScope();
        $middleware->handle($request, function () {
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertFalse(config('demo.enabled'), 'Middleware must NOT activate demo mode for real users');
    }

    public function test_unauthenticated_request_does_not_activate_demo_mode(): void
    {
        config(['demo.enabled' => false]);

        $request = \Illuminate\Http\Request::create('/test');
        // No user set

        $middleware = new \App\Http\Middleware\DemoDataScope();
        $middleware->handle($request, function () {
            return new \Illuminate\Http\Response('ok');
        });

        $this->assertFalse(config('demo.enabled'), 'Middleware must NOT activate demo mode when unauthenticated');
    }

    public function test_demo_isolation_scopes_model_queries_to_demo_records(): void
    {
        // Create one real and one demo record directly
        config(['demo.enabled' => false]);
        Facility::forceCreate(['id' => 'dd500000-0000-0000-0000-000000000001', 'name' => 'Real Hospital', 'type' => 'hospital', 'is_demo' => false]);

        config(['demo.enabled' => true]);
        Facility::forceCreate(['id' => 'dd500000-0000-0000-0000-000000000002', 'name' => 'Demo Hospital', 'type' => 'hospital', 'is_demo' => true]);

        // When demo.enabled=true, only demo records visible
        $visible = Facility::all();
        $this->assertCount(1, $visible);
        $this->assertEquals('Demo Hospital', $visible->first()->name);

        // When demo.enabled=false, only real records visible
        config(['demo.enabled' => false]);
        $visible = Facility::all();
        $this->assertCount(1, $visible);
        $this->assertEquals('Real Hospital', $visible->first()->name);
    }
}
