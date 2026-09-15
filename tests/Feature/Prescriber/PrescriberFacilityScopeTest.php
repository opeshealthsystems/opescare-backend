<?php

namespace Tests\Feature\Prescriber;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use App\Services\Clinical\PrescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The clinic prescriber desk (docs/audits/CLINIC_DASHBOARD_AUDIT_2.md).
 *
 *  C-1  The desk used to fall back to Facility::value('id') — the FIRST
 *       facility in the table — for an account with no posting, listing and
 *       writing prescriptions for a facility the doctor does not work at.
 *  C-2  The RX code the pharmacy counter looks a script up by was printed on
 *       paper only; neither the desk nor the patient's own portal showed it.
 */
class PrescriberFacilityScopeTest extends TestCase
{
    use RefreshDatabase;

    private Facility $hospital;
    private Facility $clinic;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        // Created FIRST on purpose: this is the row the old fallback picked.
        $this->hospital = Facility::create(['name' => 'Hopital Test', 'type' => 'hospital', 'status' => 'active']);
        $this->clinic   = Facility::create(['name' => 'Clinique Test', 'type' => 'clinic', 'status' => 'active']);

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-7700-0001-01',
            'first_name'    => 'Brice',
            'last_name'     => 'Kamga',
            'sex'           => 'male',
            'date_of_birth' => '1988-02-03',
            'facility_id'   => $this->clinic->id,
            'is_demo'       => false,
        ]);
    }

    private function user(string $role, ?string $facilityId, string $email, ?string $patientId = null): User
    {
        $r = Role::firstOrCreate(['name' => $role], ['description' => $role, 'dashboard_profile_key' => $role]);

        $u = User::create([
            'name'                => $email,
            'email'               => $email,
            'password'            => bcrypt('secret-pass-1234'),
            'primary_facility_id' => $facilityId,
            'patient_id'          => $patientId,
            'status'              => 'active',
        ]);
        $u->role_id = $r->id;
        $u->save();

        return $u;
    }

    private function rxAt(Facility $facility): Prescription
    {
        return app(PrescriptionService::class)->issue([
            'patient_id'  => $this->patient->id,
            'facility_id' => $facility->id,
            'items'       => [['drug_name' => 'Paracetamol 500mg', 'quantity' => 10]],
        ]);
    }

    public function test_a_doctor_with_no_posting_is_refused_not_scoped_to_the_first_facility(): void
    {
        $this->rxAt($this->hospital);
        $doctor = $this->user('doctor', null, 'nopost.doctor@example.test');

        $this->actingAs($doctor)
            ->get(route('portals.prescriber.index'))
            ->assertForbidden();
    }

    public function test_a_clinic_doctor_never_sees_another_facilitys_prescriptions(): void
    {
        $foreign = $this->rxAt($this->hospital);
        $own     = $this->rxAt($this->clinic);
        $doctor  = $this->user('doctor', $this->clinic->id, 'clinic.doctor@example.test');

        $this->actingAs($doctor)
            ->get(route('portals.prescriber.index'))
            ->assertOk()
            ->assertSee($own->reference)
            ->assertDontSee($foreign->reference);

        $this->actingAs($doctor)->get(route('portals.prescriber.show', $foreign->id))->assertNotFound();
    }

    public function test_the_rx_code_is_on_the_prescriber_page_and_in_the_patient_portal(): void
    {
        $rx = $this->rxAt($this->clinic);
        $this->assertNotEmpty($rx->reference);

        $doctor = $this->user('doctor', $this->clinic->id, 'clinic.doctor2@example.test');
        $this->actingAs($doctor)
            ->get(route('portals.prescriber.show', $rx->id))
            ->assertOk()
            ->assertSee(__('prescriber.rx_code'))
            ->assertSee($rx->reference);

        $patientUser = $this->user('patient', null, 'brice.patient@example.test', $this->patient->id);
        $this->actingAs($patientUser)
            ->get(route('portals.patient.prescriptions'))
            ->assertOk()
            ->assertSee(__('prescriber.rx_code'))
            ->assertSee($rx->reference);
    }
}
