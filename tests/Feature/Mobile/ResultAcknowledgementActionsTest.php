<?php

namespace Tests\Feature\Mobile;

use App\Http\Controllers\Api\Mobile\MobileResultsInboxController;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabResultInterpretation;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Notifications\Models\NotificationEvent;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * Acknowledging a result is a clinical act with a note and a next step.
 *
 * The acknowledge body (patient note, private comment, share flag, next
 * action) lands in the immutable ledger row and — for a lab result — in ONE
 * `lab_result_interpretations` row; the patient is told their result was
 * reviewed and reads ONLY what was shared; a second acknowledgement of the
 * same content returns the first statement and writes nothing more.
 */
class ResultAcknowledgementActionsTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private const HEALTH_ID = 'CM-HID-TEST-ACK-0001';

    private Facility $facility;
    private User $clinician;
    private Patient $patient;
    private LabOrder $order;
    private string $bearer;
    private string $rowId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();

        // Same provider-session recipe as ProviderScanTest: the middleware
        // re-checks the role on every request, so the user needs a
        // professional role with a dashboard profile.
        DB::table('dashboard_profiles')->updateOrInsert(['key' => 'doctor'], [
            'name' => 'Doctor', 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $role = Role::firstOrCreate(['name' => 'doctor'], ['description' => 'Doctor']);
        $role->forceFill(['dashboard_profile_key' => 'doctor'])->save();

        $this->clinician = User::factory()->create([
            'name'    => 'Dr Amina Fon',
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $role->id,
        ]);
        $this->bearer = app(ProviderSessionService::class)->mint($this->clinician, $this->facility->id, 'test');

        $this->patient = Patient::factory()->create([
            'health_id'  => self::HEALTH_ID,
            'first_name' => 'Brenda',
            'last_name'  => 'Ekema',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);

        // A resulted order THIS clinician placed at THIS facility — the only
        // kind of row the inbox lists.
        $this->order = LabOrder::factory()->create([
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facility->id,
            'ordered_by'  => $this->clinician->id,
            'test_name'   => 'Fasting Blood Glucose',
            'test_code'   => 'GLU',
            'status'      => 'resulted',
            'ordered_at'  => now()->subDay(),
            'resulted_at' => now()->subHour(),
        ]);

        LabResult::factory()->create([
            'lab_order_id'    => $this->order->id,
            'patient_id'      => $this->patient->id,
            'parameter_name'  => 'Glucose',
            'value'           => '7.8',
            'unit'            => 'mmol/L',
            'reference_range' => '3.9 - 5.5',
            'flag'            => 'H',
            'resulted_at'     => now()->subHour(),
        ]);

        $this->rowId = MobileResultsInboxController::labRowId($this->order->id);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function asClinician(): static
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer}"]);
    }

    private function acknowledge(array $body = []): TestResponse
    {
        return $this->asClinician()->postJson("/api/mobile/doctor/results-inbox/{$this->rowId}/acknowledge", $body);
    }

    private function patientSees(): TestResponse
    {
        return $this->withHeaders($this->mobileAuthHeaders($this->patient))
            ->getJson("/api/mobile/labs/{$this->order->id}");
    }

    private function ledgerRows(): \Illuminate\Database\Eloquent\Collection
    {
        return NotificationEvent::where('event_type', MobileResultsInboxController::ACK_EVENT)
            ->where('related_resource_id', $this->rowId)
            ->get();
    }

    private function patientBells(): \Illuminate\Support\Collection
    {
        return DB::table('notifications')
            ->where('notifiable_id', $this->patient->id)
            ->get()
            ->map(fn ($row) => json_decode($row->data, true));
    }

    // ── The act ──────────────────────────────────────────────────────────

    public function test_acknowledging_with_a_shared_note_records_the_statement_and_tells_the_patient(): void
    {
        $note    = 'Your sugar is a little high. Keep to the diet plan and we will repeat the test in three months.';
        $comment = 'Fasting glucose 7.8 mmol/L, impaired. Recheck HbA1c before changing therapy.';

        $response = $this->acknowledge([
            'patient_note' => $note,
            'comment'      => $comment,
            'next_action'  => 'follow_up',
        ])->assertOk();

        // The response carries the statement and where each next step leads.
        $response
            ->assertJsonPath('data.id', $this->rowId)
            ->assertJsonPath('data.interpretation.patient_note', $note)
            ->assertJsonPath('data.interpretation.clinician_comment', $comment)
            ->assertJsonPath('data.interpretation.next_action', 'follow_up')
            ->assertJsonPath('data.interpretation.shared', true)
            ->assertJsonPath('data.next_action_links.prescribe', '/clinician/prescribe?health_id=' . self::HEALTH_ID)
            ->assertJsonPath('data.next_action_links.follow_up.patient_id', $this->patient->id)
            ->assertJsonPath('data.next_action_links.message.patient_id', $this->patient->id)
            ->assertJsonPath('data.next_action_links.message.thread_id', null)
            ->assertJsonPath('data.next_action_links.repeat_test.patient_id', $this->patient->id)
            ->assertJsonPath('data.next_action_links.repeat_test.test_code', 'GLU')
            ->assertJsonPath('data.next_action_links.repeat_test.test_name', 'Fasting Blood Glucose')
            ->assertJsonPath('data.result.kind', 'lab');

        $this->assertNotNull($response->json('data.acknowledged_at'));
        $this->assertSame($this->clinician->id, $response->json('data.acknowledged_by'));

        // The ledger row is the immutable statement, and it carries all of it.
        $events = $this->ledgerRows();
        $this->assertCount(1, $events);
        $payload = json_decode($events->first()->payload_json, true);
        $this->assertSame($note, $payload['patient_note']);
        $this->assertSame($comment, $payload['clinician_comment']);
        $this->assertTrue($payload['share_with_patient']);
        $this->assertSame('follow_up', $payload['next_action']);
        $this->assertSame($this->patient->id, $payload['patient_id']);

        // Exactly one interpretation row, tied to that ledger row and shared.
        $this->assertSame(1, LabResultInterpretation::count());
        $interpretation = LabResultInterpretation::first();
        $this->assertSame($this->order->id, $interpretation->lab_order_id);
        $this->assertSame($this->patient->id, $interpretation->patient_id);
        $this->assertSame($this->facility->id, $interpretation->facility_id);
        $this->assertSame($this->clinician->id, $interpretation->author_user_id);
        $this->assertSame($events->first()->uuid, $interpretation->acknowledgement_event_id);
        $this->assertSame('follow_up', $interpretation->next_action);
        $this->assertNotNull($interpretation->shared_at);
        $this->assertFalse($interpretation->is_demo);

        // The patient's bell: the fact of a review and the way to the result —
        // never the value, never the private comment.
        $bells = $this->patientBells();
        $this->assertCount(1, $bells);
        $bell = $bells->first();
        $this->assertSame('lab_result_reviewed', $bell['type']);
        $this->assertSame('/labs/' . $this->order->id, $bell['action_url']);
        $this->assertSame($this->order->id, $bell['lab_order_id']);
        $this->assertStringContainsString('Fasting Blood Glucose', $bell['title']);
        $this->assertTrue($bell['has_note']);
        $this->assertStringNotContainsString('7.8', $bell['title'] . $bell['message']);
        $this->assertStringNotContainsString('HbA1c', $bell['title'] . $bell['message']);

        // And the disclosure was logged as an acknowledgement.
        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $this->patient->id,
            'actor_id'   => $this->clinician->id,
            'purpose'    => 'results_inbox_acknowledgement',
        ]);
    }

    public function test_a_second_acknowledgement_returns_the_first_statement_and_writes_nothing_more(): void
    {
        $first = $this->acknowledge([
            'patient_note' => 'First note to the patient.',
            'next_action'  => 'message',
        ])->assertOk();

        // A retry with DIFFERENT wording is not a correction: the first stands.
        $second = $this->acknowledge([
            'patient_note' => 'Different wording that must not replace the first.',
            'comment'      => 'A late private comment.',
            'next_action'  => 'prescribe',
        ])->assertOk();

        $this->assertSame($first->json('data.acknowledged_at'), $second->json('data.acknowledged_at'));
        $this->assertSame('First note to the patient.', $second->json('data.interpretation.patient_note'));
        $this->assertNull($second->json('data.interpretation.clinician_comment'));
        $this->assertSame('message', $second->json('data.interpretation.next_action'));

        $this->assertCount(1, $this->ledgerRows());
        $this->assertSame(1, LabResultInterpretation::count());
        // The patient was told once, on the real acknowledgement — not again.
        $this->assertCount(1, $this->patientBells());
    }

    public function test_a_private_comment_is_never_shown_to_the_patient_but_the_review_is(): void
    {
        // Before anything happens the result reads as not yet reviewed.
        $this->patientSees()->assertOk()
            ->assertJsonPath('data.reviewed_at', null)
            ->assertJsonPath('data.interpretations', []);

        $this->acknowledge(['comment' => 'Private: borderline, consider repeating fasting.'])
            ->assertOk()
            ->assertJsonPath('data.interpretation.clinician_comment', 'Private: borderline, consider repeating fasting.')
            ->assertJsonPath('data.interpretation.patient_note', null)
            ->assertJsonPath('data.interpretation.shared', false);

        // A row exists (the clinician said something) but it is not shared.
        $this->assertSame(1, LabResultInterpretation::count());
        $this->assertNull(LabResultInterpretation::first()->shared_at);

        $seen = $this->patientSees()->assertOk();

        $seen->assertJsonPath('data.interpretations', []);
        $this->assertNotNull($seen->json('data.reviewed_at'));
        // Not filtered out of the body — never in it.
        $this->assertStringNotContainsString('borderline', $seen->getContent());
        // The pre-test indication block is untouched.
        $this->assertArrayHasKey('clinical_notes', $seen->json('data'));
    }

    public function test_the_patient_reads_a_shared_note_with_who_wrote_it(): void
    {
        $this->acknowledge([
            'patient_note' => 'All fine. No change to your treatment; see you at your next visit.',
        ])->assertOk();

        $seen = $this->patientSees()->assertOk();

        $seen->assertJsonPath('data.interpretations.0.patient_note', 'All fine. No change to your treatment; see you at your next visit.')
            ->assertJsonPath('data.interpretations.0.author_name', 'Dr Amina Fon')
            ->assertJsonPath('data.interpretations.0.author_role_key', 'doctor');
        $this->assertNotNull($seen->json('data.interpretations.0.author_role'));
        $this->assertNotNull($seen->json('data.interpretations.0.at'));
        $this->assertNotNull($seen->json('data.reviewed_at'));
        $this->assertArrayNotHasKey('clinician_comment', $seen->json('data.interpretations.0'));

        // The list carries the same two keys, so the app can badge a row.
        $list = $this->withHeaders($this->mobileAuthHeaders($this->patient))
            ->getJson('/api/mobile/labs')
            ->assertOk();

        $this->assertNotNull($list->json('data.0.reviewed_at'));
        $this->assertSame('Dr Amina Fon', $list->json('data.0.interpretations.0.author_name'));
    }

    public function test_a_plain_acknowledgement_still_works_writes_no_interpretation_and_still_tells_the_patient(): void
    {
        $this->acknowledge()
            ->assertOk()
            ->assertJsonPath('data.interpretation', null)
            ->assertJsonPath('data.next_action_links.repeat_test.test_code', 'GLU');

        $this->assertCount(1, $this->ledgerRows());
        $this->assertSame(0, LabResultInterpretation::count());

        $bells = $this->patientBells();
        $this->assertCount(1, $bells);
        $this->assertFalse($bells->first()['has_note']);

        $seen = $this->patientSees()->assertOk();
        $seen->assertJsonPath('data.interpretations', []);
        $this->assertNotNull($seen->json('data.reviewed_at'));
    }

    public function test_a_next_step_alone_is_kept_on_the_ledger_and_shown_on_the_inbox(): void
    {
        $this->acknowledge(['next_action' => 'prescribe'])
            ->assertOk()
            ->assertJsonPath('data.interpretation.next_action', 'prescribe')
            ->assertJsonPath('data.interpretation.patient_note', null)
            ->assertJsonPath('data.interpretation.shared', false);

        // Nothing was said, so there is no patient-facing row to write.
        $this->assertSame(0, LabResultInterpretation::count());

        // show() and index rows read the statement back off the ledger.
        $this->asClinician()->getJson("/api/mobile/doctor/results-inbox/{$this->rowId}")
            ->assertOk()
            ->assertJsonPath('data.interpretation.next_action', 'prescribe');

        $this->asClinician()->getJson('/api/mobile/doctor/results-inbox')
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->rowId)
            ->assertJsonPath('data.0.interpretation.next_action', 'prescribe');
    }

    public function test_show_and_index_carry_the_latest_interpretation(): void
    {
        $this->acknowledge(['patient_note' => 'Shown on the inbox too.', 'comment' => 'And the comment.'])->assertOk();

        $this->asClinician()->getJson("/api/mobile/doctor/results-inbox/{$this->rowId}")
            ->assertOk()
            ->assertJsonPath('data.interpretation.patient_note', 'Shown on the inbox too.')
            ->assertJsonPath('data.interpretation.clinician_comment', 'And the comment.')
            ->assertJsonPath('data.interpretation.shared', true);

        $this->asClinician()->getJson('/api/mobile/doctor/results-inbox?filter=acknowledged')
            ->assertOk()
            ->assertJsonPath('data.0.interpretation.patient_note', 'Shown on the inbox too.');
    }

    public function test_an_existing_open_conversation_with_the_patient_is_offered_for_the_message_step(): void
    {
        $thread = MessageThread::create([
            'uuid'        => (string) Str::uuid(),
            'thread_type' => 'patient_provider',
            'title'       => 'Earlier conversation',
            'status'      => 'open',
            'created_by'  => $this->patient->id,
        ]);
        MessageThreadParticipant::create(['thread_id' => $thread->id, 'user_id' => $this->patient->id, 'role_in_thread' => 'patient', 'status' => 'active']);
        MessageThreadParticipant::create(['thread_id' => $thread->id, 'user_id' => $this->clinician->id, 'role_in_thread' => 'provider', 'status' => 'active']);

        $this->acknowledge()
            ->assertOk()
            ->assertJsonPath('data.next_action_links.message.thread_id', (string) $thread->id);
    }

    public function test_a_malformed_body_is_refused_before_anything_is_written(): void
    {
        $this->acknowledge(['next_action' => 'phone_the_patient'])->assertStatus(422);
        $this->acknowledge(['patient_note' => str_repeat('x', 1001)])->assertStatus(422);
        $this->acknowledge(['comment' => str_repeat('x', 1001)])->assertStatus(422);

        $this->assertCount(0, $this->ledgerRows());
        $this->assertSame(0, LabResultInterpretation::count());
        $this->assertCount(0, $this->patientBells());
    }

    public function test_an_interpretation_row_is_append_only(): void
    {
        $this->acknowledge(['patient_note' => 'Set in stone.'])->assertOk();

        $row = LabResultInterpretation::first();

        $this->expectException(\LogicException::class);
        $row->update(['patient_note' => 'Rewritten.']);
    }
}
