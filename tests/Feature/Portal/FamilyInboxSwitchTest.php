<?php
namespace Tests\Feature\Portal;

use App\Http\Controllers\MedicalId\PatientNotificationController;
use App\Models\Facility;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Services\MessagingService;
use App\Support\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Messages and notifications follow the guardian switch like every other
 * patient page: while a guardian has a dependent open, the inbox IS the
 * dependent's — never the guardian's own, never a merge of the two.
 *
 * A read-only link reads and nothing else (a flash, never a 500); a full link
 * may reply, start a thread and mark read, and the audit trail names the
 * guardian as the actor on the dependent's record. Read state and preferences
 * are per PERSON: a guardian marking read never touches the dependent's own
 * account, and a dependent's preferences are shown but never changed here.
 *
 * Threads reach a patient under two id conventions (users.id on the web,
 * patients.id from the mobile app and for account-less dependents); the
 * fixtures cover both.
 */
class FamilyInboxSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function portalSession(?string $viewing = null): array
    {
        $s = ['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'];
        if ($viewing) {
            $s['guardian_viewing_patient_id'] = $viewing;
        }
        return $s;
    }

    /** @return array{0: User, 1: Patient, 2: Patient, 3: FamilyLink} guardian, own patient, dependent (no account), link */
    private function guardianWithOwnRecordAnd(string $accessLevel): array
    {
        $own       = Patient::factory()->create(['is_demo' => false, 'first_name' => 'Guardian', 'last_name' => 'Self']);
        $guardian  = User::factory()->create(['patient_id' => $own->id, 'name' => 'Grace Guardian']);
        $dependent = Patient::factory()->create(['is_demo' => false, 'first_name' => 'Demo', 'last_name' => 'Child', 'date_of_birth' => '2017-03-12']);
        $link      = FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'access_level'         => $accessLevel,
            'status'               => 'active',
        ]);
        return [$guardian, $own, $dependent, $link];
    }

    /** A thread whose patient-side participant is $participantId (a users.id or a patients.id). */
    private function threadFor(string $participantId, string $title): MessageThread
    {
        $thread = MessageThread::create([
            'uuid'        => (string) Str::uuid(),
            'thread_type' => 'patient_care_team',
            'title'       => $title,
            'priority'    => 'normal',
            'status'      => 'open',
            'created_by'  => (string) Str::uuid(),
            'legal_hold'  => false,
        ]);
        MessageThreadParticipant::create([
            'thread_id'      => $thread->id,
            'user_id'        => $participantId,
            'role_in_thread' => 'patient',
            'status'         => 'active',
        ]);
        return $thread;
    }

    private function notifyFor(string $recipientId, string $message): void
    {
        Notify::toUser($recipientId, 'lab_result_ready', 'lab_results', ['message' => $message]);
    }

    // ── Full access: the dependent's inbox, and actions attributed to the guardian ──

    public function test_full_guardian_switched_sees_the_dependents_threads_not_their_own(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('full');

        // Mobile convention: participant keyed by the patient id.
        $this->threadFor($dependent->id, 'Clinic follow-up for the child');
        // Web convention: an account linked to the dependent (an adult dependent would have one).
        $dependentAccount = User::factory()->create(['patient_id' => $dependent->id]);
        $this->threadFor($dependentAccount->id, 'Vaccination reminder thread');
        // The guardian's OWN conversation must not leak into the dependent's inbox.
        $this->threadFor($guardian->id, 'My own private conversation');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.messages'));

        $response->assertOk();
        $response->assertSee('Clinic follow-up for the child');
        $response->assertSee('Vaccination reminder thread');
        $response->assertDontSee('My own private conversation');
        $response->assertSee('You are viewing Demo Child');
        $response->assertSee('conversations with their care team');
        $response->assertDontSee('always belong to your own account');
    }

    public function test_full_guardian_switched_sees_the_dependents_notifications_not_their_own(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('full');

        $this->notifyFor($dependent->id, 'The child lab result is ready');
        $this->notifyFor($guardian->id, 'Guardian own appointment reminder');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.notifications'));

        $response->assertOk();
        $response->assertSee('The child lab result is ready');
        $response->assertDontSee('Guardian own appointment reminder');
        $response->assertViewHas('unread', 1);
        $response->assertSee('Demo’s notifications');
    }

    public function test_full_guardian_replies_on_the_dependents_thread_and_is_recorded_as_the_actor(): void
    {
        [$guardian, , $dependent, $link] = $this->guardianWithOwnRecordAnd('full');
        $thread = $this->threadFor($dependent->id, 'Clinic follow-up');
        app(MessagingService::class)->sendMessage($thread->uuid, (string) Str::uuid(), 'Hello from the clinic');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.messages.send', $thread->uuid), ['body' => 'Please confirm the appointment']);

        $response->assertRedirect(route('portals.patient.messages.show', $thread->uuid));
        $response->assertSessionHas('success');

        $sent = $thread->fresh()->messages->sortBy('id')->last();
        $this->assertSame($guardian->id, $sent->sender_id, 'The message is signed by the person who wrote it — the guardian.');
        $this->assertSame('Please confirm the appointment', app(MessagingService::class)->decryptBody($sent->body));

        $this->assertDatabaseHas('audit_events', [
            'actor_id'    => $guardian->id,
            'patient_id'  => $dependent->id,
            'action_type' => 'message_sent',
        ]);
        $audit = DB::table('audit_events')->where('action_type', 'message_sent')->first();
        $this->assertStringContainsString($link->id, (string) $audit->after_state);

        // The guardian reads the thread back: the clinic's message is inbound, theirs is labelled as theirs.
        $page = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.messages.show', $thread->uuid));
        $page->assertOk();
        $page->assertSee('Hello from the clinic');
        $page->assertSee('Please confirm the appointment');
        $page->assertSee('data-section="on-behalf-note"', false);
        $this->assertDatabaseHas('audit_events', [
            'actor_id'    => $guardian->id,
            'patient_id'  => $dependent->id,
            'action_type' => 'message_thread_view',
        ]);
    }

    public function test_full_guardian_starts_a_thread_that_lands_in_the_dependents_inbox(): void
    {
        [$guardian, $own, $dependent] = $this->guardianWithOwnRecordAnd('full');

        $facility = Facility::factory()->create();
        $dependent->update(['facility_id' => $facility->id]);
        $doctor = User::factory()->create(['primary_facility_id' => $facility->id]);
        $doctor->role_id = Role::firstOrCreate(['name' => 'doctor'], ['description' => 'Doctor'])->id;
        $doctor->save();

        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.messages.compose'))
            ->assertOk()
            ->assertSee('data-section="on-behalf-note"', false);

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.messages.store'), [
                'title' => 'Question about the vaccination schedule',
                'body'  => 'Which dose is next?',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $thread = MessageThread::where('title', 'Question about the vaccination schedule')->firstOrFail();
        // Addressed to the dependent (no account → the patient id), NOT to the guardian's own inbox.
        $this->assertDatabaseHas('message_thread_participants', ['thread_id' => $thread->id, 'user_id' => $dependent->id, 'role_in_thread' => 'patient']);
        $this->assertDatabaseHas('message_thread_participants', ['thread_id' => $thread->id, 'user_id' => $doctor->id]);
        $this->assertDatabaseMissing('message_thread_participants', ['thread_id' => $thread->id, 'user_id' => $guardian->id]);
        $this->assertSame($guardian->id, $thread->messages->first()->sender_id);
        $this->assertDatabaseHas('audit_events', [
            'actor_id'    => $guardian->id,
            'patient_id'  => $dependent->id,
            'action_type' => 'message_thread_created',
        ]);

        // Visible in the dependent's inbox, absent from the guardian's own.
        $this->actingAs($guardian)->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.messages'))->assertSee('Question about the vaccination schedule');
        // The test session persists across requests: drop the switch explicitly
        // (withSession() only adds keys) so this really is the own inbox.
        $this->flushSession();
        $this->actingAs($guardian)->withSession($this->portalSession())
            ->get(route('portals.patient.messages'))->assertDontSee('Question about the vaccination schedule');
    }

    public function test_full_guardian_marks_the_dependents_notifications_read_without_touching_either_account(): void
    {
        [$guardian, , $dependent, $link] = $this->guardianWithOwnRecordAnd('full');
        $dependentAccount = User::factory()->create(['patient_id' => $dependent->id, 'notifications_seen_at' => null]);
        $this->notifyFor($dependent->id, 'The child lab result is ready');

        $response = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.notifications.read'));

        $response->assertRedirect(route('portals.patient.notifications'));
        $response->assertSessionHas('success');

        // Per person: neither the guardian's own watermark nor the dependent's account moved.
        $this->assertNull($guardian->fresh()->notifications_seen_at);
        $this->assertNull($dependentAccount->fresh()->notifications_seen_at);
        $this->assertDatabaseHas('audit_events', [
            'actor_id'    => $guardian->id,
            'patient_id'  => $dependent->id,
            'action_type' => PatientNotificationController::MARK_READ_ACTION,
        ]);

        // ...and the guardian's view of the dependent's inbox is now caught up.
        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.notifications'))
            ->assertOk()
            ->assertViewHas('unread', 0);

        // A later notification is new again.
        $this->travelTo(now()->addMinutes(2));
        $this->notifyFor($dependent->id, 'A second result is ready');
        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.notifications'))
            ->assertViewHas('unread', 1);
    }

    // ── Read-only access: read everything, change nothing ───────────────────

    public function test_read_only_guardian_reads_but_cannot_reply_or_start_a_thread(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('read_only');
        $thread = $this->threadFor($dependent->id, 'Clinic follow-up for the child');
        app(MessagingService::class)->sendMessage($thread->uuid, (string) Str::uuid(), 'Hello from the clinic');

        $inbox = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.messages'));
        $inbox->assertOk();
        $inbox->assertSee('Clinic follow-up for the child');
        $inbox->assertSee('data-section="read-only-note"', false);
        $inbox->assertSee('data-section="compose-disabled"', false);

        $page = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.messages.show', $thread->uuid));
        $page->assertOk();
        $page->assertSee('Hello from the clinic');
        $page->assertSee('data-section="composer-disabled"', false);
        $page->assertSee('read-only: you can read these conversations');

        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.messages.send', $thread->uuid), ['body' => 'I should not be able to send this'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertCount(1, $thread->fresh()->messages, 'Nothing was appended for a read-only guardian.');

        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.messages.compose'))
            ->assertOk()
            ->assertSee('data-section="composer-disabled"', false);

        $threadsBefore = MessageThread::count();
        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.messages.store'), ['title' => 'Nope', 'body' => 'Nope'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame($threadsBefore, MessageThread::count());
        $this->assertDatabaseMissing('audit_events', ['actor_id' => $guardian->id, 'action_type' => 'message_sent']);
        $this->assertDatabaseMissing('audit_events', ['actor_id' => $guardian->id, 'action_type' => 'message_thread_created']);
    }

    public function test_read_only_guardian_reads_notifications_but_cannot_mark_them_read(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('read_only');
        $this->notifyFor($dependent->id, 'The child lab result is ready');

        $page = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.notifications'));
        $page->assertOk();
        $page->assertSee('The child lab result is ready');
        $page->assertSee('data-section="read-only-note"', false);
        $page->assertSee('data-section="mark-read-disabled"', false);
        $page->assertDontSee('action="' . route('portals.patient.notifications.read') . '"', false);

        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.notifications.read'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($guardian->fresh()->notifications_seen_at);
        $this->assertDatabaseMissing('audit_events', ['action_type' => PatientNotificationController::MARK_READ_ACTION]);
        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.notifications'))
            ->assertViewHas('unread', 1);
    }

    // ── Preferences are per person ──────────────────────────────────────────

    public function test_preferences_are_shown_read_only_and_never_saved_while_switched(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('full');
        $dependentAccount = User::factory()->create(['patient_id' => $dependent->id]);
        DB::table('notification_preferences')->insert([
            'user_id' => $dependentAccount->id, 'category' => 'lab_results',
            'email_enabled' => false, 'sms_enabled' => false, 'whatsapp_enabled' => false,
            'push_enabled' => true, 'voice_enabled' => false, 'dashboard_enabled' => true,
            'language' => 'fr', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $page = $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.notifications.preferences'));
        $page->assertOk();
        $page->assertSee('data-section="prefs-read-only-note"', false);
        $page->assertSee('data-section="prefs-disabled"', false);
        $page->assertDontSee('Save preferences');
        $page->assertViewHas('language', 'fr');
        $page->assertViewHas('prefs', fn ($prefs) => $prefs['lab_results']['email'] === false && $prefs['lab_results']['push'] === true);

        // Even a FULL link cannot change another person's preferences.
        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->post(route('portals.patient.notifications.preferences.update'), ['language' => 'en', 'prefs' => ['general' => ['email' => 1]]])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $guardian->id]);
        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $dependentAccount->id, 'category' => 'general']);
        $this->assertDatabaseHas('notification_preferences', ['user_id' => $dependentAccount->id, 'category' => 'lab_results', 'language' => 'fr']);
    }

    // ── No switch: the own inbox is unchanged ───────────────────────────────

    public function test_without_a_switch_the_own_inbox_still_works(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('full');
        $mine  = $this->threadFor($guardian->id, 'My own private conversation');
        $this->threadFor($dependent->id, 'Clinic follow-up for the child');
        $this->notifyFor($guardian->id, 'Guardian own appointment reminder');
        $this->notifyFor($dependent->id, 'The child lab result is ready');

        $inbox = $this->actingAs($guardian)->withSession($this->portalSession())->get(route('portals.patient.messages'));
        $inbox->assertOk();
        $inbox->assertSee('My own private conversation');
        $inbox->assertDontSee('Clinic follow-up for the child');
        $inbox->assertDontSee('data-section="read-only-note"', false);

        $this->actingAs($guardian)->withSession($this->portalSession())
            ->post(route('portals.patient.messages.send', $mine->uuid), ['body' => 'Thank you, doctor.'])
            ->assertRedirect(route('portals.patient.messages.show', $mine->uuid))
            ->assertSessionHas('success');
        $this->assertSame($guardian->id, $mine->fresh()->messages->first()->sender_id);
        $this->assertDatabaseMissing('audit_events', ['action_type' => 'message_sent']);

        $notifications = $this->actingAs($guardian)->withSession($this->portalSession())->get(route('portals.patient.notifications'));
        $notifications->assertOk();
        $notifications->assertSee('Guardian own appointment reminder');
        $notifications->assertDontSee('The child lab result is ready');
        $notifications->assertViewHas('unread', 1);

        $this->actingAs($guardian)->withSession($this->portalSession())
            ->post(route('portals.patient.notifications.read'))
            ->assertRedirect(route('portals.patient.notifications'))
            ->assertSessionHas('success');
        $this->assertNotNull($guardian->fresh()->notifications_seen_at);
        $this->assertDatabaseMissing('audit_events', ['action_type' => PatientNotificationController::MARK_READ_ACTION]);

        $this->actingAs($guardian)->withSession($this->portalSession())
            ->get(route('portals.patient.notifications.preferences'))
            ->assertOk()
            ->assertSee('Save preferences')
            ->assertDontSee('data-section="prefs-disabled"', false);
    }

    public function test_switched_guardian_cannot_open_their_own_thread_by_url(): void
    {
        [$guardian, , $dependent] = $this->guardianWithOwnRecordAnd('full');
        $mine = $this->threadFor($guardian->id, 'My own private conversation');

        $this->actingAs($guardian)
            ->withSession($this->portalSession($dependent->id))
            ->get(route('portals.patient.messages.show', $mine->uuid))
            ->assertForbidden();
    }
}
