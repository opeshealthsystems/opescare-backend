<?php

namespace Tests\Feature\Chart;

use App\Enums\PainSiteSource;
use App\Models\Appointment;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\PatientPainSite;
use App\Models\Role;
use App\Models\TriageRecord;
use App\Models\User;
use App\Services\Chart\PainSiteService;
use App\Services\Triage\TriageDeskService;
use App\Support\TriageAcuity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * patient_pain_sites: the three doors (triage desk, clinician from the triage
 * record, patient check-in), each gated by its own capability and facility,
 * and the markers the chart draws from them.
 */
class PainSitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName, ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = $facility ?? Facility::factory()->create(['type' => 'hospital']);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function actAt(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function patient(): Patient
    {
        return Patient::factory()->create([
            'date_of_birth'       => '1985-04-02',
            'sex'                 => 'male',
            'privacy_preferences' => ['require_consent_for_full_record' => false],
            'is_demo'             => (bool) config('demo.enabled'),
        ]);
    }

    private function triageRecord(User $nurse, Facility $facility, Patient $patient): TriageRecord
    {
        $triage = app(TriageDeskService::class);
        $visit  = $triage->openVisitFor($patient->id, $facility->id, $nurse->id);

        return $triage->record([
            'visit' => $visit, 'nurse_id' => (string) $nurse->id, 'nurse_role' => 'nurse',
            'presenting_complaint' => 'Pain', 'pain_score' => 6, 'pregnancy_status' => null,
            'acuity' => TriageAcuity::P3, 'vitals' => [],
        ]);
    }

    // ── Triage desk ───────────────────────────────────────────────────────

    public function test_the_triage_form_records_pain_sites_at_the_sessions_facility(): void
    {
        [$nurse, $facility] = $this->staff('nurse');
        $patient = $this->patient();

        $this->actAt($nurse, $facility)->post('/facility/triage', [
            'patient_id'           => $patient->id,
            'presenting_complaint' => 'Abdominal pain',
            'acuity'               => TriageAcuity::LEVELS[0],
            'facility_id'          => (string) Str::uuid(), // ignored: the session decides
            'pain_sites'           => [
                ['region' => 'upper_abdomen', 'severity' => 7, 'trend' => 'worse', 'note' => 'Since last night'],
                ['region' => '', 'severity' => ''], // blank row, skipped
            ],
        ])->assertRedirect();

        $site = PatientPainSite::firstOrFail();
        $this->assertSame(1, PatientPainSite::count());
        $this->assertSame($facility->id, $site->facility_id);
        $this->assertSame(PainSiteSource::Triage, $site->source);
        $this->assertSame('upper_abdomen', $site->region->value);
        $this->assertSame(7, $site->severity);
        $this->assertNotNull($site->triage_record_id);
    }

    public function test_an_unknown_region_or_out_of_range_severity_is_refused(): void
    {
        [$nurse, $facility] = $this->staff('nurse');
        $patient = $this->patient();

        $this->actAt($nurse, $facility)->post('/facility/triage', [
            'patient_id' => $patient->id, 'presenting_complaint' => 'x', 'acuity' => TriageAcuity::LEVELS[0],
            'pain_sites' => [['region' => 'tail', 'severity' => 11]],
        ])->assertSessionHasErrors(['pain_sites.0.region', 'pain_sites.0.severity']);

        $this->assertSame(0, PatientPainSite::count());
    }

    public function test_a_clinician_adds_and_resolves_from_the_triage_record(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $patient = $this->patient();
        $record  = $this->triageRecord($doctor, $facility, $patient);

        $this->actAt($doctor, $facility)
            ->post("/facility/triage/{$record->id}/pain-sites", ['pain_sites' => [['region' => 'chest', 'severity' => 5]]])
            ->assertRedirect();

        $site = PatientPainSite::firstOrFail();
        $this->assertSame(PainSiteSource::Clinician, $site->source);
        $this->assertSame((string) $doctor->id, (string) $site->recorded_by_user_id);

        $this->actAt($doctor, $facility)->get("/facility/triage/{$record->id}")
            ->assertOk()->assertSee(__('pain.region.chest'));

        $this->actAt($doctor, $facility)
            ->post("/facility/triage/{$record->id}/pain-sites/{$site->id}/resolve")
            ->assertRedirect();

        $this->assertNotNull($site->fresh()->resolved_at);
        $this->assertSame(1, PatientPainSite::count(), 'resolved, never deleted');
    }

    public function test_a_nurse_adding_from_the_record_is_recorded_as_triage(): void
    {
        [$nurse, $facility] = $this->staff('nurse');
        $patient = $this->patient();
        $record  = $this->triageRecord($nurse, $facility, $patient);

        $this->actAt($nurse, $facility)
            ->post("/facility/triage/{$record->id}/pain-sites", ['pain_sites' => [['region' => 'neck', 'severity' => 3]]])
            ->assertRedirect();

        $this->assertSame(PainSiteSource::Triage, PatientPainSite::firstOrFail()->source);
    }

    public function test_writes_are_gated_by_capability_and_facility(): void
    {
        [$nurse, $facility] = $this->staff('nurse');
        $patient = $this->patient();
        $record  = $this->triageRecord($nurse, $facility, $patient);

        // No triage capability: refused by the role gate.
        [$reception] = $this->staff('receptionist', $facility);
        $this->actAt($reception, $facility)
            ->post("/facility/triage/{$record->id}/pain-sites", ['pain_sites' => [['region' => 'chest', 'severity' => 5]]])
            ->assertForbidden();

        // A nurse at ANOTHER facility: the record does not exist for them.
        [$stranger, $other] = $this->staff('nurse');
        $this->actAt($stranger, $other)
            ->post("/facility/triage/{$record->id}/pain-sites", ['pain_sites' => [['region' => 'chest', 'severity' => 5]]])
            ->assertNotFound();

        // And a site reported to another facility cannot be resolved from here.
        $foreign = app(PainSiteService::class)->record($patient->id, $other->id, PainSiteSource::Triage, null, ['region' => 'face', 'severity' => 2]);
        $this->actAt($nurse, $facility)
            ->post("/facility/triage/{$record->id}/pain-sites/{$foreign->id}/resolve")
            ->assertNotFound();

        $this->assertSame(1, PatientPainSite::count());
        $this->assertNull($foreign->fresh()->resolved_at);
    }

    // ── Patient check-in (mobile) ─────────────────────────────────────────

    public function test_the_patient_reports_pain_sites_at_check_in(): void
    {
        $facility = Facility::factory()->create();
        $patient  = $this->patient();
        $appt     = Appointment::factory()->create([
            'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'status' => 'scheduled', 'scheduled_at' => now()->addDays(2),
        ]);

        $raw = 'pat_' . Str::random(40);
        PatientAccessToken::create([
            'patient_id' => $patient->id, 'token_hash' => Hash::make($raw),
            'token_prefix' => substr($raw, 0, 12), 'expires_at' => now()->addDays(30),
        ]);
        $headers = ['Authorization' => "Bearer {$raw}", 'Accept' => 'application/json'];

        $this->postJson("/api/mobile/preconsult/appointments/{$appt->id}/pain-sites", [
            'sites' => [['region' => 'forehead', 'severity' => 5, 'trend' => 'worse']],
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.0.region', 'forehead')
            ->assertJsonPath('data.0.source', 'patient_checkin');

        $site = PatientPainSite::firstOrFail();
        $this->assertSame($facility->id, $site->facility_id);
        $this->assertSame($appt->id, $site->appointment_id);

        $this->getJson("/api/mobile/preconsult/appointments/{$appt->id}/pain-sites", $headers + ['Accept-Language' => 'fr'])
            ->assertOk()
            ->assertJsonPath('data.0.region_label', 'Front');

        // Someone else's appointment: 404, as the questionnaire answers.
        $stranger = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $theirs = Appointment::factory()->create([
            'patient_id' => $stranger->id, 'facility_id' => $facility->id,
            'status' => 'scheduled', 'scheduled_at' => now()->addDays(2),
        ]);
        $this->postJson("/api/mobile/preconsult/appointments/{$theirs->id}/pain-sites", [
            'sites' => [['region' => 'chest', 'severity' => 5]],
        ], $headers)->assertNotFound();

        $this->postJson("/api/mobile/preconsult/appointments/{$appt->id}/pain-sites", ['sites' => [['region' => 'nowhere', 'severity' => 5]]], $headers)
            ->assertUnprocessable();

        $this->assertSame(1, PatientPainSite::count());
    }

    // ── Markers ───────────────────────────────────────────────────────────

    public function test_markers_render_per_front_region_and_back_regions_are_listed_without_a_pin(): void
    {
        [$doctor, $facility] = $this->staff('doctor');
        $patient = $this->patient();
        Appointment::create([
            'patient_id' => $patient->id, 'facility_id' => $facility->id, 'provider_id' => $doctor->id,
            'appointment_type' => 'consultation', 'status' => 'scheduled', 'scheduled_at' => now()->addDay(),
        ]);

        $svc = app(PainSiteService::class);
        $svc->record($patient->id, $facility->id, PainSiteSource::Triage, null, ['region' => 'chest', 'severity' => 7]);
        $svc->record($patient->id, $facility->id, PainSiteSource::PatientCheckin, null, ['region' => 'left_leg', 'severity' => 3]);
        $svc->record($patient->id, $facility->id, PainSiteSource::Clinician, null, ['region' => 'lower_back', 'severity' => 4]);
        $resolved = $svc->record($patient->id, $facility->id, PainSiteSource::Triage, null, ['region' => 'neck', 'severity' => 2]);
        $svc->resolve($resolved, null);

        $this->actAt($doctor, $facility)->get('/facility/patients/' . $patient->id)
            ->assertOk()
            ->assertSee('class="cs-pin" aria-hidden="true" data-region="chest"', false)
            ->assertSee('data-region="left_leg"', false)
            ->assertDontSee('data-region="lower_back"', false)
            ->assertSee('data-pain-site="lower_back"', false)
            ->assertSee(__('pain.back_not_drawn'))
            ->assertDontSee('data-region="neck"', false)
            ->assertDontSee('data-pain-site="neck"', false)
            ->assertSee('7/10')
            ->assertSee(__('pain.source.patient_checkin'));
    }
}
