<?php

namespace Tests\Feature\Chart;

use App\Enums\PainRegion;
use App\Enums\PainSiteSource;
use App\Models\AccessLog;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Chart\PainSiteService;
use App\Services\Triage\TriageDeskService;
use App\Support\BodyFigure;
use App\Support\TriageAcuity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The chart's Summary tab: every section gated like the tab it summarises,
 * every section shown written to the patient's access log, nothing from
 * another facility's patient-reported trail, and a body map that never
 * guesses.
 */
class ChartSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName = 'doctor', ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = $facility ?? Facility::factory()->create(['type' => 'hospital']);

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

    private function actAt(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function patient(bool $consentSwitch, ?string $dob = '1985-04-02', ?string $sex = 'female'): Patient
    {
        return Patient::factory()->create([
            'date_of_birth'       => $dob,
            'sex'                 => $sex,
            'privacy_preferences' => ['require_consent_for_full_record' => $consentSwitch],
        ]);
    }

    private function appointment(User $doctor, Facility $facility, Patient $patient): void
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

    private function grant(Patient $patient, Facility $facility): void
    {
        ConsentGrant::create([
            'patient_id'        => $patient->id,
            'facility_id'       => $facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['patient.summary', 'vitals.read'],
            'status'            => 'active',
            'expires_at'        => now()->addHours(4),
        ]);
    }

    /** Vitals + an encounter + an allergy + a pain site, all at $facility. */
    private function fill(User $nurse, Facility $facility, Patient $patient): void
    {
        AllergyRecord::create(['patient_id' => $patient->id, 'provider_id' => $nurse->id, 'substance' => 'Penicillin', 'severity' => 'moderate', 'status' => 'active']);

        $triage = app(TriageDeskService::class);
        $visit  = $triage->openVisitFor($patient->id, $facility->id, $nurse->id);
        $triage->record([
            'visit' => $visit, 'nurse_id' => (string) $nurse->id, 'nurse_role' => 'doctor',
            'presenting_complaint' => 'Fever', 'pain_score' => null, 'pregnancy_status' => null,
            'acuity' => TriageAcuity::P2,
            'vitals' => ['temperature' => 38.6, 'pulse_rate' => 108, 'systolic_bp' => 102, 'diastolic_bp' => 66, 'oxygen_saturation' => 98],
        ]);

        Encounter::create([
            'visit_id' => $visit->id, 'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'encounter_type' => 'outpatient', 'status' => 'active', 'started_at' => now()->subHour(),
            'reason_for_encounter' => 'Fever and abdominal pain',
        ]);

        app(PainSiteService::class)->record($patient->id, $facility->id, PainSiteSource::Triage, (string) $nurse->id,
            ['region' => 'chest', 'severity' => 7, 'note' => 'Sharp']);
    }

    private function logged(Patient $patient, string $category, string $type = 'read'): int
    {
        return AccessLog::where('patient_id', $patient->id)->where('data_category', $category)->where('access_type', $type)->count();
    }

    // ── Gate ──────────────────────────────────────────────────────────────

    public function test_without_a_grant_only_identity_allergies_and_medicines_show(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(consentSwitch: true);
        $this->appointment($doctor, $facility, $patient);
        $this->fill($doctor, $facility, $patient);

        $this->actAt($doctor, $facility)->get('/facility/patients/' . $patient->id)
            ->assertOk()
            ->assertSee(__('chart.scope_preview_title'))
            ->assertSee('Penicillin')
            ->assertSee(__('chart.medicines_title'))
            ->assertDontSee(__('chart_summary.presenting_title'))
            ->assertDontSee(__('chart_summary.kpi_bp'))
            ->assertDontSee(__('chart_summary.all_encounters'))
            ->assertDontSee('Fever and abdominal pain')
            ->assertDontSee(__('chart_summary.care_team_title'))
            ->assertDontSee(__('chart_summary.consent_open_title'))
            ->assertDontSee('data-region', false);

        $this->assertSame(1, $this->logged($patient, 'patient_identity'));
        $this->assertSame(1, $this->logged($patient, 'allergies'));
        $this->assertSame(1, $this->logged($patient, 'medications'));
        $this->assertSame(0, $this->logged($patient, 'vitals'));
        $this->assertSame(0, $this->logged($patient, 'pain_sites'));
        $this->assertSame(0, $this->logged($patient, 'encounters'));
    }

    public function test_with_a_grant_every_section_shows_and_each_is_access_logged(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(consentSwitch: true);
        $this->appointment($doctor, $facility, $patient);
        $this->fill($doctor, $facility, $patient);
        $this->grant($patient, $facility);

        $this->actAt($doctor, $facility)->get('/facility/patients/' . $patient->id)
            ->assertOk()
            ->assertSee(__('chart_summary.consent_open_title'))
            ->assertDontSee(__('chart.scope_preview_title'))
            ->assertSee(__('chart_summary.presenting_title'))
            ->assertSee(__('chart_summary.kpi_bp'))
            ->assertSee('102/66')
            ->assertSee('Fever and abdominal pain')
            ->assertSee(__('chart_summary.care_mix_title'))
            ->assertSee(__('chart_summary.results_title'))
            ->assertSee(__('chart_summary.referrals_title'))
            ->assertSee(__('chart_summary.documents_title'))
            ->assertSee(__('chart_summary.immunizations_title'))
            ->assertSee(__('chart_summary.preconsult_title'))
            ->assertSee(__('chart_summary.care_team_title'))
            ->assertSee(__('chart_summary.family_title'))
            ->assertSee(__('chart_summary.consent_pill', ['until' => now()->addHours(4)->translatedFormat('d M H:i')]))
            ->assertSee('role="img"', false);

        foreach (\App\Services\Chart\ChartSummaryBuilder::SECTIONS as $category) {
            $this->assertSame(1, $this->logged($patient, $category), "category {$category} not access-logged");
        }
    }

    public function test_the_privacy_switch_decides_the_scope_without_a_grant(): void
    {
        [$doctor, $facility] = $this->staff();
        $open = $this->patient(consentSwitch: false);
        $this->appointment($doctor, $facility, $open);
        $this->fill($doctor, $facility, $open);

        // Switch off: an implicit basis opens the full summary, with no
        // consent banner because nothing was granted.
        $this->actAt($doctor, $facility)->get('/facility/patients/' . $open->id)
            ->assertOk()
            ->assertSee(__('chart_summary.presenting_title'))
            ->assertSee('Fever and abdominal pain')
            ->assertDontSee(__('chart_summary.consent_open_title'))
            ->assertSee(__('chart_summary.request_wider'));

        // Switch on, same basis: only the safety minimum.
        $closed = $this->patient(consentSwitch: true);
        $this->appointment($doctor, $facility, $closed);
        $this->fill($doctor, $facility, $closed);

        $this->actAt($doctor, $facility)->get('/facility/patients/' . $closed->id)
            ->assertOk()
            ->assertDontSee(__('chart_summary.presenting_title'))
            ->assertDontSee('Fever and abdominal pain');
    }

    public function test_another_facilitys_pain_sites_and_access_rows_are_not_shown(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(consentSwitch: false);
        $this->appointment($doctor, $facility, $patient);

        [$elsewhere, $other] = $this->staff();
        app(PainSiteService::class)->record($patient->id, $other->id, PainSiteSource::PatientCheckin, null,
            ['region' => 'left_leg', 'severity' => 4, 'note' => 'Disclosed elsewhere']);
        AccessLog::create([
            'patient_id' => $patient->id, 'actor_id' => $elsewhere->id, 'actor_type' => 'provider',
            'facility_id' => $other->id, 'purpose' => 'treatment:appointment',
            'data_category' => 'diagnoses', 'resource_type' => 'Patient', 'access_type' => 'read',
        ]);

        $this->actAt($doctor, $facility)->get('/facility/patients/' . $patient->id)
            ->assertOk()
            ->assertSee(__('chart_summary.pain_none'))
            ->assertDontSee('Disclosed elsewhere')
            ->assertDontSee('data-region="left_leg"', false)
            ->assertDontSee($elsewhere->name);
    }

    // ── Body map ──────────────────────────────────────────────────────────

    public function test_the_figure_follows_recorded_sex_and_age_band_and_never_guesses(): void
    {
        $this->assertSame('child-girl', BodyFigure::for('female', Carbon::now()->subYears(8))->figure);
        $this->assertSame('child-boy', BodyFigure::for('male', Carbon::now()->subYears(12))->figure);
        $this->assertSame('adult-male', BodyFigure::for('male', Carbon::now()->subYears(13))->figure);
        $this->assertSame('adult-female', BodyFigure::for('female', Carbon::now()->subYears(40))->figure);

        $unknown = BodyFigure::for(null, Carbon::now()->subYears(30));
        $this->assertTrue($unknown->sexAssumed);
        $this->assertSame('adult-male', $unknown->figure);

        $noDob = BodyFigure::for('female', null);
        $this->assertTrue($noDob->ageAssumed);
        $this->assertSame('adult-female', $noDob->figure);

        // And on the page: a child girl draws the child-girl figure, and an
        // unrecorded sex says so under the figure.
        [$doctor, $facility] = $this->staff();
        $girl = $this->patient(false, Carbon::now()->subYears(8)->toDateString(), 'female');
        $this->appointment($doctor, $facility, $girl);
        $this->actAt($doctor, $facility)->get('/facility/patients/' . $girl->id)
            ->assertOk()
            ->assertSee('data-figure="child-girl"', false)
            ->assertSee(__('pain.figure_follows'));

        $unsexed = $this->patient(false, '1990-01-01', null);
        $this->appointment($doctor, $facility, $unsexed);
        $this->actAt($doctor, $facility)->get('/facility/patients/' . $unsexed->id)
            ->assertOk()
            ->assertSee(__('pain.figure_sex_unrecorded'));
    }

    public function test_the_heart_sprite_is_drawn_and_reduced_motion_stills_it(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(false, '1980-01-01', 'male');
        $this->appointment($doctor, $facility, $patient);

        $this->actAt($doctor, $facility)->get('/facility/patients/' . $patient->id)
            ->assertOk()
            ->assertSee('adult-male-current.webp')
            ->assertSee('adult-male-heart.png')
            ->assertSee('data-heart="adult-male"', false)
            // meta.json heart box for adult-male, as percentages.
            ->assertSee('left:36.721%;top:16.806%', false)
            ->assertSee('css/chart-summary.css');

        foreach (['adult-male', 'adult-female', 'child-boy', 'child-girl'] as $f) {
            $this->assertFileExists(public_path("images/health-id/living/{$f}-base.png"));
            $this->assertFileExists(public_path("images/health-id/living/{$f}-current.webp"));
            $this->assertFileExists(public_path("images/health-id/living/{$f}-heart.png"));
        }

        $css = file_get_contents(public_path('css/chart-summary.css'));
        $this->assertStringContainsString('steps(6)', $css);
        $this->assertStringContainsString('steps(4)', $css);
        $this->assertMatchesRegularExpression('/prefers-reduced-motion:\s*reduce\)\s*\{[^}]*cs-current[^}]*animation:\s*none/s', $css);
    }

    public function test_the_summary_renders_in_french(): void
    {
        [$doctor, $facility] = $this->staff();
        $patient = $this->patient(consentSwitch: true);
        $this->appointment($doctor, $facility, $patient);
        $this->fill($doctor, $facility, $patient);
        $this->grant($patient, $facility);

        $this->actAt($doctor, $facility)->get('/facility/patients/' . $patient->id . '?lang=fr')
            ->assertOk()
            ->assertSee('Dossier complet ouvert sous consentement')
            ->assertSee('Motif de consultation')
            ->assertSee('Thorax')
            ->assertSee('Consentement et accès');
    }
}
