<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Demo Access routes.
 *
 * Covers:
 *  - GET /demo-access        → redirects to /demo-access/public when demo enabled
 *  - GET /demo-access/public → 200 when enabled, 404 when disabled
 *  - GET /demo-access/internal → 200 when enabled, 404 when disabled
 *  - POST /demo-access/login-as → logs in demo user, creates session data
 *  - POST /demo-access/login-as → rejects when demo user not found
 *  - POST /demo-access/login-as → 403 when demo is globally disabled
 */
class DemoRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Start each test with demo enabled (the nominal state for demo routes)
        config([
            'demo.enabled'          => true,
            'demo.public_enabled'   => true,
            'demo.internal_enabled' => true,
        ]);
    }

    // ── GET /demo-access ────────────────────────────────────────────────────

    public function test_demo_index_redirects_to_public_when_enabled(): void
    {
        $response = $this->get('/demo-access');

        $response->assertRedirect('/demo-access/public');
    }

    public function test_demo_index_returns_404_when_demo_disabled(): void
    {
        config(['demo.enabled' => false]);

        $response = $this->get('/demo-access');

        $response->assertNotFound();
    }

    // ── GET /demo-access/public ──────────────────────────────────────────────

    public function test_public_demo_page_is_accessible_when_enabled(): void
    {
        $response = $this->get('/demo-access/public');

        $response->assertOk();
        $response->assertViewIs('demo.public');
    }

    public function test_public_demo_page_returns_404_when_demo_disabled(): void
    {
        config(['demo.enabled' => false]);

        $response = $this->get('/demo-access/public');

        $response->assertNotFound();
    }

    public function test_public_demo_page_returns_404_when_public_not_enabled(): void
    {
        config(['demo.public_enabled' => false]);

        $response = $this->get('/demo-access/public');

        $response->assertNotFound();
    }

    // ── GET /demo-access/internal ────────────────────────────────────────────

    public function test_internal_demo_page_is_accessible_when_enabled(): void
    {
        $response = $this->get('/demo-access/internal');

        $response->assertOk();
        $response->assertViewIs('demo.internal');
    }

    public function test_internal_demo_page_returns_404_when_demo_disabled(): void
    {
        config(['demo.enabled' => false]);

        $response = $this->get('/demo-access/internal');

        $response->assertNotFound();
    }

    public function test_internal_demo_page_returns_404_when_internal_not_enabled(): void
    {
        config(['demo.internal_enabled' => false]);

        $response = $this->get('/demo-access/internal');

        $response->assertNotFound();
    }

    // ── POST /demo-access/login-as ───────────────────────────────────────────

    public function test_login_as_authenticates_demo_user_and_sets_session(): void
    {
        // Enable demo so forceCreate passes the global scope
        config(['demo.enabled' => true]);

        $demoUser = User::forceCreate([
            'name'     => 'Demo Patient',
            'email'    => 'demo_patient@demo.test',
            'password' => bcrypt('DemoPass!2026'),
            'is_demo'  => true,
        ]);

        // Configure a short session lifetime for the public mode
        config(['demo.session.public_lifetime_minutes' => 30]);

        $response = $this->post('/demo-access/login-as', [
            'role'  => 'patient',
            'email' => $demoUser->email,
            'mode'  => 'public',
        ]);

        // Should redirect (either to dashboard or portal), not stay on the form
        $response->assertRedirect();
        $response->assertSessionMissing('errors');

        // Session must carry demo metadata
        $this->assertEquals('public', session('demo_mode_type'));
        $this->assertEquals('patient', session('demo_role'));
        $this->assertNotNull(session('demo_session_expires_at'));
    }

    public function test_login_as_returns_back_with_error_when_user_not_found(): void
    {
        $response = $this->from('/demo-access/public')
                         ->post('/demo-access/login-as', [
                             'role'  => 'patient',
                             'email' => 'nonexistent_demo@demo.test',
                             'mode'  => 'public',
                         ]);

        $response->assertRedirect('/demo-access/public');
        $response->assertSessionHasErrors('email');
    }

    public function test_login_as_rejects_real_user_account(): void
    {
        // A real (non-demo) user should not be loginable as a demo user
        config(['demo.enabled' => false]);
        $realUser = User::forceCreate([
            'name'     => 'Real Doctor',
            'email'    => 'real_doctor@hospital.test',
            'password' => bcrypt('secret'),
            'is_demo'  => false,
        ]);
        config(['demo.enabled' => true]);

        $response = $this->from('/demo-access/public')
                         ->post('/demo-access/login-as', [
                             'role'  => 'doctor',
                             'email' => $realUser->email,
                             'mode'  => 'public',
                         ]);

        // Query requires is_demo=true, so real user won't match → error
        $response->assertRedirect('/demo-access/public');
        $response->assertSessionHasErrors('email');
    }

    public function test_login_as_returns_404_when_demo_globally_disabled(): void
    {
        // DemoSessionMiddleware intercepts all demo-access* routes when
        // demo.enabled = false and calls abort(404) before the controller fires.
        config(['demo.enabled' => false]);

        $response = $this->post('/demo-access/login-as', [
            'role'  => 'patient',
            'email' => 'demo_patient@demo.test',
            'mode'  => 'public',
        ]);

        $response->assertNotFound();
    }

    public function test_login_as_validates_required_fields(): void
    {
        $response = $this->post('/demo-access/login-as', []);

        $response->assertSessionHasErrors(['role', 'email']);
    }
}
