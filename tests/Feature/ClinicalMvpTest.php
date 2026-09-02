<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Facility;
use App\Models\User;
use App\Modules\EncounterManagement\Services\VisitManagementService;
use App\Modules\Triage\Services\TriageService;
use App\Modules\EncounterManagement\Services\ConsultationService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalMvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_visit_and_audit()
    {
        $visitService = $this->app->make(VisitManagementService::class);
        $patient = Patient::create(['health_id' => 'OC-MVP-001', 'first_name' => 'John', 'last_name' => 'Doe']);
        $facility = Facility::create(['name' => 'General Hospital', 'type' => 'hospital']);

        $visit = $visitService->createVisit([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'visit_type' => 'outpatient',
        ]);

        $this->assertNotNull($visit->id);
        $this->assertEquals('open', $visit->status);
        $this->assertDatabaseHas('audit_events', ['resource_type' => 'visit', 'action_type' => 'create', 'resource_id' => $visit->id]);
    }

    public function test_triage_validates_vital_signs_and_triggers_alert()
    {
        $triageService = $this->app->make(TriageService::class);
        $visitService = $this->app->make(VisitManagementService::class);

        $patient = Patient::create(['health_id' => 'OC-MVP-002', 'first_name' => 'Jane', 'last_name' => 'Doe']);
        $facility = Facility::create(['name' => 'Clinic A', 'type' => 'clinic']);
        $visit = $visitService->createVisit(['patient_id' => $patient->id, 'facility_id' => $facility->id, 'visit_type' => 'emergency']);

        // Test abnormal temperature throws exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Temperature value is out of logical human bounds (Celsius).");

        $triageService->recordTriage([
            'visit_id' => $visit->id,
            'vitals' => [
                'temperature' => 10.0 // logically invalid
            ]
        ]);
    }

    public function test_consultation_amendment_preserves_original()
    {
        $consultService = $this->app->make(ConsultationService::class);
        $visitService = $this->app->make(VisitManagementService::class);

        $patient = Patient::create(['health_id' => 'OC-MVP-003', 'first_name' => 'Jim', 'last_name' => 'Doe']);
        $facility = Facility::create(['name' => 'Clinic B', 'type' => 'clinic']);
        $provider = User::create(['name' => 'Dr. Jones', 'email' => 'drjones@test.com', 'password' => 'password']);
        $visit = $visitService->createVisit(['patient_id' => $patient->id, 'facility_id' => $facility->id, 'visit_type' => 'outpatient']);

        // Create signed note
        $note = $consultService->saveClinicalNote([
            'visit_id' => $visit->id,
            'provider_id' => $provider->id,
            'history_of_present_illness' => 'Patient has headache.',
            'status' => 'signed'
        ], $provider->id);

        $this->assertEquals('signed', $note->status);
        $this->assertNotNull($note->signed_at);

        // Amend the note
        $amended = $consultService->amendClinicalNote($note->id, [
            'history_of_present_illness' => 'Patient has headache and fever.',
            'amendment_reason' => 'Forgot to add fever.'
        ], $provider->id);

        // Original note status should change to amended
        $this->assertDatabaseHas('clinical_notes', ['id' => $note->id, 'status' => 'amended']);

        // New note should link back
        $this->assertEquals($note->id, $amended->amends_note_id);
        $this->assertEquals('Patient has headache and fever.', $amended->history_of_present_illness);
        $this->assertDatabaseHas('audit_events', ['action_type' => 'amend', 'resource_id' => $amended->id]);
    }
}
