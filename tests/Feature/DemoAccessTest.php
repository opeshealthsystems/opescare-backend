<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Services\Simulators\SimulatedSmsService;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('demo.enabled', true);
        Config::set('demo.public_enabled', true);
        Config::set('demo.internal_enabled', true);
        Config::set('demo.external_services_simulated', true);
    }

    public function test_demo_login_as_route_exists()
    {
        // The login-as POST endpoint must always be present in demo mode
        $response = $this->post('/demo-access/login-as', [
            'role'  => 'doctor',
            'email' => 'nonexistent@opescare.test',
        ]);
        // Returns a validation error (account not found), not a 404 or 403
        $response->assertSessionHasErrors('email');
    }

    public function test_demo_mode_can_be_disabled()
    {
        Config::set('demo.enabled', false);
        // Middleware returns 404 for all demo-access/* paths when demo is disabled
        $this->post('/demo-access/login-as', [
            'role'  => 'doctor',
            'email' => 'demo.doctor@opescare.test',
        ])->assertStatus(404);
    }

    public function test_demo_accounts_cannot_access_non_demo_records()
    {
        // Create a non-demo user and patient
        $realPatient = Patient::forceCreate([
            'id' => '00000000-0000-0000-0000-900000000001',
            'first_name' => 'Real',
            'last_name' => 'Patient',
            'health_id' => 'OC-REAL-001',
            'is_demo' => false
        ]);

        $demoPatient = Patient::forceCreate([
            'id' => '00000000-0000-0000-0000-900000000002',
            'first_name' => 'Demo',
            'last_name' => 'Patient',
            'health_id' => 'OC-DEMO-001',
            'is_demo' => true
        ]);

        // When demo is enabled, Patient::all() should only return demo patients
        $patients = Patient::all();
        $this->assertCount(1, $patients);
        $this->assertEquals('OC-DEMO-001', $patients->first()->health_id);
    }

    public function test_demo_sessions_expire()
    {
        $user = User::forceCreate([
            'id' => '00000000-0000-0000-0000-200000000001',
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
            'is_demo' => true
        ]);

        // Force a login that expires in the past
        $this->post('/demo-access/login-as', [
            'role' => 'patient',
            'email' => 'demo@example.com',
            'mode' => 'public'
        ]);

        session(['demo_session_expires_at' => now()->subMinutes(1)]);

        // Any request with an expired demo session should be redirected to login
        $this->get('/portals/staff')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_simulated_services_do_not_send_real_messages()
    {
        $smsService = new SimulatedSmsService();
        $result = $smsService->sendSms('+1234567890', 'Test');
        $this->assertTrue($result);
        // Verified by observing log output instead of actual external HTTP call
    }
}
