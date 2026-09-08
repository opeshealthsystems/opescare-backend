<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\PreconsultAnswer;
use App\Models\PreconsultSubmission;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The pre-consult questionnaire, end to end.
 *
 * What is under test is mostly what this surface REFUSES to do:
 *
 *   • it never confirms another patient's appointment exists — someone else's
 *     booking is a 404, identical to one that was never made;
 *   • it never stores an answer the definition does not contain, and never
 *     substitutes a nearest match for one;
 *   • it never renders an unanswered question as an answer, and never turns a
 *     blank allergy field into "no known allergies";
 *   • every answer it hands a clinician carries `patient_reported`, so a
 *     sentence a patient typed can never be read as a finding;
 *   • a clinician with no care relationship is refused, and a draft the
 *     patient has not sent is invisible to everyone but them.
 */
class PreconsultQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    private Facility $facility;

    private Appointment $appointment;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create(['name' => 'Preconsult Health Centre']);

        $this->patient = Patient::factory()->create([
            'health_id' => 'CM-HID-TEST-PREC-0001',
            'is_demo'   => (bool) config('demo.enabled'),
        ]);

        $this->appointment = Appointment::factory()->create([
            'patient_id'   => $this->patient->id,
            'facility_id'  => $this->facility->id,
            'status'       => 'scheduled',
            'scheduled_at' => now()->addDays(3),
        ]);

        $this->headers = $this->bearerFor($this->patient);
    }

    // ══════════════════════════════════════════════════════════════════
    // Fetching the questionnaire
    // ══════════════════════════════════════════════════════════════════

    public function test_the_patient_fetches_the_questionnaire_for_their_own_appointment(): void
    {
        $response = $this->patientGet();

        $response->assertOk();

        $this->assertSame('general_intake', $response->json('data.questionnaire.key'));
        $this->assertSame('Before your appointment', $response->json('data.questionnaire.title'));
        $this->assertTrue($response->json('data.window.open'));

        // Nothing started yet, and the payload says so rather than implying a
        // blank form was filled in.
        $this->assertNull($response->json('data.submission'));

        // Short on purpose.
        $questions = $response->json('data.questionnaire.questions');
        $this->assertCount(7, $questions);
        $this->assertLessThanOrEqual(8, count($questions), 'The intake grew long enough to be abandoned.');

        // Every question is offered as optional, in words, on the question.
        foreach ($questions as $question) {
            $this->assertTrue($question['optional']);
            $this->assertSame('Optional', $question['optional_hint']);
        }

        // The clinical minimum is covered.
        $keys = array_column($questions, 'key');
        foreach (['presenting_complaint', 'symptom_duration', 'current_medicines', 'allergy_status', 'pregnancy_status'] as $expected) {
            $this->assertContains($expected, $keys);
        }
    }

    public function test_every_string_is_translated_not_hardcoded(): void
    {
        $response = $this->patientGet(['Accept-Language' => 'fr-FR']);

        $response->assertOk();

        $this->assertSame('Avant votre rendez-vous', $response->json('data.questionnaire.title'));
        $this->assertSame('Facultatif', $response->json('data.questionnaire.questions.0.optional_hint'));
        $this->assertSame(
            'Quel est le principal problème pour lequel vous venez ?',
            $response->json('data.questionnaire.questions.0.label')
        );

        // The promise that care is never gated is made in the patient's own
        // language, on the form, not in a policy page.
        $this->assertStringContainsString('facultatif', $response->json('data.questionnaire.never_gated'));
    }

    // ══════════════════════════════════════════════════════════════════
    // Isolation
    // ══════════════════════════════════════════════════════════════════

    public function test_another_patients_appointment_is_a_404_not_a_403(): void
    {
        $stranger = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        $theirs = Appointment::factory()->create([
            'patient_id'   => $stranger->id,
            'facility_id'  => $this->facility->id,
            'status'       => 'scheduled',
            'scheduled_at' => now()->addDays(3),
        ]);

        foreach ([
            ['GET',  "/api/mobile/preconsult/appointments/{$theirs->id}"],
            ['PUT',  "/api/mobile/preconsult/appointments/{$theirs->id}/draft"],
            ['POST', "/api/mobile/preconsult/appointments/{$theirs->id}/submit"],
        ] as [$method, $path]) {
            $this->withHeaders($this->headers)->json($method, $path)->assertStatus(404);
        }

        // And nothing was created against their appointment.
        $this->assertSame(0, PreconsultSubmission::query()->where('appointment_id', $theirs->id)->count());
    }

    public function test_an_appointment_that_does_not_exist_answers_exactly_the_same(): void
    {
        $this->withHeaders($this->headers)
            ->getJson('/api/mobile/preconsult/appointments/' . Str::uuid()->toString())
            ->assertStatus(404)
            ->assertJson(['message' => 'We could not find that appointment.']);
    }

    public function test_the_surface_is_closed_without_a_token(): void
    {
        $this->flushHeaders()
            ->getJson("/api/mobile/preconsult/appointments/{$this->appointment->id}")
            ->assertStatus(401);
    }

    // ══════════════════════════════════════════════════════════════════
    // Drafting and submitting
    // ══════════════════════════════════════════════════════════════════

    public function test_a_draft_is_saved_and_can_be_changed_before_it_is_sent(): void
    {
        $first = $this->patientPut(['answers' => [
            'presenting_complaint' => 'Headache and fever since Sunday',
            'symptom_duration'     => 'days_4_7',
        ]]);

        $first->assertOk();
        $this->assertSame('draft', $first->json('data.status'));
        $this->assertTrue($first->json('data.editable'));
        $this->assertSame(2, $first->json('data.answered_count'));

        // Changed their mind.
        $second = $this->patientPut(['answers' => ['symptom_duration' => 'weeks_1_4']]);

        $second->assertOk();
        $this->assertSame(2, $second->json('data.answered_count'));

        $stored = PreconsultAnswer::query()
            ->whereHas('submission', fn ($q) => $q->where('appointment_id', $this->appointment->id))
            ->where('question_key', 'symptom_duration')
            ->firstOrFail();

        $this->assertSame('weeks_1_4', $stored->value_choice);
    }

    public function test_the_submission_takes_its_patient_and_facility_from_the_appointment_never_the_request(): void
    {
        $otherFacility = Facility::factory()->create(['name' => 'Somewhere Else']);
        $stranger      = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        // Every one of these is a lie the client is free to tell. None is read.
        $this->patientPut([
            'facility_id' => $otherFacility->id,
            'patient_id'  => $stranger->id,
            'answers'     => ['presenting_complaint' => 'Cough'],
        ])->assertOk();

        $submission = PreconsultSubmission::query()->where('appointment_id', $this->appointment->id)->firstOrFail();

        $this->assertSame($this->facility->id, $submission->facility_id);
        $this->assertSame($this->patient->id, $submission->patient_id);
    }

    public function test_an_answer_outside_the_definition_is_never_stored(): void
    {
        $this->patientPut(['answers' => [
            // Not a question.
            'blood_pressure' => '190/120',
            // Not one of this question's options — and NOT snapped to the
            // nearest one, which would be inventing what the patient said.
            'symptom_severity' => 'catastrophic',
            // Real.
            'presenting_complaint' => 'Chest pain',
        ]])->assertOk();

        $submission = PreconsultSubmission::query()->where('appointment_id', $this->appointment->id)->firstOrFail();
        $keys       = $submission->answers()->pluck('question_key')->all();

        $this->assertSame(['presenting_complaint'], $keys);
    }

    public function test_a_blank_allergy_answer_is_absent_and_never_reads_as_none_known(): void
    {
        $this->patientPut(['answers' => [
            'presenting_complaint' => 'Rash',
            'allergy_status'       => '',
        ]])->assertOk();

        $response = $this->patientGet();
        $answers  = collect($response->json('data.submission.answers'))->pluck('question_key')->all();

        $this->assertNotContains('allergy_status', $answers, 'A blank allergy answer was stored.');
        $this->assertSame(1, $response->json('data.submission.answered_count'));
    }

    public function test_an_answer_can_be_taken_back(): void
    {
        $this->patientPut(['answers' => ['presenting_complaint' => 'Something I regret typing']])->assertOk();

        $cleared = $this->patientPut(['answers' => ['presenting_complaint' => '']]);

        $cleared->assertOk();
        $this->assertSame(0, $cleared->json('data.answered_count'));
    }

    public function test_submitting_freezes_the_answers_and_is_idempotent(): void
    {
        $this->patientPut(['answers' => ['presenting_complaint' => 'Fever']])->assertOk();

        $submitted = $this->patientPost();
        $submitted->assertOk();
        $this->assertSame('submitted', $submitted->json('data.status'));
        $this->assertFalse($submitted->json('data.editable'));
        $this->assertNotNull($submitted->json('data.submitted_at'));

        // A phone on a bad connection retries. That must not look like failure.
        $again = $this->patientPost();
        $again->assertOk();
        $this->assertSame($submitted->json('data.submitted_at'), $again->json('data.submitted_at'));

        // And a further edit is refused — 409, not 403: nothing is forbidden
        // to this person, the form is simply finished.
        $this->patientPut(['answers' => ['presenting_complaint' => 'Changed my mind']])
            ->assertStatus(409)
            ->assertJson(['error_code' => 'preconsult_already_submitted']);
    }

    public function test_a_completely_empty_questionnaire_may_be_submitted(): void
    {
        $response = $this->patientPost();

        $response->assertOk();
        $this->assertSame('submitted', $response->json('data.status'));
        $this->assertSame(0, $response->json('data.answered_count'));
    }

    public function test_every_stored_answer_is_marked_patient_reported(): void
    {
        $this->patientPut(['answers' => [
            'presenting_complaint' => 'Stomach pain',
            'allergy_status'       => 'yes',
            'allergy_details'      => 'Penicillin — I came out in a rash',
        ]])->assertOk();

        $rows = PreconsultAnswer::query()
            ->whereHas('submission', fn ($q) => $q->where('appointment_id', $this->appointment->id))
            ->get();

        $this->assertCount(3, $rows);

        foreach ($rows as $row) {
            $this->assertSame(PreconsultAnswer::PROVENANCE, $row->provenance);
        }

        // A stated allergy is a reason to ask, never an entry on the list.
        $this->assertSame(0, DB::table('allergy_records')->where('patient_id', $this->patient->id)->count());
    }

    // ══════════════════════════════════════════════════════════════════
    // The window — which closes the form, never the appointment
    // ══════════════════════════════════════════════════════════════════

    public function test_a_cancelled_appointment_closes_the_form_and_nothing_else(): void
    {
        $this->appointment->forceFill(['status' => 'cancelled', 'cancelled_at' => now()])->save();

        $show = $this->patientGet();
        $show->assertOk();
        $this->assertFalse($show->json('data.window.open'));
        $this->assertSame('appointment_not_upcoming', $show->json('data.window.reason'));

        $this->patientPut(['answers' => ['presenting_complaint' => 'x']])
            ->assertStatus(409)
            ->assertJson(['error_code' => 'preconsult_window_closed']);

        // The appointment is exactly as it was. The form did not touch it.
        $this->assertSame('cancelled', $this->appointment->fresh()->status);
    }

    // ══════════════════════════════════════════════════════════════════
    // A guardian, through the existing FamilyLink act-as mechanism
    // ══════════════════════════════════════════════════════════════════

    public function test_a_guardian_may_fill_in_a_dependents_questionnaire_and_the_record_says_who_spoke(): void
    {
        $guardianPatient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $guardianUser    = User::factory()->create([
            'patient_id' => $guardianPatient->id,
            'is_demo'    => (bool) config('demo.enabled'),
        ]);

        \App\Models\FamilyLink::factory()->create([
            'guardian_user_id'     => $guardianUser->id,
            'dependent_patient_id' => $this->patient->id,
            'relationship'         => 'parent',
            'access_level'         => \App\Models\FamilyLink::ACCESS_FULL,
            'status'               => \App\Models\FamilyLink::STATUS_ACTIVE,
            'created_by'           => \App\Models\FamilyLink::CREATED_BY_SELF_REGISTERED,
        ]);

        $headers = $this->bearerFor($guardianPatient)
            + [\App\Http\Middleware\ActAsDependent::HEADER => $this->patient->id];

        $this->flushHeaders()->withHeaders($headers)->putJson(
            "/api/mobile/preconsult/appointments/{$this->appointment->id}/draft",
            ['answers' => ['presenting_complaint' => 'He has been coughing for three days']]
        )->assertOk();

        $submission = PreconsultSubmission::query()->where('appointment_id', $this->appointment->id)->firstOrFail();

        // The record is the CHILD's; the person who spoke is the GUARDIAN.
        $this->assertSame($this->patient->id, $submission->patient_id);
        $this->assertSame($guardianUser->id, $submission->completed_by_user_id);
    }

    // ══════════════════════════════════════════════════════════════════
    // The clinician's read
    // ══════════════════════════════════════════════════════════════════

    public function test_a_clinician_with_a_care_relationship_reads_it_marked_patient_reported(): void
    {
        $doctor = $this->professional('doctor');
        $bearer = app(ProviderSessionService::class)->mint($doctor, $this->facility->id, 'preconsult-doc');

        $this->grantConsent($this->facility->id);
        $this->appointment->forceFill(['provider_id' => $doctor->id])->save();

        $this->patientPut(['answers' => [
            'presenting_complaint' => 'Headache for a week',
            'symptom_duration'     => 'days_4_7',
        ]])->assertOk();
        $this->patientPost()->assertOk();

        $response = $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
            ->getJson("/api/mobile/doctor/patients/{$this->patient->id}/preconsult");

        $response->assertOk();

        $this->assertSame(1, $response->json('meta.count'));
        $this->assertSame('patient_reported', $response->json('data.provenance.value'));
        $this->assertSame('Reported by the patient', $response->json('data.provenance.label'));

        // Every single answer carries it too — a client that renders only the
        // list still says where this came from.
        foreach ($response->json('data.submissions.0.answers') as $answer) {
            $this->assertSame('patient_reported', $answer['provenance']['value']);
            $this->assertNotEmpty($answer['provenance']['description']);
        }

        // The choice comes back as both a token and the label it was chosen
        // under — never the token alone rendered as prose.
        $duration = collect($response->json('data.submissions.0.answers'))->firstWhere('question_key', 'symptom_duration');
        $this->assertSame('days_4_7', $duration['value']);
        $this->assertSame('4 to 7 days', $duration['value_label']);
    }

    public function test_a_draft_the_patient_has_not_sent_is_invisible_to_the_clinician(): void
    {
        $doctor = $this->professional('doctor');
        $bearer = app(ProviderSessionService::class)->mint($doctor, $this->facility->id, 'preconsult-draft');

        $this->grantConsent($this->facility->id);
        $this->appointment->forceFill(['provider_id' => $doctor->id])->save();

        $this->patientPut(['answers' => ['presenting_complaint' => 'Half a sentence I have not']])->assertOk();

        $response = $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
            ->getJson("/api/mobile/doctor/patients/{$this->patient->id}/preconsult");

        $response->assertOk();
        $this->assertSame(0, $response->json('meta.count'));
        $this->assertSame([], $response->json('data.submissions'));
    }

    public function test_a_clinician_with_no_care_relationship_is_refused(): void
    {
        $stranger = $this->professional('doctor');
        $elsewhere = Facility::factory()->create(['name' => 'Unrelated Clinic']);
        $bearer   = app(ProviderSessionService::class)->mint($stranger, $elsewhere->id, 'preconsult-stranger');

        $this->patientPost()->assertOk();

        $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
            ->getJson("/api/mobile/doctor/patients/{$this->patient->id}/preconsult")
            ->assertStatus(403)
            ->assertJson(['error_code' => 'NO_CLINICAL_RELATIONSHIP']);
    }

    public function test_a_role_without_the_chart_capability_is_403_role_forbidden_never_404(): void
    {
        $pharmacist = $this->professional('pharmacist');
        $bearer     = app(ProviderSessionService::class)->mint($pharmacist, $this->facility->id, 'preconsult-rx');

        $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
            ->getJson("/api/mobile/doctor/patients/{$this->patient->id}/preconsult")
            ->assertStatus(403);
    }

    public function test_an_unknown_patient_is_a_404_on_the_clinician_read(): void
    {
        $doctor = $this->professional('doctor');
        $bearer = app(ProviderSessionService::class)->mint($doctor, $this->facility->id, 'preconsult-unknown');

        $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
            ->getJson('/api/mobile/doctor/patients/' . Str::uuid()->toString() . '/preconsult')
            ->assertStatus(404);
    }

    // ── fixtures ─────────────────────────────────────────────────────────

    private function patientGet(array $extraHeaders = []): TestResponse
    {
        return $this->flushHeaders()
            ->withHeaders($this->headers + $extraHeaders)
            ->getJson("/api/mobile/preconsult/appointments/{$this->appointment->id}");
    }

    private function patientPut(array $body): TestResponse
    {
        return $this->flushHeaders()
            ->withHeaders($this->headers)
            ->putJson("/api/mobile/preconsult/appointments/{$this->appointment->id}/draft", $body);
    }

    private function patientPost(array $body = []): TestResponse
    {
        return $this->flushHeaders()
            ->withHeaders($this->headers)
            ->postJson("/api/mobile/preconsult/appointments/{$this->appointment->id}/submit", $body);
    }

    /** A valid mobile bearer, exactly as MobileAuthController issues one. */
    private function bearerFor(Patient $patient): array
    {
        $raw = 'pat_' . Str::random(40);

        PatientAccessToken::create([
            'patient_id'   => $patient->id,
            'token_hash'   => Hash::make($raw),
            'token_prefix' => substr($raw, 0, 12),
            'expires_at'   => now()->addDays(30),
        ]);

        return ['Authorization' => "Bearer {$raw}"];
    }

    private function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name'          => ucfirst($role),
            'portal_prefix' => 'staff',
            'landing_route' => 'staff.dashboard',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        return User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }

    private function grantConsent(string $facilityId): void
    {
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
