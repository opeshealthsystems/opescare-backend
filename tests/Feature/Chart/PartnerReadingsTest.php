<?php

namespace Tests\Feature\Chart;

use App\Models\AccessLog;
use App\Models\Appointment;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\IntegrationClient;
use App\Models\PartnerImmunization;
use App\Models\PartnerObservation;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Chart\PatientChartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Partner-reported readings (fhir_partner_*): shown to the clinician and the
 * patient, labelled with sending system + facility, and NEVER merged into the
 * facility's own vitals.
 */
class PartnerReadingsTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(): array
    {
        $role     = Role::where('name', 'doctor')->firstOrFail();
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

    private function patient(bool $consentSwitch = false): Patient
    {
        return Patient::factory()->create([
            'date_of_birth'       => '1985-04-02',
            'privacy_preferences' => ['require_consent_for_full_record' => $consentSwitch],
            'is_demo'             => false,
        ]);
    }

    private function appointmentBasis(User $doctor, Facility $facility, Patient $patient): void
    {
        Appointment::create([
            'patient_id'       => $patient->id,
            'facility_id'      => $facility->id,
            'provider_id'      => $doctor->id,
            'appointment_type' => 'consultation',
            'status'           => 'scheduled',
            'scheduled_at'     => now()->addDay(),
        ]);
    }

    /** A partner facility and the observation it pushed, written as ingest writes it. */
    private function partnerObservation(Patient $patient, string $system = 'OPES Triage', float $value = 187.0): PartnerObservation
    {
        $sender = Facility::factory()->create(['name' => 'Hôpital Partenaire Bonanjo', 'type' => 'hospital']);
        $clientId = 'cli_' . Str::random(10);

        $o = new PartnerObservation([
            'patient_id'             => $patient->id,
            'facility_id'            => $sender->id,
            'integration_client_id'  => $clientId,
            'observation_kind'       => PartnerObservation::KIND_VITAL_SIGNS,
            'loinc_code'             => '8867-4',
            'loinc_display'          => 'Heart rate',
            'value_quantity'         => $value,
            'value_unit'             => '/min',
            'partner_interpretation' => 'H',
            'reference_range'        => '60-100 /min',
            'effective_at'           => now()->subHours(2),
            'status'                 => 'final',
            'fhir_source'            => ['resourceType' => 'Observation'],
        ]);
        $o->source_system = $system;
        $o->save();

        return $o;
    }

    private function show(User $doctor, Facility $facility, Patient $patient, string $tab)
    {
        return $this->actingAs($doctor)->withSession(['active_facility_id' => $facility->id])
            ->get('/facility/patients/' . $patient->id . '?tab=' . $tab);
    }

    // ── Clinician chart ───────────────────────────────────────────────────

    public function test_a_clinician_with_a_basis_sees_partner_readings_labelled_and_the_read_is_logged(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->appointmentBasis($doctor, $facility, $patient);
        $this->partnerObservation($patient);

        $this->show($doctor, $facility, $patient, 'partner_reported')
            ->assertOk()
            ->assertSee(__('partner_readings.title'))
            ->assertSee(__('partner_readings.provenance_label'))
            ->assertSee(__('partner_readings.badge_system', ['name' => 'OPES Triage']), false)
            ->assertSee('Hôpital Partenaire Bonanjo')
            ->assertSee('187');

        $this->assertDatabaseHas('access_logs', [
            'patient_id'    => $patient->id,
            'actor_id'      => $doctor->id,
            'data_category' => 'partner_reported',
            'access_type'   => 'read',
        ]);
    }

    public function test_the_summary_shows_a_separate_partner_card_and_logs_the_category(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->appointmentBasis($doctor, $facility, $patient);
        $this->partnerObservation($patient);

        $this->show($doctor, $facility, $patient, 'summary')
            ->assertOk()
            ->assertSee('cs-partner-title', false)
            ->assertSee('OPES Triage');

        $this->assertDatabaseHas('access_logs', [
            'patient_id'    => $patient->id,
            'data_category' => 'partner_reported',
            'access_type'   => 'read',
        ]);
    }

    public function test_a_clinician_without_a_basis_gets_404_and_sees_nothing(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->partnerObservation($patient);

        $this->show($doctor, $facility, $patient, 'partner_reported')
            ->assertNotFound()
            ->assertDontSee('OPES Triage');

        $this->assertSame(0, AccessLog::where('patient_id', $patient->id)->where('access_type', 'read')->count());
        $this->assertDatabaseHas('access_logs', [
            'patient_id'    => $patient->id,
            'data_category' => 'partner_reported',
            'access_type'   => 'denied',
        ]);
    }

    public function test_without_consent_a_patient_behind_the_switch_shows_no_partner_readings(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(consentSwitch: true);
        $this->appointmentBasis($doctor, $facility, $patient);
        $this->partnerObservation($patient);

        $this->show($doctor, $facility, $patient, 'partner_reported')
            ->assertOk()
            ->assertSee(__('chart.consent_required_title'))
            ->assertDontSee('OPES Triage');

        $this->show($doctor, $facility, $patient, 'summary')
            ->assertOk()
            ->assertDontSee('cs-partner-title', false);

        $this->assertSame(0, AccessLog::where('patient_id', $patient->id)
            ->where('data_category', 'partner_reported')->where('access_type', 'read')->count());
    }

    public function test_facility_vitals_never_include_partner_rows(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient();
        $this->appointmentBasis($doctor, $facility, $patient);
        $this->partnerObservation($patient, value: 187);

        $vitals = app(PatientChartService::class)->facilityVitals($patient);
        $this->assertCount(0, $vitals['rows']);

        $this->show($doctor, $facility, $patient, 'vitals')
            ->assertOk()
            ->assertSee(__('chart.facility_vitals_none'))
            ->assertDontSee('OPES Triage');
    }

    // ── Patient (mobile) ──────────────────────────────────────────────────

    public function test_the_patient_api_returns_own_labelled_partner_readings_only(): void
    {
        $mine  = $this->patient();
        $other = $this->patient();
        $this->partnerObservation($mine, 'OPES Triage', 120);
        $this->partnerObservation($other, 'Somebody Else EMR', 99);

        $imm = new PartnerImmunization([
            'patient_id'            => $mine->id,
            'facility_id'           => Facility::factory()->create(['name' => 'CSI Akwa'])->id,
            'integration_client_id' => 'cli_named',
            'cvx_code'              => '08',
            'vaccine_display'       => 'Hep B, adolescent or pediatric',
            'status'                => 'completed',
            'occurrence_at'         => now()->subDays(3),
            'fhir_source'           => ['resourceType' => 'Immunization'],
        ]);
        // Stored as the client id: the service falls back to the client's name.
        $imm->source_system = 'cli_named';
        $imm->save();
        IntegrationClient::factory()->create(['client_id' => 'cli_named', 'name' => 'District EPI App']);

        $this->getJson('/api/mobile/records/partner-readings')->assertStatus(401);

        $response = $this->mobileGetJson($mine, '/api/mobile/records/partner-readings')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.provenance', 'partner_reported')
            ->assertJsonPath('data.0.type', 'observation')
            ->assertJsonPath('data.0.sending_system.name', 'OPES Triage')
            ->assertJsonPath('data.0.sending_facility.name', 'Hôpital Partenaire Bonanjo')
            ->assertJsonPath('data.0.provenance', 'partner_reported')
            ->assertJsonPath('data.0.provenance_label', __('partner_readings.provenance_label'))
            ->assertJsonPath('data.0.code', '8867-4')
            ->assertJsonPath('data.1.type', 'immunization')
            ->assertJsonPath('data.1.sending_system.name', 'District EPI App')
            ->assertJsonPath('data.1.sending_facility.name', 'CSI Akwa');

        $this->assertStringNotContainsString('Somebody Else EMR', $response->getContent());

        $this->mobileGetJson($mine, '/api/mobile/records/partner-readings?per_page=500')->assertStatus(422);
    }

    public function test_the_fhir_export_carries_partner_provenance(): void
    {
        $patient = $this->patient();
        $this->partnerObservation($patient);

        $bundle = $this->mobilePostJson($patient, '/api/mobile/medical-records/export/fhir')->assertOk()->json();

        $obs = collect($bundle['entry'])->pluck('resource')->firstWhere('resourceType', 'Observation');
        $this->assertNotNull($obs);
        $this->assertSame('urn:opescare:partner:OPES Triage', $obs['meta']['source']);
        $this->assertSame('partner_reported', $obs['meta']['security'][0]['code']);
        $this->assertSame('Hôpital Partenaire Bonanjo', $obs['meta']['extension'][0]['valueString']);
    }
}
