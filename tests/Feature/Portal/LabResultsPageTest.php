<?php
namespace Tests\Feature\Portal;

use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabResultsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_labs_page_requires_auth(): void
    {
        $this->get(route('portals.patient.labs'))->assertRedirect();
    }

    public function test_labs_page_shows_real_results_for_linked_patient(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);
        LabResult::factory()->count(3)->create(['patient_id' => $patient->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.labs'))
            ->assertStatus(200)
            ->assertViewHas('labs', fn($l) => $l->count() === 3);
    }

    public function test_labs_page_shows_empty_state_for_unlinked_user(): void
    {
        $user = User::factory()->create(['patient_id' => null]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.labs'))
            ->assertStatus(200)
            ->assertViewHas('patient', null);
    }

    public function test_labs_page_does_not_show_other_patients_results(): void
    {
        $patientA = Patient::factory()->create(['is_demo' => false]);
        $patientB = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patientA->id]);
        LabResult::factory()->count(2)->create(['patient_id' => $patientB->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.labs'))
            ->assertStatus(200)
            ->assertViewHas('labs', fn($l) => $l->count() === 0);
    }
}
