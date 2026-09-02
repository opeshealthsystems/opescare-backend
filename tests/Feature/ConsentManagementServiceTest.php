<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Facility;
use App\Models\User;
use App\Modules\ConsentManagement\Services\ConsentManagementService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ConsentManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ConsentManagementService::class);
    }

    public function test_can_request_and_grant_consent()
    {
        $patient = Patient::create(['health_id' => 'OC-MVP-1234', 'first_name' => 'John', 'last_name' => 'Doe', 'identity_status' => 'provisional']);
        $facility = Facility::create(['name' => 'General Hospital', 'type' => 'hospital']);

        $requestData = [
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'purpose' => 'consultation',
            'scope' => ['recent_clinical_summary', 'allergies'],
            'duration_minutes' => 120,
        ];

        // Request Consent
        $request = $this->service->requestConsent($requestData);
        $this->assertEquals('pending_patient_approval', $request->status);
        $this->assertDatabaseHas('audit_events', ['resource_type' => 'consent_request', 'resource_id' => $request->id]);

        // Grant Consent
        $grant = $this->service->grantConsent($request->id, 'patient');
        $this->assertEquals('active', $grant->status);
        $this->assertEquals('patient', $grant->authorizing_actor);
        $this->assertNotNull($grant->expires_at);

        // Ensure request status was updated
        $this->assertDatabaseHas('consent_requests', ['id' => $request->id, 'status' => 'approved']);
        $this->assertDatabaseHas('audit_events', ['resource_type' => 'consent_grant', 'action_type' => 'approve']);
    }

    public function test_can_revoke_active_consent()
    {
        $patient = Patient::create(['health_id' => 'OC-MVP-1235', 'first_name' => 'Jane', 'last_name' => 'Doe', 'identity_status' => 'provisional']);
        $facility = Facility::create(['name' => 'Clinic B', 'type' => 'clinic']);
        $user = User::create(['name' => 'Dr. Smith', 'email' => 'dr@test.com', 'password' => 'password']);

        $request = $this->service->requestConsent([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'purpose' => 'consultation',
            'scope' => ['allergies'],
        ]);

        $grant = $this->service->grantConsent($request->id, 'patient');

        // Revoke Consent
        $revocation = $this->service->revokeConsent($grant->id, 'Patient requested revocation', $user->id);

        $this->assertDatabaseHas('consent_grants', ['id' => $grant->id, 'status' => 'revoked']);
        $this->assertDatabaseHas('consent_revocations', ['consent_grant_id' => $grant->id, 'reason' => 'Patient requested revocation']);
        $this->assertDatabaseHas('audit_events', ['action_type' => 'revoke', 'resource_id' => $grant->id]);
    }

    public function test_cannot_grant_already_approved_request()
    {
        $patient = Patient::create(['health_id' => 'OC-MVP-1236', 'first_name' => 'Test', 'last_name' => 'User']);
        $facility = Facility::create(['name' => 'Clinic C', 'type' => 'clinic']);

        $request = $this->service->requestConsent([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'purpose' => 'consultation',
            'scope' => ['allergies'],
        ]);

        $this->service->grantConsent($request->id, 'patient');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Consent request is not in a pending state.");

        // Try to grant again
        $this->service->grantConsent($request->id, 'patient');
    }
}
