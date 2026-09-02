<?php
namespace Tests\Feature\Portal;

use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrescriptionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_prescriptions_page_requires_auth(): void
    {
        $this->get(route('portals.patient.prescriptions'))->assertRedirect();
    }

    public function test_prescriptions_page_shows_patient_prescriptions(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);
        Prescription::factory()->count(2)->create([
            'patient_id' => $patient->id,
            'prescribed_by' => (string) Str::uuid(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.prescriptions'))
            ->assertStatus(200)
            ->assertViewHas('prescriptions', fn($p) => $p->count() === 2);
    }

    public function test_prescriptions_page_shows_empty_state_for_unlinked_user(): void
    {
        $user = User::factory()->create(['patient_id' => null]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.prescriptions'))
            ->assertStatus(200)
            ->assertViewHas('patient', null);
    }

    public function test_prescriptions_page_does_not_show_other_patients_prescriptions(): void
    {
        $patientA = Patient::factory()->create(['is_demo' => false]);
        $patientB = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patientA->id]);
        Prescription::factory()->count(3)->create([
            'patient_id' => $patientB->id,
            'prescribed_by' => (string) Str::uuid(),
        ]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.prescriptions'))
            ->assertStatus(200)
            ->assertViewHas('prescriptions', fn($p) => $p->count() === 0);
    }
}
