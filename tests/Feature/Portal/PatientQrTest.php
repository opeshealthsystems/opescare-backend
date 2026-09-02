<?php
namespace Tests\Feature\Portal;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_temp_qr_returns_url_and_expires_in(): void
    {
        $patient = Patient::factory()->create(['health_id' => 'OC-QR-001', 'is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);
        $this->actingAs($user);

        $response = $this->withSession(['active_facility_id' => 1])
            ->postJson(route('portals.patient.qr'));
        $response->assertStatus(200);
        $response->assertJsonStructure(['url', 'expires_in']);
        $this->assertStringContainsString('/verify/qr/', $response->json('url'));
        $this->assertEquals(3600, $response->json('expires_in'));

        // Ensure token is path-based (not a query param) — tokens in query strings are logged by most servers
        $url = $response->json('url');
        $parsed = parse_url($url);
        $this->assertArrayNotHasKey('query', $parsed, 'Token must be in path, not query string');
        $this->assertStringNotContainsString('token=', $url);
    }

    public function test_generate_temp_qr_requires_auth(): void
    {
        $response = $this->postJson(route('portals.patient.qr'));
        $response->assertStatus(401);
    }

    public function test_generate_temp_qr_returns_404_when_no_patient_linked(): void
    {
        $user = User::factory()->create(['patient_id' => null]);
        $this->actingAs($user);

        $response = $this->withSession(['active_facility_id' => 1])
            ->postJson(route('portals.patient.qr'));
        $response->assertStatus(404);
    }
}
