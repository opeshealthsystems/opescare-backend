<?php
namespace Tests\Feature\PatientEngagement;

use App\Services\PatientEngagement\UssdSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UssdSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ussd_session_created_on_new_request(): void
    {
        $service  = new UssdSessionService();
        $response = $service->handle(
            sessionId:   'USSD-SESSION-001',
            phoneNumber: '+237670000001',
            text:        '',
            serviceCode: '*999#',
        );

        $this->assertStringContainsString('OpesCare', $response['message']);
        $this->assertEquals('CON', $response['type']);
    }

    public function test_ussd_menu_option_1_shows_appointments(): void
    {
        $service  = new UssdSessionService();
        $response = $service->handle(
            sessionId:   'USSD-SESSION-002',
            phoneNumber: '+237670000001',
            text:        '1',
            serviceCode: '*999#',
        );

        $this->assertStringContainsString('appointment', strtolower($response['message']));
    }
}
