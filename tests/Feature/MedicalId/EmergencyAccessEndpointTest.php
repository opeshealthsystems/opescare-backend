<?php

namespace Tests\Feature\MedicalId;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Patient;
use App\Models\MedicalIdAccessEvent;
use App\Services\Identity\HealthIdGeneratorService;

class EmergencyAccessEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup a demo patient with a valid Health ID
        $generator = new HealthIdGeneratorService();
        $this->patient = Patient::create([
            'first_name' => 'Demo',
            'last_name' => 'Test',
            'country_code' => 'CM',
            'health_id' => $generator->generate('CM'),
            'verification_status' => 'facility_verified',
            'identity_status' => 'verified',
            'is_demo' => false
        ]);
    }

    private function clientHeaders(): array
    {
        return [
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ];
    }

    public function test_emergency_access_requires_reason()
    {
        $response = $this->postJson('/api/v1/connect/patients/emergency-profile', [
            'health_id' => $this->patient->health_id,
            // reason is intentionally missing
        ], $this->clientHeaders());

        // Platform-wide convention: the global API exception handler renders all
        // validation failures as 422 VALIDATION_FAILED (bootstrap/app.php).
        $response->assertStatus(422)
                 ->assertJsonPath('error_code', 'VALIDATION_FAILED');
    }

    public function test_emergency_access_with_reason_succeeds_and_audits()
    {
        $response = $this->postJson('/api/v1/connect/patients/emergency-profile', [
            'health_id' => $this->patient->health_id,
            'reason' => 'Unconscious patient arrived at ER after car accident'
        ], $this->clientHeaders());

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonStructure([
                     'profile' => [
                         'identity',
                         'allergies',
                         'chronic_conditions'
                     ]
                 ]);

        // Verify the audit log was created
        $this->assertDatabaseHas('medical_id_access_events', [
            'patient_id' => $this->patient->id,
            'health_id' => $this->patient->health_id,
            'access_type' => 'pull_emergency_profile',
            'purpose' => 'emergency_access',
            'result' => 'success'
        ]);
    }

    public function test_emergency_access_fails_with_invalid_health_id()
    {
        $response = $this->postJson('/api/v1/connect/patients/emergency-profile', [
            'health_id' => 'CM-HID-XXXX-YYYY-ZZZZ',
            'reason' => 'Unconscious patient arrived at ER after car accident'
        ], $this->clientHeaders());

        $response->assertStatus(404)
                 ->assertJsonPath('error_code', 'HEALTH_ID_NOT_FOUND');

        // Verify the failed lookup was audited under the dedicated
        // AuditEventType::EmergencyAccessDenied taxonomy.
        $this->assertDatabaseHas('medical_id_access_events', [
            'health_id' => 'CM-HID-XXXX-YYYY-ZZZZ',
            'access_type' => 'emergency_access_denied',
            'result' => 'denied'
        ]);
    }
}
