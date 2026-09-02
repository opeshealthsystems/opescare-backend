<?php

namespace Tests\Feature\Messaging;

use App\Models\AuditEvent;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The staff end of patient messaging.
 *
 * The messages were always being written; nothing could read them. These tests
 * pin the two properties that matter now that something can: staff see only
 * their own facility's patients, and a staff reply lands in the very thread the
 * patient is looking at.
 */
class StaffMessagingTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name], ['description' => ucfirst($name)]);
    }

    private function makeStaff(Facility $facility, string $roleName = 'doctor'): User
    {
        $user = User::factory()->create(['primary_facility_id' => $facility->id]);
        $user->role_id = $this->role($roleName)->id;
        $user->save();

        return $user->fresh();
    }

    /** A patient of $facility, plus the User account they sign in with. */
    private function makePatientUser(Facility $facility): array
    {
        $patient = Patient::factory()->create(['is_demo' => false, 'facility_id' => $facility->id]);
        $user    = User::factory()->create(['patient_id' => $patient->id]);

        return [$patient, $user];
    }

    private function makeThread(string $title = 'Care follow-up'): MessageThread
    {
        return MessageThread::create([
            'uuid'        => (string) Str::uuid(),
            'thread_type' => 'patient_care_team',
            'title'       => $title,
            'priority'    => 'normal',
            'status'      => 'open',
            'created_by'  => (string) Str::uuid(),
            'legal_hold'  => false,
        ]);
    }

    private function addParticipant(MessageThread $thread, string $userId, string $role = 'participant'): void
    {
        MessageThreadParticipant::create([
            'thread_id'      => $thread->id,
            'user_id'        => $userId,
            'role_in_thread' => $role,
            'status'         => 'active',
        ]);
    }

    // ── Inbox scoping ────────────────────────────────────────────────────

    public function test_staff_see_only_threads_for_patients_of_their_own_facility(): void
    {
        $mine   = Facility::factory()->create();
        $theirs = Facility::factory()->create();

        $staff = $this->makeStaff($mine);

        [, $ourPatientUser]     = $this->makePatientUser($mine);
        [, $foreignPatientUser] = $this->makePatientUser($theirs);

        $ours = $this->makeThread('Chest pain follow up');
        $this->addParticipant($ours, $ourPatientUser->id, 'patient');
        $this->addParticipant($ours, $staff->id, 'care_team');

        // Same staff member is a participant here too — participation alone must
        // NOT be enough when the patient belongs to another facility.
        $foreign = $this->makeThread('Another hospitals conversation');
        $this->addParticipant($foreign, $foreignPatientUser->id, 'patient');
        $this->addParticipant($foreign, $staff->id, 'care_team');

        $response = $this->actingAs($staff)->get(route('portals.staff.messages'));

        $response->assertOk();
        $response->assertSee('Chest pain follow up');
        $response->assertDontSee('Another hospitals conversation');
    }

    public function test_opening_another_facilitys_thread_is_forbidden(): void
    {
        $mine   = Facility::factory()->create();
        $theirs = Facility::factory()->create();

        $staff = $this->makeStaff($mine);
        [, $foreignPatientUser] = $this->makePatientUser($theirs);

        $foreign = $this->makeThread('Not yours');
        $this->addParticipant($foreign, $foreignPatientUser->id, 'patient');
        $this->addParticipant($foreign, $staff->id, 'care_team');

        $this->actingAs($staff)
            ->get(route('portals.staff.messages.show', $foreign->uuid))
            ->assertForbidden();
    }

    public function test_a_non_participant_thread_is_forbidden_even_at_the_same_facility(): void
    {
        $facility = Facility::factory()->create();

        $staff = $this->makeStaff($facility);
        [, $patientUser] = $this->makePatientUser($facility);

        $thread = $this->makeThread('Someone elses conversation');
        $this->addParticipant($thread, $patientUser->id, 'patient');
        $this->addParticipant($thread, (string) Str::uuid(), 'care_team');

        // MessagePermissionService::canViewThread() is the module's own gate and
        // still applies — the facility check is added to it, not instead of it.
        $this->actingAs($staff)
            ->get(route('portals.staff.messages.show', $thread->uuid))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('portals.staff.messages'))
            ->assertOk()
            ->assertDontSee('Someone elses conversation');
    }

    // ── Reading and replying ─────────────────────────────────────────────

    public function test_staff_can_open_a_thread_and_read_the_patients_message(): void
    {
        $facility = Facility::factory()->create();
        $staff    = $this->makeStaff($facility);
        [, $patientUser] = $this->makePatientUser($facility);

        $thread = $this->makeThread('Lab result question');
        $this->addParticipant($thread, $patientUser->id, 'patient');
        $this->addParticipant($thread, $staff->id, 'care_team');

        app(MessagingService::class)->sendMessage($thread->uuid, $patientUser->id, 'My results look strange.');

        $this->actingAs($staff)
            ->get(route('portals.staff.messages.show', $thread->uuid))
            ->assertOk()
            ->assertSee('My results look strange.');
    }

    public function test_a_staff_reply_lands_in_the_thread_the_patient_sees(): void
    {
        $facility = Facility::factory()->create();
        $staff    = $this->makeStaff($facility);
        [, $patientUser] = $this->makePatientUser($facility);

        $thread = $this->makeThread('Prescription refill');
        $this->addParticipant($thread, $patientUser->id, 'patient');
        $this->addParticipant($thread, $staff->id, 'care_team');

        $this->actingAs($staff)
            ->post(route('portals.staff.messages.send', $thread->uuid), [
                'body' => 'Come in on Tuesday and we will renew it.',
            ])
            ->assertRedirect(route('portals.staff.messages.show', $thread->uuid));

        $message = Message::where('thread_id', $thread->id)->latest('id')->firstOrFail();

        $this->assertSame($staff->id, $message->sender_id);
        // Bodies are KMS-encrypted at rest, so the row must not hold plaintext.
        $this->assertNotSame('Come in on Tuesday and we will renew it.', $message->body);

        // The patient, in their own portal, sees the same thread and the reply.
        $this->actingAs($patientUser)
            ->get(route('portals.patient.messages.show', $thread->uuid))
            ->assertOk()
            ->assertSee('Come in on Tuesday and we will renew it.');
    }

    public function test_replying_to_another_facilitys_thread_is_forbidden(): void
    {
        $mine   = Facility::factory()->create();
        $theirs = Facility::factory()->create();

        $staff = $this->makeStaff($mine);
        [, $foreignPatientUser] = $this->makePatientUser($theirs);

        $foreign = $this->makeThread('Not yours');
        $this->addParticipant($foreign, $foreignPatientUser->id, 'patient');
        $this->addParticipant($foreign, $staff->id, 'care_team');

        $this->actingAs($staff)
            ->post(route('portals.staff.messages.send', $foreign->uuid), ['body' => 'hello'])
            ->assertForbidden();

        $this->assertSame(0, Message::where('thread_id', $foreign->id)->count());
    }

    // ── Audit ────────────────────────────────────────────────────────────

    public function test_reading_a_thread_is_audited_against_the_patient(): void
    {
        $facility = Facility::factory()->create();
        $staff    = $this->makeStaff($facility);
        [$patient, $patientUser] = $this->makePatientUser($facility);

        $thread = $this->makeThread('Audit me');
        $this->addParticipant($thread, $patientUser->id, 'patient');
        $this->addParticipant($thread, $staff->id, 'care_team');

        $this->actingAs($staff)->get(route('portals.staff.messages.show', $thread->uuid))->assertOk();

        $event = AuditEvent::where('action_type', 'staff_message_thread_view')->first();

        $this->assertNotNull($event, 'opening a patient thread must emit an AuditEvent');
        $this->assertSame($staff->id, $event->actor_id);
        $this->assertSame($facility->id, $event->facility_id);
        $this->assertSame($patient->id, $event->patient_id);
        $this->assertSame($thread->uuid, $event->resource_id);
    }

    public function test_sending_a_reply_is_audited(): void
    {
        $facility = Facility::factory()->create();
        $staff    = $this->makeStaff($facility);
        [$patient, $patientUser] = $this->makePatientUser($facility);

        $thread = $this->makeThread('Audit the reply');
        $this->addParticipant($thread, $patientUser->id, 'patient');
        $this->addParticipant($thread, $staff->id, 'care_team');

        $this->actingAs($staff)->post(route('portals.staff.messages.send', $thread->uuid), ['body' => 'Noted.']);

        $event = AuditEvent::where('action_type', 'staff_message_reply_sent')->first();

        $this->assertNotNull($event);
        $this->assertSame($patient->id, $event->patient_id);
        $this->assertSame($facility->id, $event->facility_id);
    }
}
