<?php

namespace Tests\Feature;

use App\Models\UssdSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UssdTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_callback_returns_con_with_main_menu(): void
    {
        $response = $this->post('/api/ussd/callback', [
            'sessionId'   => 'AT-SESS-001',
            'serviceCode' => '*384#',
            'phoneNumber' => '+237670000001',
            'text'        => '',
        ]);

        $response->assertStatus(200);
        $body = $response->getContent();
        $this->assertStringStartsWith('CON ', $body);
        $this->assertStringContainsString('Welcome to OpesCare', $body);
    }

    public function test_session_is_created_on_first_request(): void
    {
        $this->post('/api/ussd/callback', [
            'sessionId'   => 'AT-SESS-002',
            'serviceCode' => '*384#',
            'phoneNumber' => '+237670000002',
            'text'        => '',
        ]);

        $this->assertDatabaseHas('ussd_sessions', [
            'session_id'   => 'AT-SESS-002',
            'phone_number' => '+237670000002',
        ]);
    }

    public function test_selecting_emergency_returns_con_response(): void
    {
        // First call to create session at MAIN
        $this->post('/api/ussd/callback', [
            'sessionId'   => 'AT-SESS-003',
            'serviceCode' => '*384#',
            'phoneNumber' => '+237670000003',
            'text'        => '',
        ]);

        // Second call with choice 4
        $response = $this->post('/api/ussd/callback', [
            'sessionId'   => 'AT-SESS-003',
            'serviceCode' => '*384#',
            'phoneNumber' => '+237670000003',
            'text'        => '4',
        ]);

        $body = $response->getContent();
        $this->assertStringStartsWith('CON ', $body);
        $this->assertStringContainsString('Emergency', $body);
    }

    public function test_appointment_flow_returns_end_on_date_entry(): void
    {
        // Step 1: open session
        $this->post('/api/ussd/callback', [
            'sessionId' => 'AT-SESS-004', 'serviceCode' => '*384#',
            'phoneNumber' => '+237670000004', 'text' => '',
        ]);

        // Step 2: choose book appointment
        $this->post('/api/ussd/callback', [
            'sessionId' => 'AT-SESS-004', 'serviceCode' => '*384#',
            'phoneNumber' => '+237670000004', 'text' => '1',
        ]);

        // Step 3: enter facility code
        $this->post('/api/ussd/callback', [
            'sessionId' => 'AT-SESS-004', 'serviceCode' => '*384#',
            'phoneNumber' => '+237670000004', 'text' => '1*FC001',
        ]);

        // Step 4: enter date — expect END
        $response = $this->post('/api/ussd/callback', [
            'sessionId' => 'AT-SESS-004', 'serviceCode' => '*384#',
            'phoneNumber' => '+237670000004', 'text' => '1*FC001*28/05/2026',
        ]);

        $this->assertStringStartsWith('END ', $response->getContent());
    }
}
