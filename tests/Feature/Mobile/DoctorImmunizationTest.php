<?php

namespace Tests\Feature\Mobile;

use App\Models\AdverseReactionNote;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\Role;
use App\Models\User;
use App\Models\VaccinationSchedule;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * MobileDoctorImmunizationController — the clinician write path onto the
 * vaccination card.
 *
 * What is under test is mostly what the controller REFUSES to invent:
 *
 *   • the administering facility and clinician come from the token, and a
 *     historical transcription names NO administering clinician at all;
 *   • a field nobody recorded is absent — dose_number, route, site and an
 *     adverse reaction's outcome are never defaulted;
 *   • `adverse_reaction_count` is null when nobody asked and 0 only when they
 *     did, so a client cannot print "none reported" from a question nobody put;
 *   • a dose is voided, never updated and never deleted, and only by the
 *     facility that recorded it;
 *   • a schedule line closes on an exact match and reopens when the dose that
 *     closed it is retracted.
 */
class DoctorImmunizationTest extends TestCase
{
    use RefreshDatabase;

    private const HEALTH_ID = 'CM-HID-TEST-IMMU-0001';

    /** The token's facility — the recording facility. */
    private Facility $facilityA;

    /** Another facility that also holds a grant on this patient. */
    private Facility $facilityB;

    private Patient $patient;

    private User $doctorA;

    private User $doctorB;

    private string $bearerA;

    private string $bearerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facilityA = Facility::factory()->create(['name' => 'Recording Health Centre']);
        $this->facilityB = Facility::factory()->create(['name' => 'Other Health Centre']);

        $this->doctorA = $this->professional('doctor');
        $this->doctorB = $this->professional('doctor');

        $this->bearerA = app(ProviderSessionService::class)->mint($this->doctorA, $this->facilityA->id, 'immu-a');
        $this->bearerB = app(ProviderSessionService::class)->mint($this->doctorB, $this->facilityB->id, 'immu-b');

        $this->patient = Patient::factory()->create([
            'health_id' => self::HEALTH_ID,
            'is_demo'   => (bool) config('demo.enabled'),
        ]);

        // Both facilities may open this chart, so nothing below is refused for
        // want of a care relationship — the refusals under test are the
        // controller's own rules.
        foreach ([$this->facilityA->id, $this->facilityB->id] as $facilityId) {
            ConsentGrant::create([
                'patient_id'        => $this->patient->id,
                'facility_id'       => $facilityId,
                'authorizing_actor' => 'patient',
                'scope'             => ['patient_identity', 'allergies', 'problems', 'encounters', 'vitals'],
                'status'            => 'active',
                'expires_at'        => now()->addDays(90),
            ]);
        }
    }

    // ── fixtures ─────────────────────────────────────────────────────────

    private function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        return User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }

    private function asA(string $method, string $path, array $body = []): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearerA}"])->json($method, $path, $body);
    }

    private function asB(string $method, string $path, array $body = []): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearerB}"])->json($method, $path, $body);
    }

    /** Record a dose through the API and return the row. */
    private function dose(array $body = []): ImmunizationRecord
    {
        $response = $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', array_merge([
            'vaccine_code' => 'BCG',
            'vaccine_name' => 'BCG',
        ], $body));

        $response->assertStatus(201);

        return ImmunizationRecord::findOrFail($response->json('data.id'));
    }

    // ══════════════════════════════════════════════════════════════════
    // The role gate
    // ══════════════════════════════════════════════════════════════════

    public function test_a_role_without_the_chart_capability_is_refused_on_every_immunisation_route(): void
    {
        $pharmacist = $this->professional('pharmacist');
        $bearer     = app(ProviderSessionService::class)->mint($pharmacist, $this->facilityA->id, 'immu-rx');

        $record = $this->dose();

        $routes = [
            ['GET',  '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations'],
            ['POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations'],
            ['GET',  '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/vaccination-schedule'],
            ['POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/vaccination-schedule'],
            ['GET',  '/api/mobile/doctor/immunizations/' . $record->id],
            ['POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void'],
            ['POST', '/api/mobile/doctor/immunizations/' . $record->id . '/adverse-reactions'],
        ];

        foreach ($routes as [$method, $path]) {
            $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
                ->json($method, $path, ['reason' => 'anything', 'vaccine_code' => 'OPV', 'vaccine_name' => 'OPV'])
                ->assertStatus(403)
                ->assertJsonPath('error_code', 'role_forbidden');
        }

        // Refused, not quietly successful: one dose, still administered.
        $this->assertDatabaseCount('immunization_records', 1);
        $this->assertSame('completed', $record->refresh()->status);
        $this->assertDatabaseCount('adverse_reaction_notes', 0);
    }

    public function test_no_token_means_no_card(): void
    {
        $this->json('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations')->assertStatus(401);
    }

    // ══════════════════════════════════════════════════════════════════
    // Identity comes from the token
    // ══════════════════════════════════════════════════════════════════

    public function test_a_facility_and_an_administering_clinician_posted_in_the_body_are_ignored(): void
    {
        $stranger = $this->professional('doctor');

        $response = $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', [
            'vaccine_code' => 'OPV',
            'vaccine_name' => 'Oral polio vaccine',
            'dose_number'  => 1,
            // The lie the partner API accepted.
            'facility_id'         => $this->facilityB->id,
            'administered_by_id'  => $stranger->id,
            'verification_status' => 'verified',
            'patient_id'          => Str::uuid()->toString(),
        ]);

        $response->assertStatus(201);

        $record = ImmunizationRecord::findOrFail($response->json('data.id'));

        $this->assertSame($this->facilityA->id, $record->facility_id);
        $this->assertSame($this->doctorA->id, $record->administered_by_id);
        $this->assertSame($this->patient->id, $record->patient_id);

        $this->assertDatabaseMissing('immunization_records', ['facility_id' => $this->facilityB->id]);
        $this->assertDatabaseMissing('immunization_records', ['administered_by_id' => $stranger->id]);
    }

    public function test_a_transcribed_paper_card_names_no_administering_clinician(): void
    {
        $response = $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', [
            'vaccine_code'    => 'MEASLES',
            'vaccine_name'    => 'Measles',
            'administered_at' => now()->subYears(3)->toIso8601String(),
            'is_historical'   => true,
        ])->assertStatus(201);

        $record = ImmunizationRecord::findOrFail($response->json('data.id'));

        // The clinician typing the card did NOT give the vaccine.
        $this->assertNull($record->administered_by_id);
        $response->assertJsonPath('data.administered_by_id', null)
            ->assertJsonPath('data.is_historical', true)
            // A transcription is a claim about somebody else's act.
            ->assertJsonPath('data.verification_status', 'unverified');

        // But the facility is still the token's — the record has to say where
        // the transcription was made.
        $this->assertSame($this->facilityA->id, $record->facility_id);
    }

    public function test_a_dose_this_facility_gave_itself_is_attributed_and_verified(): void
    {
        $record = $this->dose(['vaccine_code' => 'HEPB', 'vaccine_name' => 'Hepatitis B']);

        $this->assertSame($this->doctorA->id, $record->administered_by_id);
        $this->assertSame('verified', $record->verification_status);
        $this->assertFalse((bool) $record->is_historical);
    }

    // ══════════════════════════════════════════════════════════════════
    // Nothing is invented
    // ══════════════════════════════════════════════════════════════════

    public function test_a_dose_number_route_and_site_that_were_not_recorded_stay_absent(): void
    {
        $response = $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', [
            'vaccine_code' => 'BCG',
            'vaccine_name' => 'BCG',
        ])->assertStatus(201);

        // Not 1, not 'IM', not 'left arm'.
        $response->assertJsonPath('data.dose_number', null)
            ->assertJsonPath('data.dose_sequence', null)
            ->assertJsonPath('data.route', null)
            ->assertJsonPath('data.site', null)
            ->assertJsonPath('data.lot_number', null)
            ->assertJsonPath('data.manufacturer', null);

        $record = ImmunizationRecord::findOrFail($response->json('data.id'));
        $this->assertNull($record->dose_number);
        $this->assertNull($record->route);
        $this->assertNull($record->site);
    }

    public function test_an_adverse_reaction_outcome_that_was_not_recorded_stays_absent(): void
    {
        $record = $this->dose();

        $response = $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/adverse-reactions', [
            'severity'    => 'mild',
            'description' => 'Low-grade fever the evening after the dose.',
        ])->assertStatus(201);

        // Never 'unknown': that is a clinician's positive statement.
        $response->assertJsonPath('data.outcome', null)
            ->assertJsonPath('data.onset_timing', null)
            ->assertJsonPath('data.action_taken', null)
            ->assertJsonPath('data.reported_to_authority', false);

        $reaction = AdverseReactionNote::findOrFail($response->json('data.id'));
        $this->assertNull($reaction->outcome);
        // The reporter is the token's clinician, never the body's.
        $this->assertSame($this->doctorA->id, $reaction->reported_by_id);
    }

    public function test_the_adverse_reaction_count_says_null_when_nobody_asked(): void
    {
        // The duplicate response serialises a record fetched WITHOUT the
        // adverseReactions relation, which is the one reachable place the
        // "we did not ask" reading is returned.
        $first = $this->dose(['dose_number' => 1]);

        $duplicate = $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', [
            'vaccine_code' => 'BCG',
            'vaccine_name' => 'BCG',
            'dose_number'  => 1,
        ])->assertStatus(409)->assertJsonPath('error_code', 'DUPLICATE_DOSE');

        $duplicate->assertJsonPath('existing_id', $first->id)
            ->assertJsonPath('existing_dose.adverse_reaction_count', null);

        // And 0 only where the question was actually put.
        $this->asA('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations')
            ->assertOk()
            ->assertJsonPath('data.0.adverse_reaction_count', 0);

        $this->asA('GET', '/api/mobile/doctor/immunizations/' . $first->id)
            ->assertOk()
            ->assertJsonPath('data.adverse_reaction_count', 0)
            ->assertJsonPath('data.adverse_reactions', []);
    }

    public function test_a_reported_reaction_is_counted_on_the_card(): void
    {
        $record = $this->dose();

        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/adverse-reactions', [
            'severity'    => 'moderate',
            'description' => 'Persistent crying and local swelling.',
            'outcome'     => 'recovering',
        ])->assertStatus(201);

        $this->asA('GET', '/api/mobile/doctor/immunizations/' . $record->id)
            ->assertOk()
            ->assertJsonPath('data.adverse_reaction_count', 1)
            ->assertJsonPath('data.adverse_reactions.0.outcome', 'recovering');
    }

    public function test_a_severe_reaction_says_out_loud_that_the_authority_report_is_still_a_human_step(): void
    {
        $record = $this->dose();

        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/adverse-reactions', [
            'severity'    => 'severe',
            'description' => 'Anaphylaxis within minutes; adrenaline given.',
        ])->assertStatus(201)
            ->assertJsonPath('notice', __('doctor_immunizations.authority_report_pending'));
    }

    public function test_a_dose_that_was_not_given_must_say_why(): void
    {
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', [
            'vaccine_code' => 'OPV',
            'vaccine_name' => 'Oral polio vaccine',
            'status'       => 'not_done',
        ])->assertStatus(422)->assertJsonPath('error_code', 'NOT_DONE_REASON_REQUIRED');

        $this->assertDatabaseCount('immunization_records', 0);
    }

    public function test_a_dose_cannot_be_born_retracted(): void
    {
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', [
            'vaccine_code' => 'OPV',
            'vaccine_name' => 'Oral polio vaccine',
            'status'       => 'entered_in_error',
        ])->assertStatus(422);

        $this->assertDatabaseCount('immunization_records', 0);
    }

    public function test_a_vaccine_cannot_have_been_given_in_the_future(): void
    {
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', [
            'vaccine_code'    => 'OPV',
            'vaccine_name'    => 'Oral polio vaccine',
            'administered_at' => now()->addDay()->toIso8601String(),
        ])->assertStatus(422)->assertJsonPath('error_code', 'ADMINISTERED_IN_FUTURE');

        $this->assertDatabaseCount('immunization_records', 0);
    }

    // ══════════════════════════════════════════════════════════════════
    // No enumeration, no cross-patient reads
    // ══════════════════════════════════════════════════════════════════

    public function test_a_patient_resolves_only_by_an_exact_health_id(): void
    {
        $prefix = substr(self::HEALTH_ID, 0, 12);

        $this->asA('GET', '/api/mobile/doctor/patients/' . $prefix . '/immunizations')
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'NOT_FOUND');

        $this->asA('POST', '/api/mobile/doctor/patients/' . $prefix . '/immunizations', [
            'vaccine_code' => 'BCG', 'vaccine_name' => 'BCG',
        ])->assertStatus(404);

        $this->asA('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations')->assertOk();
    }

    public function test_a_dose_belonging_to_a_patient_this_clinician_cannot_open_answers_404(): void
    {
        $record = $this->dose();

        $stranger       = $this->professional('doctor');
        $strangerFacility = Facility::factory()->create();
        $strangerBearer = app(ProviderSessionService::class)->mint($stranger, $strangerFacility->id, 'immu-stranger');

        foreach ([
            ['GET',  '/api/mobile/doctor/immunizations/' . $record->id, []],
            ['POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void', ['reason' => 'not mine']],
            ['POST', '/api/mobile/doctor/immunizations/' . $record->id . '/adverse-reactions', ['severity' => 'mild', 'description' => 'x']],
        ] as [$method, $path, $body]) {
            // 404, not 403: confirming the id exists would already say this
            // patient was vaccinated.
            $this->withHeaders(['Authorization' => "Bearer {$strangerBearer}"])
                ->json($method, $path, $body)
                ->assertStatus(404)
                ->assertJsonPath('error_code', 'NOT_FOUND');
        }

        $this->assertSame('completed', $record->refresh()->status);
        $this->assertDatabaseCount('adverse_reaction_notes', 0);
    }

    public function test_the_card_carries_doses_from_every_facility(): void
    {
        $mine = $this->dose(['vaccine_code' => 'BCG', 'vaccine_name' => 'BCG']);

        $theirs = ImmunizationRecord::create([
            'patient_id'      => $this->patient->id,
            'facility_id'     => $this->facilityB->id,
            'vaccine_code'    => 'OPV',
            'vaccine_name'    => 'Oral polio vaccine',
            'administered_at' => now()->subMonth(),
            'status'          => 'completed',
        ]);

        $ids = $this->asA('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations')
            ->assertOk()
            ->json('data.*.id');

        $this->assertContains($mine->id, $ids);
        $this->assertContains($theirs->id, $ids, 'A card that stopped at the facility door would not be a card.');
    }

    // ══════════════════════════════════════════════════════════════════
    // Void — amendment, not deletion
    // ══════════════════════════════════════════════════════════════════

    public function test_voiding_retracts_the_row_and_keeps_it(): void
    {
        $record = $this->dose();

        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void', [
            'reason' => 'Recorded on the wrong child.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'entered_in_error')
            ->assertJsonPath('data.is_voided', true)
            ->assertJsonPath('data.void_reason', 'Recorded on the wrong child.')
            // A retraction is not a refusal, and must never render as one.
            ->assertJsonPath('data.not_done_reason', null);

        $this->assertDatabaseHas('immunization_records', [
            'id'         => $record->id,
            'status'     => 'entered_in_error',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseCount('immunization_records', 1);
    }

    public function test_voiding_requires_a_reason(): void
    {
        $record = $this->dose();

        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void')
            ->assertStatus(422);

        $this->assertSame('completed', $record->refresh()->status);
    }

    public function test_only_the_recording_facility_may_void(): void
    {
        $record = $this->dose();

        // Facility B can read this chart, so the refusal is about authorship
        // and is told plainly — 403, not 404.
        $this->asB('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void', [
            'reason' => 'I think this is wrong.',
        ])->assertStatus(403)->assertJsonPath('error_code', 'NOT_RECORDING_FACILITY');

        $this->assertSame('completed', $record->refresh()->status);
    }

    public function test_a_voided_dose_cannot_be_voided_again(): void
    {
        $record = $this->dose();

        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void', ['reason' => 'Wrong child.'])->assertOk();
        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void', ['reason' => 'Wrong child again.'])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'ALREADY_VOIDED');

        // The first reason stands; a second void did not overwrite it.
        $this->assertSame('Wrong child.', $record->refresh()->not_done_reason);
    }

    public function test_there_is_no_update_and_no_delete_route_for_a_dose(): void
    {
        $record = $this->dose();

        foreach (['DELETE', 'PUT', 'PATCH'] as $method) {
            $status = $this->asA($method, '/api/mobile/doctor/immunizations/' . $record->id)->status();
            $this->assertContains($status, [404, 405], "{$method} on a dose answered {$status}");
        }

        $this->assertDatabaseHas('immunization_records', ['id' => $record->id, 'deleted_at' => null, 'status' => 'completed']);
    }

    public function test_a_voided_dose_leaves_the_clinicians_card_unless_asked_for(): void
    {
        $record = $this->dose();
        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void', ['reason' => 'Wrong child.'])->assertOk();

        $this->asA('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations')
            ->assertOk()
            ->assertJsonPath('meta.count', 0)
            ->assertJsonPath('meta.include_voided', false);

        // A clinician checking why a dose vanished sees it was retracted.
        $this->asA('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations?include_voided=1')
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.is_voided', true);
    }

    public function test_a_voided_dose_disappears_from_the_patients_own_card(): void
    {
        $record = $this->dose(['vaccine_name' => 'BCG']);

        $token = 'pat_' . Str::random(40);
        PatientAccessToken::create([
            'patient_id'   => $this->patient->id,
            'token_hash'   => Hash::make($token),
            'token_prefix' => substr($token, 0, 12),
            'expires_at'   => now()->addDays(30),
        ]);

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/mobile/immunizations')
            ->assertOk()
            ->assertJsonCount(1, 'immunizations');

        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void', ['reason' => 'Wrong child.'])->assertOk();

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/mobile/immunizations')
            ->assertOk()
            ->assertJsonCount(0, 'immunizations');
    }

    public function test_a_reaction_cannot_be_attached_to_a_voided_dose(): void
    {
        $record = $this->dose();
        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/void', ['reason' => 'Wrong child.'])->assertOk();

        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/adverse-reactions', [
            'severity'    => 'mild',
            'description' => 'There was no dose.',
        ])->assertStatus(409)->assertJsonPath('error_code', 'DOSE_VOIDED');

        $this->assertDatabaseCount('adverse_reaction_notes', 0);
    }

    public function test_an_aefi_may_be_filed_by_a_facility_that_did_not_give_the_dose(): void
    {
        $record = $this->dose();

        // A reaction presents wherever the patient goes.
        $this->asB('POST', '/api/mobile/doctor/immunizations/' . $record->id . '/adverse-reactions', [
            'severity'    => 'moderate',
            'description' => 'Presented here two days later with a swollen arm.',
        ])->assertStatus(201);

        $this->assertSame($this->doctorB->id, AdverseReactionNote::sole()->reported_by_id);
    }

    // ══════════════════════════════════════════════════════════════════
    // The forward course
    // ══════════════════════════════════════════════════════════════════

    public function test_a_dose_closes_a_schedule_line_only_on_an_exact_match(): void
    {
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/vaccination-schedule', [
            'schedules' => [
                ['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 1, 'due_date' => now()->addWeek()->toDateString()],
                ['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 2, 'due_date' => now()->addMonths(2)->toDateString()],
                ['vaccine_code' => 'MEASLES', 'vaccine_name' => 'Measles', 'dose_number' => 1, 'due_date' => now()->addMonths(9)->toDateString()],
            ],
        ])->assertStatus(201)->assertJsonPath('meta.count', 3);

        $dose = $this->dose(['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 1]);

        $lines = VaccinationSchedule::where('patient_id', $this->patient->id)->get()
            ->keyBy(fn (VaccinationSchedule $s) => $s->vaccine_code . '-' . $s->dose_number);

        $this->assertSame('completed', $lines['OPV-1']->status);
        $this->assertSame($dose->id, $lines['OPV-1']->completed_by_immunization_id);

        // A different dose number and a different vaccine are untouched — the
        // controller does not guess which line the clinician meant.
        $this->assertSame('due', $lines['OPV-2']->status);
        $this->assertNull($lines['OPV-2']->completed_by_immunization_id);
        $this->assertSame('due', $lines['MEASLES-1']->status);
    }

    public function test_a_dose_with_no_dose_number_closes_no_line(): void
    {
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/vaccination-schedule', [
            'schedules' => [
                ['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 1],
            ],
        ])->assertStatus(201);

        $this->dose(['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine']);

        $this->assertSame('due', VaccinationSchedule::sole()->status);
    }

    public function test_voiding_a_dose_reopens_the_line_it_closed(): void
    {
        $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/vaccination-schedule', [
            'schedules' => [
                ['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 1, 'due_date' => now()->subWeek()->toDateString()],
            ],
        ])->assertStatus(201);

        $dose = $this->dose(['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 1]);
        $this->assertSame('completed', VaccinationSchedule::sole()->status);

        $this->asA('POST', '/api/mobile/doctor/immunizations/' . $dose->id . '/void', [
            'reason' => 'Recorded against the wrong child.',
        ])->assertOk();

        // A child must not read as covered on the strength of a retraction.
        $line = VaccinationSchedule::sole();
        $this->assertSame('due', $line->status);
        $this->assertNull($line->completed_by_immunization_id);

        // Overdue is derived from the date on read, not from a nightly job.
        $this->asA('GET', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/vaccination-schedule')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'due')
            ->assertJsonPath('data.0.is_overdue', true);
    }

    public function test_a_course_posted_twice_does_not_stack_and_never_rewrites_a_given_dose(): void
    {
        $post = fn (string $due) => $this->asA('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/vaccination-schedule', [
            'schedules' => [
                ['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 1, 'due_date' => $due],
            ],
        ]);

        $post(now()->addWeek()->toDateString())->assertStatus(201);
        $post(now()->addWeeks(3)->toDateString())->assertStatus(201);

        $this->assertSame(1, VaccinationSchedule::count());
        $this->assertSame(now()->addWeeks(3)->toDateString(), VaccinationSchedule::sole()->due_date->toDateString());

        // Now the dose is actually given, and the line closes.
        $this->dose(['vaccine_code' => 'OPV', 'vaccine_name' => 'Oral polio vaccine', 'dose_number' => 1]);
        $this->assertSame('completed', VaccinationSchedule::sole()->status);

        // Re-posting the course must not move a fact.
        $post(now()->addMonths(6)->toDateString())->assertStatus(201);

        $line = VaccinationSchedule::sole();
        $this->assertSame('completed', $line->status);
        $this->assertSame(now()->addWeeks(3)->toDateString(), $line->due_date->toDateString());
    }

    public function test_a_clinician_without_a_care_relationship_cannot_write_to_the_card(): void
    {
        $stranger       = $this->professional('doctor');
        $facility       = Facility::factory()->create();
        $strangerBearer = app(ProviderSessionService::class)->mint($stranger, $facility->id, 'immu-nostranger');

        $this->withHeaders(['Authorization' => "Bearer {$strangerBearer}"])
            ->json('POST', '/api/mobile/doctor/patients/' . self::HEALTH_ID . '/immunizations', [
                'vaccine_code' => 'BCG', 'vaccine_name' => 'BCG',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'NO_CLINICAL_RELATIONSHIP');

        $this->assertDatabaseCount('immunization_records', 0);
    }
}
