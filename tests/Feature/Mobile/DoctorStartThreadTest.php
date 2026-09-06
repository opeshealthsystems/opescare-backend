<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A clinician opens a conversation WITH a patient from the phone.
 *
 * POST /api/mobile/doctor/messages/threads { patient_id, subject, body }
 *
 * The patient must be in a care relationship with the clinician (the chart's
 * rule) and have an appointment at the token's facility for the thread to
 * hang off — a thread the clinician's own inbox could never list is refused,
 * not written. Roles without `messaging` never reach the controller.
 */
class DoctorStartThreadTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Patient $patient;
    private Appointment $appointment;

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, string> */
    private array $bearer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create(['name' => 'Bamenda Regional Hospital']);

        foreach (['doctor', 'pharmacist', 'cashier'] as $role) {
            $this->users[$role]  = $this->professional($role);
            $this->bearer[$role] = app(ProviderSessionService::class)->mint($this->users[$role], $this->facility->id, 'test');
        }

        $this->patient = Patient::factory()->create([
            'health_id'  => 'CM-HID-TEST-THREAD-0001',
            'first_name' => 'Samuel',
            'last_name'  => 'Tabi',
            'is_demo'    => (bool) config('demo.enabled'),
        ]);

        // Basis 1 for the doctor AND the delivery context: on their schedule,
        // at this facility.
        $this->appointment = Appointment::factory()->create([
            'patient_id'   => $this->patient->id,
            'facility_id'  => $this->facility->id,
            'provider_id'  => $this->users['doctor']->id,
            'scheduled_at' => now()->addDays(3),
        ]);
    }

    /** Same recipe as ProviderRoleMatrixTest. */
    private function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        return User::factory()->create([
            'name'    => match ($role) {
                'doctor'     => 'Dr Ngozi Mbah',
                'pharmacist' => 'Paul Pharma',
                default      => ucfirst($role) . ' Staff',
            },
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }

    private function start(string $role, array $body): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer[$role]}"])
            ->postJson('/api/mobile/doctor/messages/threads', $body);
    }

    private function goodBody(?string $patientId = null): array
    {
        return [
            'patient_id' => $patientId ?? $this->patient->id,
            'subject'    => 'About your glucose result',
            'body'       => 'Hello Samuel, your fasting glucose came back a little high. Please keep to the plan we discussed and book a follow-up.',
        ];
    }

    public function test_a_clinician_opens_a_conversation_with_a_patient_on_their_schedule(): void
    {
        $response = $this->start('doctor', $this->goodBody())->assertCreated();

        $response
            ->assertJsonPath('data.kind', 'patient')
            ->assertJsonPath('data.subject', 'About your glucose result')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.patient_id', $this->patient->id)
            ->assertJsonPath('data.patient_name', 'Samuel Tabi')
            ->assertJsonPath('data.patient_health_id', 'CM-HID-TEST-THREAD-0001')
            ->assertJsonPath('data.facility_id', $this->facility->id)
            ->assertJsonPath('data.provider_id', $this->users['doctor']->id)
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.messages.0.is_mine', true)
            ->assertJsonPath('data.messages.0.body', $this->goodBody()['body']);

        $threadId = $response->json('data.id');
        $this->assertIsString($threadId);

        $thread = MessageThread::findOrFail($threadId);
        $this->assertSame('patient_provider', $thread->thread_type);
        $this->assertSame('appointment', $thread->context_type);
        $this->assertSame((string) $this->appointment->id, (string) $thread->context_id);
        $this->assertSame($this->users['doctor']->id, $thread->created_by);
        $this->assertSame('About your glucose result', $thread->title);

        // Both sides are on it: the clinician as provider, the patient under
        // the identity the patient app reads (patients.id).
        $participants = MessageThreadParticipant::where('thread_id', $thread->id)->get();
        $this->assertTrue($participants->contains(fn ($p) => $p->user_id === $this->users['doctor']->id && $p->role_in_thread === 'provider'));
        $this->assertTrue($participants->contains(fn ($p) => $p->user_id === $this->patient->id && $p->role_in_thread === 'patient'));

        // It reaches the clinician's own inbox through the same two gates.
        $this->withHeaders(['Authorization' => "Bearer {$this->bearer['doctor']}"])
            ->getJson('/api/mobile/doctor/messages/threads')
            ->assertOk()
            ->assertJsonPath('data.0.id', $threadId)
            ->assertJsonPath('data.0.patient_id', $this->patient->id);

        // Opening the thread is a patient-scoped disclosure and was logged.
        $this->assertDatabaseHas('access_logs', [
            'patient_id' => $this->patient->id,
            'actor_id'   => $this->users['doctor']->id,
            'purpose'    => 'clinical_messaging',
        ]);
    }

    public function test_the_patients_linked_account_is_added_alongside_when_there_is_one(): void
    {
        $linked = User::factory()->create([
            'is_demo'    => (bool) config('demo.enabled'),
            'patient_id' => $this->patient->id,
        ]);

        $threadId = $this->start('doctor', $this->goodBody())->assertCreated()->json('data.id');

        $this->assertDatabaseHas('message_thread_participants', [
            'thread_id'      => $threadId,
            'user_id'        => $linked->id,
            'role_in_thread' => 'patient',
        ]);
    }

    public function test_a_role_without_messaging_is_refused_before_anything_is_written(): void
    {
        // Billing holds no `messaging` capability (config/staff_capabilities.php).
        $this->start('cashier', $this->goodBody())
            ->assertForbidden()
            ->assertJsonPath('error_code', 'role_forbidden');

        $this->assertSame(0, MessageThread::count());
    }

    public function test_a_pharmacist_holds_messaging_and_is_judged_on_the_care_relationship_instead(): void
    {
        // Pharmacy IS in the `messaging` capability, so the role gate lets the
        // pharmacist through; what decides is the care relationship. With the
        // patient booked at this facility (a live facility episode) it holds —
        // the chart's rule, applied to a write.
        $this->start('pharmacist', $this->goodBody())
            ->assertCreated()
            ->assertJsonPath('data.kind', 'patient');

        // A patient with no tie to the facility is refused on the relationship,
        // not the role.
        $stranger = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        $this->start('pharmacist', $this->goodBody($stranger->id))
            ->assertForbidden()
            ->assertJsonPath('error_code', 'NO_CLINICAL_RELATIONSHIP');

        $this->assertSame(1, MessageThread::count());
    }

    public function test_a_patient_with_no_care_relationship_is_refused(): void
    {
        $stranger = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        $this->start('doctor', $this->goodBody($stranger->id))
            ->assertForbidden()
            ->assertJsonPath('error_code', 'NO_CLINICAL_RELATIONSHIP')
            ->assertJsonPath('required_action', 'request_consent');

        $this->assertSame(0, MessageThread::count());

        // The refusal is recorded, best effort.
        $this->assertDatabaseHas('access_logs', [
            'patient_id'  => $stranger->id,
            'actor_id'    => $this->users['doctor']->id,
            'access_type' => 'denied',
        ]);
    }

    public function test_a_relationship_with_no_appointment_at_this_facility_is_refused_honestly(): void
    {
        // Basis 3 (treating clinician) holds — but there is nothing for the
        // thread to hang off, so the clinician's inbox could never list it.
        $treated = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        LabOrder::factory()->create([
            'patient_id'  => $treated->id,
            'facility_id' => $this->facility->id,
            'ordered_by'  => $this->users['doctor']->id,
        ]);

        $this->start('doctor', $this->goodBody($treated->id))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'MESSAGE_CONTEXT_UNAVAILABLE')
            ->assertJsonPath('basis', 'treating_clinician');

        $this->assertSame(0, MessageThread::count());
    }

    public function test_an_appointment_at_another_facility_does_not_carry_the_thread(): void
    {
        // The doctor sees this patient — at a facility the token does not name.
        $elsewhere = Facility::factory()->create();
        $other     = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        Appointment::factory()->create([
            'patient_id'  => $other->id,
            'facility_id' => $elsewhere->id,
            'provider_id' => $this->users['doctor']->id,
        ]);

        $this->start('doctor', $this->goodBody($other->id))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'MESSAGE_CONTEXT_UNAVAILABLE');

        $this->assertSame(0, MessageThread::count());
    }

    public function test_an_unknown_patient_is_not_found(): void
    {
        $this->start('doctor', $this->goodBody((string) Str::uuid()))
            ->assertNotFound()
            ->assertJsonPath('error_code', 'NOT_FOUND');
    }

    public function test_the_body_is_validated(): void
    {
        $this->start('doctor', ['patient_id' => $this->patient->id, 'subject' => 'No body'])->assertStatus(422);
        $this->start('doctor', ['patient_id' => 'not-a-uuid', 'subject' => 'x', 'body' => 'y'])->assertStatus(422);

        $long = $this->goodBody();
        $long['subject'] = str_repeat('s', 121);
        $this->start('doctor', $long)->assertStatus(422);

        $this->assertSame(0, MessageThread::count());
    }

    public function test_a_patient_session_cannot_open_a_clinician_thread(): void
    {
        $this->postJson('/api/mobile/doctor/messages/threads', $this->goodBody())->assertUnauthorized();
    }
}
