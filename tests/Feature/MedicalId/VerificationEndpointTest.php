<?php

namespace Tests\Feature\MedicalId;

use Tests\TestCase;
use App\Models\Patient;
use App\Models\MedicalIdAccessEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Identity\HealthIdGeneratorService;

class VerificationEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected Patient $patient;
    protected string $validHealthId;
    protected string $invalidPatientHealthId;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['demo.enabled' => false]);
        
        $generator = new HealthIdGeneratorService();
        $this->validHealthId = $generator->generate('CM');
        $this->invalidPatientHealthId = $generator->generate('CM');

        $this->patient = Patient::forceCreate([
            'health_id' => $this->validHealthId,
            'country_code' => 'CM',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sex' => 'male',
            'phone_number' => '1234567890',
            'date_of_birth' => '1990-01-01',
            'identity_status' => 'verified',
            'verification_status' => 'facility_verified',
            'is_demo' => false,
        ]);
    }

    public function test_verify_endpoint_returns_safe_preview_and_masks_name()
    {
        $response = $this->postJson('/api/v1/connect/medical-ids/verify', [
            'health_id' => $this->validHealthId,
            'purpose' => 'treatment',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'valid',
            'patient_preview' => [
                'display_name' => 'John D.', // Masked
                'sex' => 'male',
                'year_of_birth' => 1990,     // Only year
                'health_id' => $this->validHealthId,
            ]
        ]);

        // Ensure full details are NOT returned
        $response->assertJsonMissing(['date_of_birth' => '1990-01-01']);
        $response->assertJsonMissing(['last_name' => 'Doe']);

        // Verify audit log created
        $this->assertDatabaseHas('medical_id_access_events', [
            'health_id' => $this->validHealthId,
            'purpose' => 'treatment',
            'result' => 'success'
        ]);
    }

    public function test_verify_endpoint_audits_invalid_lookup()
    {
        $response = $this->postJson('/api/v1/connect/medical-ids/verify', [
            'health_id' => $this->invalidPatientHealthId,
            'purpose' => 'treatment',
        ]);

        $response->assertStatus(404);

        $this->assertDatabaseHas('medical_id_access_events', [
            'health_id' => $this->invalidPatientHealthId,
            'result' => 'denied'
        ]);
    }

    public function test_api_lookup_is_rate_limited()
    {
        // Hit it 31 times
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/v1/connect/medical-ids/verify', [
                'health_id' => $this->invalidPatientHealthId,
                'purpose' => 'treatment',
            ]);
        }

        $response = $this->postJson('/api/v1/connect/medical-ids/verify', [
            'health_id' => $this->invalidPatientHealthId,
            'purpose' => 'treatment',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }
}
