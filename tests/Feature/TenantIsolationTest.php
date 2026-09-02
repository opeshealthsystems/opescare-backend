<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_facility_a_cannot_retrieve_facility_b_appointments(): void
    {
        $facilityA = Facility::factory()->create();
        $facilityB = Facility::factory()->create();

        $patientA = Patient::factory()->create(['facility_id' => $facilityA->id]);
        $patientB = Patient::factory()->create(['facility_id' => $facilityB->id]);

        $appointmentA = Appointment::factory()->create([
            'facility_id' => $facilityA->id,
            'patient_id'  => $patientA->id,
        ]);
        $appointmentB = Appointment::factory()->create([
            'facility_id' => $facilityB->id,
            'patient_id'  => $patientB->id,
        ]);

        // Simulate request scoped to Facility A.
        // HasFacilityScope is deliberately opt-in (no global scope), so tenant
        // isolation is exercised through the forCurrentFacility() scope.
        app()->instance('current_facility_id', $facilityA->id);

        $results = Appointment::forCurrentFacility()->get();

        $ids = $results->pluck('id')->toArray();

        $this->assertContains($appointmentA->id, $ids, 'Facility A record should be visible');
        $this->assertNotContains($appointmentB->id, $ids, 'Facility B record must NOT be visible from Facility A scope');
    }

    public function test_facility_scope_applies_to_all_scoped_models(): void
    {
        // Smoke-test: all scoped models respond to facility() scope without error
        $facility = Facility::factory()->create();
        app()->instance('current_facility_id', $facility->id);

        // Only models whose tables actually carry a facility_id column can be
        // facility-scoped (clinical_notes / vital_signs are scoped through
        // their parent visit / triage_record and have no facility_id column).
        $models = [
            \App\Models\Appointment::class,
            \App\Models\AppointmentSlot::class,
            \App\Models\LabOrder::class,
            \App\Models\Prescription::class,
            \App\Models\InsuranceClaim::class,
        ];

        foreach ($models as $model) {
            $this->assertContains(
                \App\Traits\HasFacilityScope::class,
                class_uses_recursive($model),
                "Model {$model} must use the HasFacilityScope trait"
            );

            $query = $model::query()->forCurrentFacility();

            $this->assertStringContainsString(
                'facility_id',
                $query->toSql(),
                "Model {$model} forCurrentFacility() must constrain by facility_id"
            );
            $this->assertContains(
                $facility->id,
                $query->getBindings(),
                "Model {$model} forCurrentFacility() must bind the current facility id"
            );

            // The scoped query must execute without error.
            $this->assertIsObject(
                $query->get(),
                "Model {$model} should support HasFacilityScope without error"
            );
        }
    }
}
