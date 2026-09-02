<?php

namespace Tests\Feature\Messaging;

use App\Models\Patient;
use App\Models\User;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PatientMessagingTest extends TestCase
{
    use RefreshDatabase;

    private function makePatientUser(): User
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        return User::factory()->create(['patient_id' => $patient->id]);
    }

    private function makeThread(string $title = 'Care follow-up'): MessageThread
    {
        return MessageThread::create([
            'uuid'        => (string) Str::uuid(),
            'thread_type' => 'patient_provider',
            'title'       => $title,
            'priority'    => 'normal',
            'status'      => 'open',
            'created_by'  => (string) Str::uuid(),
            'legal_hold'  => false,
        ]);
    }

    private function addParticipant(MessageThread $thread, string $userId, string $role = 'patient'): void
    {
        MessageThreadParticipant::create([
            'thread_id'      => $thread->id,
            'user_id'        => $userId,
            'role_in_thread' => $role,
            'status'         => 'active',
        ]);
    }

    public function test_inbox_shows_threads_the_patient_participates_in(): void
    {
        $user = $this->makePatientUser();

        $mine = $this->makeThread('My visible thread');
        $this->addParticipant($mine, $user->id);

        $other = $this->makeThread('Someone elses thread');
        $this->addParticipant($other, (string) Str::uuid());

        $response = $this->actingAs($user)->get(route('portals.patient.messages'));

        $response->assertOk();
        $response->assertSee('My visible thread');
        $response->assertDontSee('Someone elses thread');
    }

    public function test_viewing_a_non_participant_thread_returns_403(): void
    {
        $user = $this->makePatientUser();

        $foreign = $this->makeThread('Not yours');
        $this->addParticipant($foreign, (string) Str::uuid());

        $this->actingAs($user)
            ->get(route('portals.patient.messages.show', $foreign->uuid))
            ->assertForbidden();
    }

    public function test_participant_can_view_thread(): void
    {
        $user = $this->makePatientUser();
        $thread = $this->makeThread('Visible');
        $this->addParticipant($thread, $user->id);

        // Seed an encrypted message from the provider.
        app(MessagingService::class)->sendMessage($thread->uuid, (string) Str::uuid(), 'Hello from your doctor');

        $response = $this->actingAs($user)->get(route('portals.patient.messages.show', $thread->uuid));

        $response->assertOk();
        $response->assertSee('Hello from your doctor');
    }

    public function test_posting_a_reply_creates_a_message_in_the_thread(): void
    {
        $user = $this->makePatientUser();
        $thread = $this->makeThread();
        $this->addParticipant($thread, $user->id);

        $response = $this->actingAs($user)->post(
            route('portals.patient.messages.send', $thread->uuid),
            ['body' => 'Thank you, doctor.']
        );

        $response->assertRedirect(route('portals.patient.messages.show', $thread->uuid));

        $this->assertCount(1, $thread->fresh()->messages);

        $message = $thread->fresh()->messages->first();
        $this->assertSame($user->id, $message->sender_id);
        $this->assertSame(
            'Thank you, doctor.',
            app(MessagingService::class)->decryptBody($message->body)
        );
    }

    public function test_non_participant_cannot_post_a_reply(): void
    {
        $user = $this->makePatientUser();
        $thread = $this->makeThread();
        $this->addParticipant($thread, (string) Str::uuid());

        $this->actingAs($user)->post(
            route('portals.patient.messages.send', $thread->uuid),
            ['body' => 'I should not be able to send this.']
        )->assertForbidden();

        $this->assertCount(0, $thread->fresh()->messages);
    }
}
