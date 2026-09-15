<?php

namespace Tests\Feature\Imports;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use App\Modules\ClinicalDecisionSupport\Services\DrugInteractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Group C import: the platform DrugInteractionService on the prescriber desk.
 * A warning the prescriber acknowledges — never a block.
 */
class DrugInteractionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Facility $facility;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);

        $role = Role::where('name', 'doctor')->firstOrFail();
        $this->facility = Facility::factory()->create(['type' => 'hospital']);
        $this->doctor = User::factory()->create([
            'role_id' => $role->id, 'primary_facility_id' => $this->facility->id, 'status' => 'active',
        ]);
        FacilityRoleAssignment::create([
            'user_id' => $this->doctor->id, 'facility_id' => $this->facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        $this->patient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        // A live episode at this facility = standing to prescribe.
        Appointment::factory()->create([
            'patient_id' => $this->patient->id, 'facility_id' => $this->facility->id,
            'provider_id' => null, 'scheduled_at' => now()->addDay(),
        ]);
    }

    private function asDoctor(): static
    {
        return $this->actingAs($this->doctor)->withSession(['active_facility_id' => $this->facility->id]);
    }

    public function test_the_service_matches_catalogue_names_and_ignores_safe_pairs(): void
    {
        $svc = app(DrugInteractionService::class);

        $this->assertCount(1, $svc->checkInteractions([['name' => 'Warfarin 5 mg'], ['name' => 'Aspirin 75mg']]));
        $this->assertSame([], $svc->checkInteractions([['name' => 'Paracetamol 500 mg'], ['name' => 'Amoxicillin 500 mg']]));
    }

    public function test_an_interacting_pair_shows_a_warning_on_the_check_endpoint(): void
    {
        $this->asDoctor()
            ->postJson(route('portals.prescriber.interaction_check'), [
                'patient_id' => $this->patient->id,
                'drugs'      => ['Warfarin 5 mg', 'Aspirin 75 mg'],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.severity', 'major');
    }

    public function test_a_safe_pair_gives_no_warning(): void
    {
        $this->asDoctor()
            ->postJson(route('portals.prescriber.interaction_check'), [
                'patient_id' => $this->patient->id,
                'drugs'      => ['Paracetamol 500 mg', 'Amoxicillin 500 mg'],
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_new_medicine_is_checked_against_the_patients_active_prescriptions(): void
    {
        $this->asDoctor()->post(route('portals.prescriber.store'), [
            'patient_id' => $this->patient->id,
            'items'      => [['drug_name' => 'Simvastatin 20 mg']],
        ])->assertRedirect();

        $this->asDoctor()
            ->postJson(route('portals.prescriber.interaction_check'), [
                'patient_id' => $this->patient->id,
                'drugs'      => ['Clarithromycin 500 mg'],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_the_prescription_still_saves_and_the_acknowledgement_is_audited(): void
    {
        $response = $this->asDoctor()->post(route('portals.prescriber.store'), [
            'patient_id' => $this->patient->id,
            'interactions_acknowledged' => '1',
            'items' => [
                ['drug_name' => 'Warfarin 5 mg'],
                ['drug_name' => 'Aspirin 75 mg'],
            ],
        ]);

        $prescription = Prescription::where('patient_id', $this->patient->id)->firstOrFail();
        $response->assertRedirect(route('portals.prescriber.show', $prescription->id))
            ->assertSessionHas('warning');

        $audit = AuditEvent::where('action_type', 'prescription_interaction_warning')
            ->where('resource_id', $prescription->id)
            ->firstOrFail();

        $this->assertSame('acknowledged_by_prescriber', $audit->reason);
        $this->assertTrue($audit->after_state['acknowledged']);
        $this->assertSame('major', $audit->after_state['interactions'][0]['severity']);
    }

    public function test_a_safe_prescription_writes_no_interaction_audit(): void
    {
        $this->asDoctor()->post(route('portals.prescriber.store'), [
            'patient_id' => $this->patient->id,
            'items' => [['drug_name' => 'Paracetamol 500 mg'], ['drug_name' => 'Amoxicillin 500 mg']],
        ])->assertSessionMissing('warning');

        $this->assertSame(1, Prescription::where('patient_id', $this->patient->id)->count());
        $this->assertFalse(AuditEvent::where('action_type', 'prescription_interaction_warning')->exists());
    }

    public function test_the_create_form_carries_the_warning_panel(): void
    {
        $this->asDoctor()
            ->get(route('portals.prescriber.create', ['patient_id' => $this->patient->id]))
            ->assertOk()
            ->assertSee('rx-interactions', false)
            ->assertSee(__('cdss.acknowledge'));
    }
}
