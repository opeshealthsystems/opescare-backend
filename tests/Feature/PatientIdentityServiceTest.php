<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\AuditEvent;
use App\Modules\PatientIdentity\Services\PatientIdentityService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PatientIdentityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(PatientIdentityService::class);
    }

    public function test_can_create_patient_and_audit_event()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'sex' => 'male',
            'phone_number' => '+1234567890',
        ];

        $patient = $this->service->createPatientCandidate($data);

        $this->assertNotNull($patient->id);
        $this->assertNotNull($patient->health_id);
        $this->assertEquals('John', $patient->first_name);
        $this->assertEquals('provisional', $patient->identity_status instanceof \BackedEnum ? $patient->identity_status->value : $patient->identity_status);

        $this->assertDatabaseHas('audit_events', [
            'action_type' => 'create',
            'resource_type' => 'patient',
            'resource_id' => $patient->id,
        ]);
    }

    public function test_prevents_duplicate_patient_creation()
    {
        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'date_of_birth' => '1992-02-02',
            'sex' => 'female',
            'phone_number' => '+0987654321',
        ];

        $this->service->createPatientCandidate($data);

        $duplicateData = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'date_of_birth' => '1992-02-02 00:00:00',
            'sex' => 'female',
            'phone_number' => '+0987654321',
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Duplicate candidate found. Please review existing patients before creating.");

        $this->service->createPatientCandidate($duplicateData);
    }
}
