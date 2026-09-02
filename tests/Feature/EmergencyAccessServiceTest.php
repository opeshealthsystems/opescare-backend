<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Facility;
use App\Models\User;
use App\Modules\AccessControl\Services\EmergencyAccessService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmergencyAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmergencyAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(EmergencyAccessService::class);
    }

    public function test_can_log_emergency_access()
    {
        $patient = Patient::create(['health_id' => 'OC-MVP-EMER', 'first_name' => 'John', 'last_name' => 'Doe']);
        $facility = Facility::create(['name' => 'ER Hospital', 'type' => 'hospital']);
        $provider = User::create(['name' => 'Dr. ER', 'email' => 'er@test.com', 'password' => 'password']);

        $data = [
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'reason' => 'Patient is unconscious, suspected allergic reaction.',
        ];

        $event = $this->service->logEmergencyAccess($data);

        $this->assertNotNull($event->id);

        // Ensure a review case is automatically generated
        $this->assertDatabaseHas('emergency_review_cases', [
            'emergency_access_event_id' => $event->id,
            'status' => 'pending',
        ]);

        // Ensure the audit log flags emergency_override = true
        $this->assertDatabaseHas('audit_events', [
            'action_type' => 'read',
            'resource_type' => 'emergency_profile',
            'emergency_override' => true,
        ]);
    }

    public function test_fails_if_reason_is_missing()
    {
        $patient = Patient::create(['health_id' => 'OC-MVP-EMER2', 'first_name' => 'Jane', 'last_name' => 'Doe']);
        $facility = Facility::create(['name' => 'ER Hospital 2', 'type' => 'hospital']);
        $provider = User::create(['name' => 'Dr. ER 2', 'email' => 'er2@test.com', 'password' => 'password']);

        $data = [
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'reason' => '', // Empty reason should fail
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("A reason is mandatory for emergency break-glass access.");

        $this->service->logEmergencyAccess($data);
    }
}
