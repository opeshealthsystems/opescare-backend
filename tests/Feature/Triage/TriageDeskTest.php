<?php

namespace Tests\Feature\Triage;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\PatientRecordedVital;
use App\Models\Role;
use App\Models\TriageRecord;
use App\Models\TriageScore;
use App\Models\TriageVitalSign;
use App\Models\User;
use App\Models\Visit;
use App\Support\TriageAcuity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The triage desk, and specifically the two ways it is allowed to hurt someone.
 *
 * The happy path is here only far enough to make the negatives meaningful. What
 * is actually being defended:
 *
 *   1. A CHILD IS NEVER SCORED AGAINST ADULT BANDS. VitalBandResolver's bands
 *      are NEWS2, published for ages 16 and over. A respiratory rate of 30 is
 *      an emergency in an adult and unremarkable in an infant, so adult bands
 *      applied to a child under-triage sick children — the failure that kills.
 *      The desk must show no interpretation at all for a patient under 16, for
 *      a patient whose age is unknown, and in pregnancy (which NEWS2 also
 *      excludes). test_an_adult_does_get_advisory_flags is the control that
 *      stops the three refusal tests from passing vacuously.
 *
 *   2. A HOME READING NEVER BECOMES AN OBSERVATION. `patient_recorded_vitals`
 *      is a self-reported table; `triage_vital_signs` is a facility-measured
 *      one. Nothing may copy across.
 */
class TriageDeskTest extends TestCase
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
    private function staff(string $roleName = 'triage_nurse'): array
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

    private function patient(?string $dob = '1985-04-02'): Patient
    {
        return Patient::factory()->create(['date_of_birth' => $dob]);
    }

    /**
     * Assert that a rendered triage page carries NO band verdict of any kind.
     *
     * The band words come from VitalBandResolver::labelKey(), which names
     * `labs.flag.*` — so this checks for the actual words a leaked verdict
     * would print, not just for the advisory heading. The first version of
     * this test only checked the heading, and a deliberately broken guard
     * (interpret() forced to return verdicts) still passed: the per-reading
     * badges leaked into the table unnoticed. This is what closes that.
     */
    private function assertNoBandVerdictRendered(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertDontSee(__('triage.advisory_source'));
        $response->assertDontSee(__('triage.advisory_note'));
        $response->assertDontSee(__('triage.advisory_none'));

        foreach (['critical', 'high', 'low', 'abnormal', 'normal'] as $band) {
            $response->assertDontSee(__('labs.flag.' . $band));
        }
    }

    /** The vitals payload used throughout: deliberately alarming in an ADULT. */
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

    private function recordFor(User $user, Facility $facility, Patient $patient, array $overrides = []): TriageRecord
    {
        $this->actingAsStaff($user, $facility)
            ->post('/facility/triage', array_merge([
                'patient_id'           => $patient->id,
                'presenting_complaint' => 'Chest pain since this morning',
                'pain_score'           => 7,
                'acuity'               => TriageAcuity::P2,
            ], $this->alarmingVitals(), $overrides))
            ->assertRedirect();

        return TriageRecord::latest('created_at')->firstOrFail();
    }

    // ── The happy path, only far enough to make the negatives mean something ──

    public function test_a_nurse_records_a_triage_and_the_acuity_is_stored_as_a_manual_score(): void
    {
        [$user, $facility] = $this->staff();
        $patient = $this->patient();

        $record = $this->recordFor($user, $facility, $patient);

        $this->assertSame(TriageAcuity::P2, $record->acuity_score);
        $this->assertSame($user->id, $record->nurse_id);

        // The visit the triage hangs off is at THIS facility, from the session.
        $visit = Visit::findOrFail($record->visit_id);
        $this->assertSame($facility->id, $visit->facility_id);
        $this->assertSame($patient->id, $visit->patient_id);

        // The priority is recorded as a HUMAN decision, never a computed one.
        $score = TriageScore::where('triage_assessment_id', $record->id)->firstOrFail();
        $this->assertSame('manual', $score->scoring_system);
        $this->assertSame('nurse', $score->computed_by);
        $this->assertSame(TriageAcuity::P2, $score->priority_level);
        $this->assertNull($score->numeric_score);

        $vitals = TriageVitalSign::where('triage_record_id', $record->id)->firstOrFail();
        $this->assertSame(132, $vitals->pulse_rate);
        $this->assertSame($user->id, $vitals->recorded_by);

        $this->assertDatabaseHas('triage_audits', [
            'triage_record_id' => $record->id,
            'action'           => 'assessed',
        ]);
    }

    /**
     * THE CONTROL. Without this, the three refusal tests below would pass on a
     * desk that shows nobody anything.
     */
    public function test_an_adult_does_get_advisory_flags_clearly_labelled_as_adult_bands(): void
    {
        [$user, $facility] = $this->staff();
        $record = $this->recordFor($user, $facility, $this->patient('1985-04-02'));

        $response = $this->actingAsStaff($user, $facility)->get('/facility/triage/' . $record->id);

        $response->assertOk();
        $response->assertSee(__('triage.advisory_source'));
        $response->assertSee(__('triage.advisory_note'));
        $response->assertDontSee(__('triage.no_bands_title'));
    }

    // ── SAFETY 1: no adult-band interpretation for a child ───────────────────

    public function test_a_patient_under_sixteen_gets_no_adult_band_interpretation_and_no_derived_acuity(): void
    {
        [$user, $facility] = $this->staff();
        $child = $this->patient(now()->subYears(7)->toDateString());

        $record = $this->recordFor($user, $facility, $child);

        $response = $this->actingAsStaff($user, $facility)->get('/facility/triage/' . $record->id);

        $response->assertOk();

        // The refusal is stated plainly, and the adult-band label never appears.
        $response->assertSee(__('triage.no_bands_title'));
        $response->assertSee(__('triage.no_bands.paediatric'), false);
        $this->assertNoBandVerdictRendered($response);

        // And nothing derived a priority: the ONLY acuity on the record is the
        // one the nurse chose, and it was not raised by the alarming numbers.
        $this->assertSame(TriageAcuity::P2, $record->fresh()->acuity_score);
        $this->assertSame(
            ['manual'],
            TriageScore::where('triage_assessment_id', $record->id)->pluck('scoring_system')->unique()->values()->all()
        );
    }

    public function test_a_patient_with_no_recorded_date_of_birth_gets_no_interpretation(): void
    {
        [$user, $facility] = $this->staff();
        $record = $this->recordFor($user, $facility, $this->patient(null));

        $response = $this->actingAsStaff($user, $facility)->get('/facility/triage/' . $record->id);

        $response->assertOk();
        $response->assertSee(__('triage.no_bands_title'));
        $response->assertSee(__('triage.no_bands.age_unknown'), false);
        $this->assertNoBandVerdictRendered($response);
    }

    public function test_a_pregnant_patient_gets_no_interpretation_because_news2_excludes_pregnancy(): void
    {
        [$user, $facility] = $this->staff();

        $record = $this->recordFor($user, $facility, $this->patient('1990-01-01'), [
            'pregnancy_status' => 'pregnant',
        ]);

        $response = $this->actingAsStaff($user, $facility)->get('/facility/triage/' . $record->id);

        $response->assertOk();
        $response->assertSee(__('triage.no_bands_title'));
        $response->assertSee(__('triage.no_bands.pregnancy'), false);
        $this->assertNoBandVerdictRendered($response);
    }

    /** The unit-level statement of the same rule, without a request. */
    public function test_the_band_context_returns_no_interpretation_for_a_child(): void
    {
        $child = \App\Support\TriageBandContext::for(now()->subYears(4));

        $this->assertFalse($child->eligible);
        $this->assertSame('paediatric', $child->reason);
        $this->assertSame([], $child->interpret([
            'respiratory_rate' => ['value' => 30, 'unit' => '/min'],
            'pulse_rate'       => ['value' => 140, 'unit' => 'bpm'],
        ]));

        $adult = \App\Support\TriageBandContext::for(now()->subYears(40));
        $this->assertTrue($adult->eligible);
        $this->assertNotSame([], $adult->interpret([
            'respiratory_rate' => ['value' => 30, 'unit' => '/min'],
        ]));
    }

    // ── SAFETY 2: self-reported readings never become observations ───────────

    public function test_a_patients_own_readings_are_never_written_into_triage_vital_signs(): void
    {
        [$user, $facility] = $this->staff();
        $patient = $this->patient();

        // A home blood pressure the patient logged themselves.
        PatientRecordedVital::create([
            'patient_id'      => $patient->id,
            'measure'         => 'blood_pressure',
            'value_primary'   => 178,
            'value_secondary' => 121,
            'unit'            => 'mmHg',
            'measured_at'     => now()->subDay(),
        ]);

        // A triage recorded with NO observations at all.
        $this->actingAsStaff($user, $facility)
            ->post('/facility/triage', [
                'patient_id'           => $patient->id,
                'presenting_complaint' => 'Headache',
                'acuity'               => TriageAcuity::P4,
            ])
            ->assertRedirect();

        $record = TriageRecord::latest('created_at')->firstOrFail();

        // Nothing was invented from the home reading — not a row, not a value.
        $this->assertSame(0, TriageVitalSign::where('triage_record_id', $record->id)->count());
        $this->assertDatabaseMissing('triage_vital_signs', ['systolic_bp' => 178]);

        // It IS shown to the nurse, under its own unverified heading.
        $this->actingAsStaff($user, $facility)
            ->get('/facility/triage/' . $record->id)
            ->assertOk()
            ->assertSee(__('triage.self_reported_badge'))
            ->assertSee(__('triage.self_reported_note'));
    }

    // ── Tenancy and role ────────────────────────────────────────────────────

    public function test_a_triage_record_at_another_facility_does_not_exist(): void
    {
        [$mine, $myFacility]      = $this->staff();
        [$theirs, $theirFacility] = $this->staff();

        $record = $this->recordFor($theirs, $theirFacility, $this->patient());

        // Not 403 — the record is not this desk's to know about at all.
        $this->actingAsStaff($mine, $myFacility)
            ->get('/facility/triage/' . $record->id)
            ->assertNotFound();

        // And it is absent from the queue.
        $this->actingAsStaff($mine, $myFacility)
            ->get('/facility/triage')
            ->assertOk()
            ->assertSee(__('triage.empty_queue'));
    }

    public function test_a_role_without_the_triage_capability_is_refused_with_403(): void
    {
        [$user, $facility] = $this->staff('receptionist');

        $this->actingAsStaff($user, $facility)
            ->get('/facility/triage')
            ->assertForbidden();

        $this->actingAsStaff($user, $facility)
            ->post('/facility/triage', [
                'patient_id'           => $this->patient()->id,
                'presenting_complaint' => 'x',
                'acuity'               => TriageAcuity::P1,
            ])
            ->assertForbidden();
    }

    public function test_the_facility_is_never_taken_from_the_request(): void
    {
        [$user, $facility] = $this->staff();
        $elsewhere = Facility::factory()->create(['type' => 'hospital']);
        $patient   = $this->patient();

        $this->actingAsStaff($user, $facility)
            ->post('/facility/triage', [
                'patient_id'           => $patient->id,
                'facility_id'          => $elsewhere->id,   // ignored
                'presenting_complaint' => 'Fever',
                'acuity'               => TriageAcuity::P3,
            ])
            ->assertRedirect();

        $record = TriageRecord::latest('created_at')->firstOrFail();
        $this->assertSame($facility->id, Visit::findOrFail($record->visit_id)->facility_id);
    }

    // ── Reassessment: accretion, never overwrite ─────────────────────────────

    public function test_a_reassessment_supersedes_the_record_without_changing_it(): void
    {
        [$user, $facility] = $this->staff();
        $record = $this->recordFor($user, $facility, $this->patient());

        $originalComplaint = $record->presenting_complaint;
        $originalPulse     = TriageVitalSign::where('triage_record_id', $record->id)->value('pulse_rate');

        $this->actingAsStaff($user, $facility)
            ->post('/facility/triage/' . $record->id . '/reassess', [
                'new_priority'      => TriageAcuity::P1,
                'reason_for_change' => 'Became unresponsive in the waiting area',
                'pulse_rate'        => 148,
                'oxygen_saturation' => 84,
            ])
            ->assertRedirect();

        // The original assessment is untouched.
        $record->refresh();
        $this->assertSame(TriageAcuity::P2, $record->acuity_score);
        $this->assertSame($originalComplaint, $record->presenting_complaint);

        // The first observation row is untouched; a SECOND one was appended.
        $rows = TriageVitalSign::where('triage_record_id', $record->id)->orderBy('recorded_at')->get();
        $this->assertCount(2, $rows);
        $this->assertSame($originalPulse, $rows->first()->pulse_rate);
        $this->assertSame(148, $rows->last()->pulse_rate);

        $this->assertDatabaseHas('triage_reassessments', [
            'triage_assessment_id' => $record->id,
            'previous_priority'    => TriageAcuity::P2,
            'new_priority'         => TriageAcuity::P1,
        ]);

        $this->assertDatabaseHas('triage_audits', [
            'triage_record_id' => $record->id,
            'action'           => 'reassessed',
        ]);

        // The queue now shows the NEW priority, read from the history.
        $this->actingAsStaff($user, $facility)
            ->get('/facility/triage')
            ->assertOk()
            ->assertSee(__('triage.acuity_levels.' . TriageAcuity::P1));
    }

    public function test_a_reassessment_without_a_reason_is_refused(): void
    {
        [$user, $facility] = $this->staff();
        $record = $this->recordFor($user, $facility, $this->patient());

        $this->actingAsStaff($user, $facility)
            ->post('/facility/triage/' . $record->id . '/reassess', [
                'new_priority' => TriageAcuity::P1,
            ])
            ->assertSessionHasErrors('reason_for_change');

        $this->assertDatabaseCount('triage_reassessments', 0);
    }

    // ── Patient lookup ──────────────────────────────────────────────────────

    public function test_the_patient_is_found_only_by_an_exact_health_id(): void
    {
        [$user, $facility] = $this->staff();
        $patient = Patient::factory()->create(['health_id' => 'OC-ZZZ-777', 'last_name' => 'Ngassa']);

        $this->actingAsStaff($user, $facility)
            ->get('/facility/triage/new?health_id=OC-ZZZ-777')
            ->assertOk()
            ->assertSee('OC-ZZZ-777');

        // A prefix is not a match, and the surname is not searchable.
        $this->actingAsStaff($user, $facility)
            ->get('/facility/triage/new?health_id=OC-ZZZ')
            ->assertOk()
            ->assertSee(__('triage.not_found'))
            ->assertDontSee('Ngassa');
    }

    // ── Navigation ──────────────────────────────────────────────────────────

    /**
     * A page reachable only by typing its URL is not shipped. The link must be
     * in the sidebar of the roles that hold the capability, and absent from the
     * roles that do not.
     */
    public function test_the_desk_is_linked_from_the_sidebar_of_roles_that_hold_it(): void
    {
        foreach (['triage_nurse', 'nurse', 'ward_nurse', 'midwife', 'doctor'] as $roleName) {
            [$user, $facility] = $this->staff($roleName);

            $this->actingAsStaff($user, $facility)
                ->get('/facility')
                ->assertOk()
                ->assertSee('/facility/triage');
        }

        [$reception, $receptionFacility] = $this->staff('receptionist');

        $this->actingAsStaff($reception, $receptionFacility)
            ->get('/facility')
            ->assertOk()
            ->assertDontSee('/facility/triage');
    }

    public function test_the_queue_puts_the_most_urgent_first_not_the_most_recent(): void
    {
        // The regression this pins: the queue ordered by created_at DESC. It
        // performed the assessment, printed the priority on every row, and
        // handed the nurse the arrival order anyway — triage doing the
        // opposite of triage, while looking like triage.
        [$user, $facility] = $this->staff();

        // Entered least-urgent first, so arrival order and clinical order
        // disagree. Remove the sort and this fails immediately.
        foreach ([TriageAcuity::P5, TriageAcuity::P4, TriageAcuity::P1, TriageAcuity::P3] as $acuity) {
            $this->recordFor($user, $facility, $this->patient(), ['acuity' => $acuity]);
        }

        $rows = $this->actingAsStaff($user, $facility)
            ->get('/facility/triage')
            ->assertOk()
            ->viewData('rows');

        $this->assertSame(
            [TriageAcuity::P1, TriageAcuity::P3, TriageAcuity::P4, TriageAcuity::P5],
            $rows->pluck('priority')->values()->all(),
            'The queue must be ordered by clinical urgency, not by arrival time.'
        );
    }

    public function test_a_reassessment_moves_a_patient_up_the_queue(): void
    {
        // A waiting patient deteriorating is the whole reason reassessment
        // exists. If the queue sorted on the ORIGINAL acuity, a nurse could
        // re-triage someone to P1 and watch them stay at the bottom.
        [$user, $facility] = $this->staff();

        // The deteriorating patient is triaged FIRST and the P2 arrives after,
        // so arrival order would put the P2 on top and only clinical urgency
        // puts the reassessed patient there.
        //
        // The two created_at values are pinned an hour apart on purpose. Both
        // records are inserted inside the same second by default, which makes
        // an arrival-order sort arbitrary — the test then passed roughly half
        // the time with the urgency sort mutated away, which is worse than no
        // test at all.
        $deteriorating = $this->recordFor($user, $facility, $this->patient(), ['acuity' => TriageAcuity::P5]);
        TriageRecord::whereKey($deteriorating->id)->update(['created_at' => now()->subHour()]);

        $this->recordFor($user, $facility, $this->patient(), ['acuity' => TriageAcuity::P2]);

        $this->actingAsStaff($user, $facility)
            ->post("/facility/triage/{$deteriorating->id}/reassess", [
                'new_priority' => TriageAcuity::P1,
                'reason_for_change' => 'Became unresponsive while waiting',
            ])
            ->assertRedirect();

        $rows = $this->actingAsStaff($user, $facility)
            ->get('/facility/triage')
            ->assertOk()
            ->viewData('rows');

        $this->assertSame(TriageAcuity::P1, $rows->first()['priority']);
        $this->assertSame(
            $deteriorating->id,
            $rows->first()['record']->id,
            'The reassessed patient must lead the queue, not the one triaged P2 earlier.'
        );
    }

    public function test_an_acuity_outside_the_five_levels_is_refused(): void
    {
        [$user, $facility] = $this->staff();

        $this->actingAsStaff($user, $facility)
            ->post('/facility/triage', [
                'patient_id'           => $this->patient()->id,
                'presenting_complaint' => 'Cough',
                'acuity'               => 'P0_made_up',
            ])
            ->assertSessionHasErrors('acuity');

        $this->assertDatabaseCount('triage_records', 0);
    }
}
