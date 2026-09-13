<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pharmacy prescription queue's search box.
 *
 * It searched `patients.full_name` with a LIKE. That was wrong twice over,
 * and the two wrongs were hiding each other:
 *
 *   1. `patients` has first_name / middle_name / last_name and NO `full_name`,
 *      so every search was a 500 — SQLSTATE 42703, undefined column.
 *   2. Had the column existed, a LIKE across patient names is exactly the
 *      enumeration search this platform forbids. Type "a" at a pharmacy
 *      counter and you walk the patient register.
 *
 * The crash was the only thing preventing the disclosure. Fixing the column
 * name alone would have turned a broken feature into a working privacy
 * violation, so the search resolves an EXACT Health ID instead — the same
 * `Patient::resolveHealthId()` the dispensing counter in this very controller
 * already uses.
 */
class PharmacyPrescriptionSearchTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    private User $pharmacist;

    private Patient $ngozi;

    private Patient $ngomo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::create([
            'name'   => 'PharmaPlus Akwa',
            'type'   => 'pharmacy',
            'status' => 'active',
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'pharmacist'],
            ['description' => 'Pharmacist', 'dashboard_profile_key' => 'pharmacist'],
        );

        $this->pharmacist = User::create([
            'name'                => 'Aline Ngo',
            'email'               => 'aline.search@example.test',
            'password'            => bcrypt('secret-pass-1234'),
            'primary_facility_id' => $this->facility->id,
            'status'              => 'active',
        ]);
        $this->pharmacist->role_id = $role->id;
        $this->pharmacist->save();

        // Two patients whose names share a prefix. Under the old LIKE search a
        // pharmacist typing "Ng" would have been handed both.
        $this->ngozi = $this->patient('OC-TST-7300-0001-01', 'Ngozi', 'Seeker');
        $this->ngomo = $this->patient('OC-TST-7300-0002-02', 'Ngomo', 'Other');

        $this->prescriptionFor($this->ngozi);
        $this->prescriptionFor($this->ngomo);
    }

    private function patient(string $healthId, string $first, string $last): Patient
    {
        return Patient::create([
            'health_id'     => $healthId,
            'first_name'    => $first,
            'last_name'     => $last,
            'sex'           => 'female',
            'date_of_birth' => '1990-02-02',
            'is_demo'       => false,
        ]);
    }

    private function prescriptionFor(Patient $patient): Prescription
    {
        return Prescription::create([
            'patient_id'  => $patient->id,
            'facility_id' => $this->facility->id,
            'status'      => 'active',
        ]);
    }

    private function asPharmacist(): self
    {
        return $this->actingAs($this->pharmacist)
            ->withSession(['active_facility_id' => $this->facility->id]);
    }

    public function test_the_search_box_does_not_crash(): void
    {
        // The regression: every use of this box was a 500 on an undefined
        // column, so the feature had never worked at all.
        $this->asPharmacist()
            ->get('/pharmacy/prescriptions?search=' . urlencode('Ngozi'))
            ->assertOk();
    }

    public function test_a_patient_name_finds_nothing(): void
    {
        // The privacy property. A name is not a lookup key here — and a search
        // that finds nothing is the correct answer, not an error.
        $response = $this->asPharmacist()
            ->get('/pharmacy/prescriptions?search=' . urlencode('Ngozi'))
            ->assertOk();

        $this->assertCount(0, $response->viewData('prescriptions')->items(),
            'A patient name must never resolve to prescriptions.');
    }

    public function test_a_partial_health_id_finds_nothing(): void
    {
        // Exact match only: a prefix must not enumerate its way to a patient.
        $response = $this->asPharmacist()
            ->get('/pharmacy/prescriptions?search=' . urlencode('OC-TST-7300'))
            ->assertOk();

        $this->assertCount(0, $response->viewData('prescriptions')->items());
    }

    public function test_an_exact_health_id_finds_only_that_patient(): void
    {
        $response = $this->asPharmacist()
            ->get('/pharmacy/prescriptions?search=' . urlencode($this->ngozi->health_id))
            ->assertOk();

        $items = $response->viewData('prescriptions')->items();

        $this->assertCount(1, $items, 'An exact Health ID must find exactly its own patient.');
        $this->assertSame($this->ngozi->id, $items[0]->patient_id);
    }

    public function test_an_unmatched_search_returns_nothing_rather_than_everything(): void
    {
        // The dangerous failure mode: dropping the WHERE when the lookup finds
        // no patient would turn a failed search into "show me every
        // prescription in this facility".
        $response = $this->asPharmacist()
            ->get('/pharmacy/prescriptions?search=' . urlencode('OC-NOBODY-0000-0000-00'))
            ->assertOk();

        $this->assertCount(0, $response->viewData('prescriptions')->items());
    }

    public function test_no_search_still_lists_the_facilitys_prescriptions(): void
    {
        // The control: without a search the queue works, so the assertions
        // above are proving the filter and not an empty fixture.
        $response = $this->asPharmacist()
            ->get('/pharmacy/prescriptions')
            ->assertOk();

        $this->assertCount(2, $response->viewData('prescriptions')->items());
    }
}
