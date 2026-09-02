<?php
namespace Tests\Feature\Portal;

use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_page_requires_auth(): void
    {
        $this->get(route('portals.patient.documents'))->assertRedirect();
    }

    public function test_documents_page_shows_patient_documents(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);
        OfficialDocument::factory()->count(3)->create(['patient_id' => $patient->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.documents'))
            ->assertStatus(200)
            ->assertViewHas('documents', fn($d) => $d->count() === 3);
    }

    public function test_documents_page_does_not_expose_sensitive_fields(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);
        OfficialDocument::factory()->create([
            'patient_id' => $patient->id,
            'pdf_path'   => '/secure/docs/sensitive.pdf',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.documents'));

        $response->assertStatus(200);
        // The view variable must not contain pdf_path
        $documents = $response->viewData('documents');
        $this->assertNull($documents->first()?->pdf_path ?? null);
    }

    public function test_documents_page_does_not_show_other_patients_documents(): void
    {
        $patientA = Patient::factory()->create(['is_demo' => false]);
        $patientB = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patientA->id]);
        OfficialDocument::factory()->count(2)->create(['patient_id' => $patientB->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.documents'))
            ->assertStatus(200)
            ->assertViewHas('documents', fn($d) => $d->count() === 0);
    }
}
