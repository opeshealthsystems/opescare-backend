<?php

namespace Tests\Feature\Chart;

use App\Models\AccessLog;
use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\PatientRecordedVital;
use App\Models\Role;
use App\Models\User;
use App\Services\Triage\TriageDeskService;
use App\Support\TriageAcuity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The web patient chart, and specifically the ways it is allowed to hurt
 * someone: opening a record with no basis, opening one without a trail,
 * scoring a child against adult bands, and passing a home reading off as an
 * observation.
 *
 * Access is App\Support\ClinicalRelationshipGate's decision; these tests
 * assert what the gate actually does at the web door, not a re-implementation.
 */
class PatientChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** A professional posted at one facility, exactly as the middleware leaves one. */
    private function staff(string $roleName = 'doctor'): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function actingAsStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    /**
     * A patient who has NOT asked for their full record to stay behind
     * consent. The switch defaults to ON, so most tests set it off explicitly
     * and the privacy test sets it on.
     */
    private function patient(?string $dob = '1985-04-02', bool $consentSwitch = false): Patient
    {
        return Patient::factory()->create([
            'date_of_birth'       => $dob,
            'privacy_preferences' => ['require_consent_for_full_record' => $consentSwitch],
        ]);
    }

    /** Basis 1: the patient is on this clinician's own schedule. */
    private function appointmentBasis(User $doctor, Facility $facility, Patient $patient): void
    {
        Appointment::create([
            'patient_id'   => $patient->id,
            'facility_id'  => $facility->id,
            'provider_id'  => $doctor->id,
            'appointment_type' => 'consultation',
            'status'       => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    /** Facility-measured observations, written the way the triage desk writes them. */
    private function triageVitals(User $nurse, Facility $facility, Patient $patient, array $vitals): void
    {
        /** @var TriageDeskService $triage */
        $triage = app(TriageDeskService::class);
        $visit  = $triage->openVisitFor($patient->id, $facility->id, $nurse->id);

        $triage->record([
            'visit'                => $visit,
            'nurse_id'             => (string) $nurse->id,
            'nurse_role'           => 'doctor',
            'presenting_complaint' => 'Test',
            'pain_score'           => null,
            'pregnancy_status'     => null,
            'acuity'               => TriageAcuity::P2,
            'vitals'               => $vitals,
        ]);
    }

    /** Deliberately alarming in an ADULT. */
    private function alarmingVitals(): array
    {
        return [
            'temperature'       => 39.4,
            'pulse_rate'        => 132,
            'respiratory_rate'  => 30,
            'systolic_bp'       => 86,
            'diastolic_bp'      => 54,
            'oxygen_saturation' => 89,
        ];
    }

    private function assertNoBandVerdictRendered(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertDontSee(__('chart.advisory_source'));
        $response->assertDontSee(__('chart.advisory_note'));

        foreach (['critical', 'high', 'low', 'abnormal', 'normal'] as $band) {
            $response->assertDontSee(__('labs.flag.' . $band));
        }
    }

    // ── Access ────────────────────────────────────────────────────────────

    public function test_a_doctor_with_a_basis_opens_the_chart_and_the_read_is_logged(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->appointmentBasis($doctor, $facility, $patient);

        $response = $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id);

        $response->assertOk()
            ->assertSee($patient->health_id)
            ->assertSee(__('chart.basis.appointment'))
            ->assertSee(__('chart.allergies_none'))
            // Age, never the date of birth.
            ->assertDontSee('1985-04-02');

        $this->assertDatabaseHas('access_logs', [
            'patient_id'    => $patient->id,
            'actor_id'      => $doctor->id,
            'facility_id'   => $facility->id,
            'purpose'       => 'treatment:appointment',
            'data_category' => 'patient_identity',
            'access_type'   => 'read',
        ]);
    }

    public function test_the_health_id_form_redirects_to_the_chart_by_uuid(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->appointmentBasis($doctor, $facility, $patient);

        $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients?health_id=' . strtolower($patient->health_id))
            ->assertRedirect('/facility/patients/' . $patient->id);
    }

    public function test_a_patient_with_no_basis_does_not_exist_and_the_refusal_is_logged_as_denied(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        // No appointment, no care team, no act, no episode here, no grant.

        $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id)
            ->assertNotFound();

        // What the gate's callers do on refusal: a `denied` row, never a `read`.
        $this->assertDatabaseHas('access_logs', [
            'patient_id'  => $patient->id,
            'actor_id'    => $doctor->id,
            'purpose'     => 'treatment:no_relationship',
            'access_type' => 'denied',
        ]);
        $this->assertSame(0, AccessLog::where('patient_id', $patient->id)->where('access_type', 'read')->count());

        // And the Health-ID form answers exactly as it would for a Health ID
        // nobody holds.
        $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients?health_id=' . $patient->health_id)
            ->assertOk()
            ->assertSee(__('chart.not_found'));
    }

    public function test_a_role_without_the_clinical_chart_capability_is_refused_with_403(): void
    {
        [$reception, $facility] = $this->staff('receptionist');
        $patient = $this->patient();
        $this->appointmentBasis($reception, $facility, $patient);

        $this->actingAsStaff($reception, $facility)
            ->get('/facility/patients')
            ->assertForbidden();

        $this->actingAsStaff($reception, $facility)
            ->get('/facility/patients/' . $patient->id)
            ->assertForbidden();

        $this->assertSame(0, AccessLog::where('patient_id', $patient->id)->count());
    }

    public function test_a_name_never_reaches_the_database_as_a_search(): void
    {
        [$doctor, $facility] = $this->staff();
        $this->patient();

        DB::enableQueryLog();

        $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients?health_id=' . urlencode('John Smith'))
            ->assertOk()
            ->assertSee(__('chart.not_found'));

        $patientQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $sql) => str_contains($sql, '"patients"') && str_contains($sql, 'select'));

        DB::disableQueryLog();

        $this->assertNotEmpty($patientQueries, 'the exact Health-ID lookup should have run');

        foreach ($patientQueries as $sql) {
            $this->assertStringNotContainsStringIgnoringCase(' like ', $sql);
            $this->assertStringNotContainsString('first_name', $sql);
            $this->assertStringNotContainsString('last_name', $sql);
            $this->assertStringContainsString('"health_id" = ', $sql);
        }
    }

    // ── Privacy ───────────────────────────────────────────────────────────

    public function test_a_patient_who_keeps_their_record_behind_consent_opens_the_preview_only(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(consentSwitch: true);
        $this->appointmentBasis($doctor, $facility, $patient);

        // The safety minimum still opens: identity, allergies, medicines.
        $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id)
            ->assertOk()
            ->assertSee(__('chart.scope_preview_title'))
            ->assertSee(__('chart.allergies_none'));

        // History does not, and the refusal is logged.
        $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id . '?tab=encounters')
            ->assertOk()
            ->assertSee(__('chart.consent_required_title'))
            ->assertDontSee(__('chart.encounters_none'));

        $this->assertDatabaseHas('access_logs', [
            'patient_id'    => $patient->id,
            'data_category' => 'encounters',
            'access_type'   => 'denied',
        ]);
        $this->assertSame(0, AccessLog::where('patient_id', $patient->id)->where('data_category', 'encounters')->where('access_type', 'read')->count());

        // A grant to this facility is the consent the switch asks for.
        ConsentGrant::create([
            'patient_id'        => $patient->id,
            'facility_id'       => $facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['full'],
            'status'            => 'active',
            'expires_at'        => now()->addMonth(),
        ]);

        $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id . '?tab=encounters')
            ->assertOk()
            ->assertDontSee(__('chart.consent_required_title'))
            ->assertSee(__('chart.encounters_none'));
    }

    // ── Vitals ────────────────────────────────────────────────────────────

    public function test_an_adult_does_get_advisory_flags(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient('1985-04-02');
        $this->appointmentBasis($doctor, $facility, $patient);
        $this->triageVitals($doctor, $facility, $patient, $this->alarmingVitals());

        $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id . '?tab=vitals')
            ->assertOk()
            ->assertSee(__('chart.advisory_source'))
            ->assertSee(__('patient_writes.provenance.facility_measured.label'))
            ->assertSee(__('labs.flag.critical'));
    }

    public function test_a_child_gets_the_recorded_numbers_and_no_interpretation(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(now()->subYears(8)->toDateString());
        $this->appointmentBasis($doctor, $facility, $patient);
        $this->triageVitals($doctor, $facility, $patient, $this->alarmingVitals());

        $response = $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id . '?tab=vitals')
            ->assertOk()
            ->assertSee(__('chart.no_bands_title'))
            ->assertSee(__('chart.no_bands.paediatric'))
            // The numbers themselves are shown.
            ->assertSee('132');

        $this->assertNoBandVerdictRendered($response);
    }

    public function test_an_unknown_date_of_birth_gets_no_interpretation(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(null);
        $this->appointmentBasis($doctor, $facility, $patient);
        $this->triageVitals($doctor, $facility, $patient, $this->alarmingVitals());

        $response = $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id . '?tab=vitals')
            ->assertOk()
            ->assertSee(__('chart.no_bands.age_unknown'))
            ->assertSee(__('chart.age_unknown'));

        $this->assertNoBandVerdictRendered($response);
    }

    public function test_self_reported_readings_carry_the_self_reported_badge_and_are_never_flagged(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient('1985-04-02');
        $this->appointmentBasis($doctor, $facility, $patient);

        PatientRecordedVital::create([
            'patient_id'      => $patient->id,
            'measure'         => 'blood_pressure',
            'value_primary'   => 178,
            'value_secondary' => 121,
            'unit'            => 'mmHg',
            'measured_at'     => now()->subDay(),
        ]);

        $response = $this->actingAsStaff($doctor, $facility)
            ->get('/facility/patients/' . $patient->id . '?tab=vitals')
            ->assertOk()
            ->assertSee('178')
            ->assertSee(__('patient_writes.provenance.self_reported.label'))
            ->assertSee(__('chart.facility_vitals_none'));

        // An adult with NO facility observations: a 178/121 home reading must
        // not produce a flag of any kind.
        foreach (['critical', 'high', 'low', 'abnormal'] as $band) {
            $response->assertDontSee(__('labs.flag.' . $band));
        }
    }
}
