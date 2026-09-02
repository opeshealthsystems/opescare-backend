<?php
namespace Tests\Feature\Portal;

use App\Models\ConsentRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_consent_page_requires_auth(): void
    {
        $this->get(route('portals.patient.consent'))->assertRedirect();
    }

    public function test_consent_page_shows_pending_requests(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);
        ConsentRequest::factory()->count(2)->create(['patient_id' => $patient->id, 'status' => 'pending']);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->get(route('portals.patient.consent'))
            ->assertStatus(200)
            ->assertViewHas('consentRequests', fn($r) => $r->count() === 2);
    }

    public function test_patient_can_approve_consent_request(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);
        $req = ConsentRequest::factory()->create(['patient_id' => $patient->id, 'status' => 'pending']);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.consent.approve', $req->id))
            ->assertRedirect(route('portals.patient.consent'));

        $this->assertEquals('approved', $req->fresh()->status);
    }

    public function test_patient_can_deny_consent_request(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);
        $req = ConsentRequest::factory()->create(['patient_id' => $patient->id, 'status' => 'pending']);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.consent.deny', $req->id))
            ->assertRedirect(route('portals.patient.consent'));

        $this->assertEquals('denied', $req->fresh()->status);
    }

    public function test_patient_cannot_act_on_other_patients_consent(): void
    {
        $patientA = Patient::factory()->create(['is_demo' => false]);
        $patientB = Patient::factory()->create(['is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patientA->id]);
        $req = ConsentRequest::factory()->create(['patient_id' => $patientB->id, 'status' => 'pending']);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.consent.approve', $req->id))
            ->assertStatus(404);
    }

    public function test_patient_cannot_deny_other_patients_consent(): void
    {
        $otherPatient = \App\Models\Patient::factory()->create();
        $consentReq = \App\Models\ConsentRequest::factory()->create([
            'patient_id' => $otherPatient->id,
            'status'     => 'pending',
        ]);

        $myPatient = \App\Models\Patient::factory()->create();
        $user = \App\Models\User::factory()->create(['patient_id' => $myPatient->id]);

        $this->actingAs($user)
            ->withSession(['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'])
            ->post(route('portals.patient.consent.deny', $consentReq->id))
            ->assertStatus(404);
    }
}
