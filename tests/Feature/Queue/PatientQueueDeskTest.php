<?php

namespace Tests\Feature\Queue;

use App\Models\Appointment;
use App\Models\AppointmentCheckIn;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\QueueStatusHistory;
use App\Models\QueueTicket;
use App\Models\Role;
use App\Models\User;
use App\Services\Queue\PatientQueueService;
use App\Services\Triage\TriageDeskService;
use App\Support\TriageAcuity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The patient queue desk — and the three ways it is allowed to lie.
 *
 *   1. THE ORDER. A queue that prints a priority and then hands the nurse the
 *      arrival order is worse than no queue. The P1 triaged at 09:30 must sit
 *      above the P5 who arrived at 09:00, and the test pins `checked_in_at`
 *      so a tie can never make it pass by luck. It also asserts that the
 *      arrival order is DIFFERENT from the ranked order: a sort that happens
 *      to agree with insertion proves nothing.
 *
 *   2. THE TIMESTAMPS. Every measured wait in this feature is a difference
 *      between two timestamps written once. A re-call that moved `called_at`
 *      would shorten the wait on paper. It must not.
 *
 *   3. THE HISTORY. Append-only: every transition is a row, including the
 *      re-call, and nothing is edited.
 */
class PatientQueueDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** A professional posted at one facility, exactly as the middleware leaves one. */
    private function staff(string $roleName = 'receptionist', ?Facility $facility = null): array
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

    private function actingAsStaff(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function patient(): Patient
    {
        return Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
    }

    /** Check a walk-in in through the service, at a pinned instant. */
    private function ticketAt(string $when, Patient $patient, Facility $facility, User $actor, int $priority = 5): QueueTicket
    {
        Carbon::setTestNow(Carbon::parse($when));

        $ticket = app(PatientQueueService::class)->checkIn([
            'patient'         => $patient,
            'facility_id'     => $facility->id,
            'actor_id'        => $actor->id,
            'priority_level'  => $priority,
            'priority_reason' => $priority === 5 ? null : 'test',
        ]);

        Carbon::setTestNow();

        return $ticket;
    }

    // ── Check-in ──────────────────────────────────────────────────────────

    public function test_a_receptionist_checks_a_walk_in_in_and_a_ticket_and_history_row_are_written(): void
    {
        [$user, $facility] = $this->staff('receptionist');
        $patient = $this->patient();

        $this->actingAsStaff($user, $facility)
            ->post('/facility/queue', ['patient_id' => $patient->id, 'priority_level' => 5])
            ->assertRedirect(route('portals.queue.index'));

        $ticket = QueueTicket::where('patient_id', $patient->id)->firstOrFail();

        $this->assertSame($facility->id, $ticket->facility_id);
        $this->assertSame('waiting', $ticket->status);
        $this->assertSame(5, (int) $ticket->priority_level);
        $this->assertNotNull($ticket->checked_in_at);
        $this->assertNull($ticket->called_at);
        $this->assertMatchesRegularExpression('/^\d{6}-\d{3}$/', $ticket->queue_number);
        $this->assertSame('001', PatientQueueService::displayNumber($ticket));

        // A walk-in gets a visit at THIS facility, so a later triage lands on
        // the same encounter and can outrank the arrival priority.
        $this->assertNotNull($ticket->visit_id);
        $this->assertDatabaseHas('visits', ['id' => $ticket->visit_id, 'facility_id' => $facility->id, 'patient_id' => $patient->id]);

        $history = QueueStatusHistory::where('queue_ticket_id', $ticket->id)->get();
        $this->assertCount(1, $history);
        $this->assertNull($history[0]->from_status);
        $this->assertSame('waiting', $history[0]->to_status);
        $this->assertSame($user->id, $history[0]->changed_by);

        // The second walk-in of the day takes the next serial, and the same
        // patient cannot be queued twice.
        $second = $this->patient();
        $this->actingAsStaff($user, $facility)
            ->post('/facility/queue', ['patient_id' => $second->id])
            ->assertRedirect();
        $this->assertSame('002', PatientQueueService::displayNumber(QueueTicket::where('patient_id', $second->id)->firstOrFail()));

        $this->actingAsStaff($user, $facility)
            ->post('/facility/queue', ['patient_id' => $patient->id])
            ->assertRedirect(route('portals.queue.index'))
            ->assertSessionHas('error', __('queue_desk.refusal.already_in_queue'));
        $this->assertSame(1, QueueTicket::where('patient_id', $patient->id)->count());
    }

    public function test_checking_a_booking_in_writes_the_appointment_check_in_and_checks_the_booking_in(): void
    {
        [$user, $facility] = $this->staff('receptionist');
        $patient = $this->patient();

        $appointment = Appointment::factory()->create([
            'patient_id'   => $patient->id,
            'facility_id'  => $facility->id,
            'status'       => 'booked',
            'scheduled_at' => now()->addHour(),
        ]);

        $this->actingAsStaff($user, $facility)
            ->post('/facility/queue', ['appointment_id' => $appointment->id])
            ->assertRedirect(route('portals.queue.index'));

        $ticket = QueueTicket::where('appointment_id', $appointment->id)->firstOrFail();
        $appointment->refresh();

        $this->assertSame('checked_in', $appointment->status);
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertNotNull($appointment->visit_id);
        $this->assertSame($appointment->visit_id, $ticket->visit_id);

        $checkIn = AppointmentCheckIn::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame('checked_in', $checkIn->status);
        $this->assertSame($facility->id, $checkIn->facility_id);
        $this->assertSame($user->id, $checkIn->checked_in_by);
    }

    public function test_an_arrival_priority_above_standard_requires_a_reason(): void
    {
        [$user, $facility] = $this->staff('receptionist');

        $this->actingAsStaff($user, $facility)
            ->from('/facility/queue/check-in')
            ->post('/facility/queue', ['patient_id' => $this->patient()->id, 'priority_level' => 3])
            ->assertSessionHasErrors('priority_reason');

        $this->assertSame(0, QueueTicket::count());
    }

    // ── The order ─────────────────────────────────────────────────────────

    public function test_a_p1_triaged_later_is_listed_before_a_p5_who_arrived_earlier(): void
    {
        [$desk, $facility] = $this->staff('receptionist');
        [$nurse]           = $this->staff('triage_nurse', $facility);

        // Arrival order, pinned: A 09:00 (standard), B 09:30 (standard, then
        // triaged P1), C 09:45 (courtesy lane, arrival 3).
        $a = $this->ticketAt('2026-09-14 09:00:00', $this->patient(), $facility, $desk, 5);
        $b = $this->ticketAt('2026-09-14 09:30:00', $this->patient(), $facility, $desk, 5);
        $c = $this->ticketAt('2026-09-14 09:45:00', $this->patient(), $facility, $desk, 3);

        $triage = app(TriageDeskService::class);
        $triage->record([
            'visit'                => $b->visit,
            'nurse_id'             => $nurse->id,
            'nurse_role'           => 'triage_nurse',
            'presenting_complaint' => 'Chest pain',
            'pain_score'           => 8,
            'pregnancy_status'     => null,
            'acuity'               => TriageAcuity::P1,
            'vitals'               => [],
        ]);

        $ranked  = app(PatientQueueService::class)->ranked($facility->id)->pluck('id')->all();
        $arrival = [$a->id, $b->id, $c->id];

        $this->assertSame([$b->id, $c->id, $a->id], $ranked, 'The P1 must come first, then the courtesy lane, then the earliest standard arrival.');

        // The mutation check: if the ORDER BY were dropped or reversed, the
        // rows would come back in arrival (insertion) order, which is a
        // different list. This assertion is what makes the one above mean
        // something.
        $this->assertNotSame($arrival, $ranked, 'Ranked order coincides with arrival order — the sort is not doing anything.');

        // A reassessment DOWN moves the patient down, live: the queue reads
        // the derived priority, never a stored one.
        $record = \App\Models\TriageRecord::where('visit_id', $b->visit_id)->firstOrFail();
        $triage->reassess($record, [
            'reassessed_by' => $nurse->id,
            'role'          => 'triage_nurse',
            'new_priority'  => TriageAcuity::P5,
            'reason'        => 'Pain resolved, ECG normal',
            'pain_score'    => 1,
            'vitals'        => [],
        ]);

        $this->assertSame(
            [$c->id, $a->id, $b->id],
            app(PatientQueueService::class)->ranked($facility->id)->pluck('id')->all(),
            'After re-triage to P5, B ranks with the standards and, having arrived after A, below A.'
        );

        // And the screen renders them in that order.
        $html = $this->actingAsStaff($desk, $facility)->get('/facility/queue')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, $a->patient->health_id), strpos($html, $c->patient->health_id));
        $this->assertLessThan(strpos($html, $b->patient->health_id), strpos($html, $a->patient->health_id));
    }

    // ── Transitions ───────────────────────────────────────────────────────

    public function test_transitions_append_history_and_never_overwrite_a_timestamp(): void
    {
        [$user, $facility] = $this->staff('nurse');
        $service = app(PatientQueueService::class);

        $ticket = $this->ticketAt('2026-09-14 09:00:00', $this->patient(), $facility, $user);

        Carbon::setTestNow('2026-09-14 09:20:00');
        $this->actingAsStaff($user, $facility)->post("/facility/queue/{$ticket->id}/call")->assertRedirect();

        // Called again ten minutes later — the patient did not hear. The
        // FIRST call is the one the wait is measured against.
        Carbon::setTestNow('2026-09-14 09:30:00');
        $this->actingAsStaff($user, $facility)->post("/facility/queue/{$ticket->id}/call")->assertRedirect();

        $ticket->refresh();
        $this->assertSame('called', $ticket->status);
        $this->assertSame('2026-09-14 09:20:00', $ticket->called_at->format('Y-m-d H:i:s'), 'A re-call overwrote called_at.');
        $this->assertSame($user->id, $ticket->assigned_to_id);

        // Complete is not allowed before the consultation has started.
        $this->actingAsStaff($user, $facility)->post("/facility/queue/{$ticket->id}/complete")
            ->assertRedirect(route('portals.queue.index'))
            ->assertSessionHas('error', __('queue_desk.refusal.not_allowed_from_called'));

        Carbon::setTestNow('2026-09-14 09:32:00');
        $this->actingAsStaff($user, $facility)->post("/facility/queue/{$ticket->id}/start")->assertRedirect();

        Carbon::setTestNow('2026-09-14 09:50:00');
        $this->actingAsStaff($user, $facility)->post("/facility/queue/{$ticket->id}/complete")->assertRedirect();

        $ticket->refresh();
        $this->assertSame('completed', $ticket->status);
        $this->assertSame('2026-09-14 09:00:00', $ticket->checked_in_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-14 09:20:00', $ticket->called_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-14 09:32:00', $ticket->service_started_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-14 09:50:00', $ticket->completed_at->format('Y-m-d H:i:s'));
        $this->assertNull($ticket->cancelled_at);

        // Measured, not asserted: 20 minutes waited, 18 in the room.
        $durations = $service->durations($ticket);
        $this->assertSame(20 * 60, $durations['waited']);
        $this->assertSame(18 * 60, $durations['served']);

        // Five rows, in order, including the re-call. Nothing was edited.
        $history = QueueStatusHistory::where('queue_ticket_id', $ticket->id)->orderBy('changed_at')->get();
        $this->assertSame(
            [[null, 'waiting'], ['waiting', 'called'], ['called', 'called'], ['called', 'service_started'], ['service_started', 'completed']],
            $history->map(fn ($h) => [$h->from_status, $h->to_status])->all()
        );

        // Closed is closed: nothing moves a completed ticket.
        $this->actingAsStaff($user, $facility)->post("/facility/queue/{$ticket->id}/call")
            ->assertSessionHas('error', __('queue_desk.refusal.not_allowed_from_completed'));
        $this->assertSame(5, QueueStatusHistory::where('queue_ticket_id', $ticket->id)->count());
    }

    public function test_no_show_only_after_a_call_and_cancel_needs_a_reason(): void
    {
        [$user, $facility] = $this->staff('receptionist');

        $ticket = $this->ticketAt('2026-09-14 09:00:00', $this->patient(), $facility, $user);

        $this->actingAsStaff($user, $facility)->post("/facility/queue/{$ticket->id}/no-show")
            ->assertSessionHas('error', __('queue_desk.refusal.not_allowed_from_waiting'));
        $this->assertSame('waiting', $ticket->fresh()->status);

        $this->actingAsStaff($user, $facility)->from("/facility/queue/{$ticket->id}")
            ->post("/facility/queue/{$ticket->id}/cancel", [])
            ->assertSessionHasErrors('reason');

        $this->actingAsStaff($user, $facility)
            ->post("/facility/queue/{$ticket->id}/cancel", ['reason' => 'Left before being called'])
            ->assertRedirect(route('portals.queue.index'));

        $ticket->refresh();
        $this->assertSame('cancelled', $ticket->status);
        $this->assertNotNull($ticket->cancelled_at);
        $this->assertSame('Left before being called', $ticket->status_reason);
        $this->assertDatabaseHas('queue_status_histories', [
            'queue_ticket_id' => $ticket->id, 'from_status' => 'waiting', 'to_status' => 'cancelled', 'reason' => 'Left before being called',
        ]);
    }

    // ── Scope ─────────────────────────────────────────────────────────────

    public function test_a_ticket_at_another_facility_does_not_exist(): void
    {
        [$mine, $myFacility]      = $this->staff('receptionist');
        [$theirs, $theirFacility] = $this->staff('receptionist');

        $ticket = $this->ticketAt('2026-09-14 09:00:00', $this->patient(), $theirFacility, $theirs);

        // Not 403 — the ticket is not this desk's to know about at all.
        $this->actingAsStaff($mine, $myFacility)->get("/facility/queue/{$ticket->id}")->assertNotFound();
        $this->actingAsStaff($mine, $myFacility)->post("/facility/queue/{$ticket->id}/call")->assertNotFound();
        $this->assertSame('waiting', $ticket->fresh()->status);

        // Absent from the queue, and its booking cannot be checked in from here.
        $this->actingAsStaff($mine, $myFacility)->get('/facility/queue')
            ->assertOk()
            ->assertSee(__('queue_desk.empty_queue'))
            ->assertDontSee($ticket->patient->health_id);

        $foreign = Appointment::factory()->create(['facility_id' => $theirFacility->id, 'status' => 'booked', 'scheduled_at' => now()]);
        $this->actingAsStaff($mine, $myFacility)->post('/facility/queue', ['appointment_id' => $foreign->id])->assertNotFound();
        $this->assertSame('booked', $foreign->fresh()->status);
    }

    public function test_a_receptionist_can_open_the_desk_and_a_labtech_is_refused_with_403(): void
    {
        [$receptionist, $facility] = $this->staff('receptionist');
        [$labtech]                 = $this->staff('labtech', $facility);

        $this->actingAsStaff($receptionist, $facility)->get('/facility/queue')->assertOk();
        $this->actingAsStaff($receptionist, $facility)->get('/facility/queue/check-in')->assertOk();

        $this->actingAsStaff($labtech, $facility)->get('/facility/queue')->assertForbidden();
        $this->actingAsStaff($labtech, $facility)
            ->post('/facility/queue', ['patient_id' => $this->patient()->id])
            ->assertForbidden();
        $this->assertSame(0, QueueTicket::count());
    }

    public function test_a_facility_admin_reaches_the_queue_but_not_the_rest_of_the_desk(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');

        $this->actingAsStaff($admin, $facility)->get('/facility/queue')->assertOk();
        $this->actingAsStaff($admin, $facility)->get('/facility')->assertRedirect();
    }

    // ── The dashboard tile and the day's figures ──────────────────────────

    public function test_the_dashboard_tile_counts_todays_active_tickets_at_this_facility_only(): void
    {
        [$user, $facility] = $this->staff('receptionist');
        [$other, $otherFacility] = $this->staff('receptionist');

        Carbon::setTestNow('2026-09-14 10:00:00');
        $today = now();

        $this->ticketAt('2026-09-14 08:00:00', $this->patient(), $facility, $user);
        $called = $this->ticketAt('2026-09-14 08:10:00', $this->patient(), $facility, $user);
        $done   = $this->ticketAt('2026-09-14 08:20:00', $this->patient(), $facility, $user);
        $this->ticketAt('2026-09-13 08:00:00', $this->patient(), $facility, $user);        // yesterday, never closed
        $this->ticketAt('2026-09-14 08:30:00', $this->patient(), $otherFacility, $other);  // elsewhere

        Carbon::setTestNow($today);
        $service = app(PatientQueueService::class);
        $service->call($called, $user->id);
        $service->start($done, $user->id);
        $service->complete($done, $user->id);

        $kpis = $this->actingAsStaff($user, $facility)->get('/facility')->assertOk()->viewData('kpis');

        $this->assertSame(2, $kpis['in_queue']);
        $this->assertSame(2, $service->summaryFor($facility->id, $today)['active']);
    }

    public function test_the_days_summary_is_a_median_of_measured_waits(): void
    {
        [$user, $facility] = $this->staff('nurse');
        $service = app(PatientQueueService::class);

        $t1 = $this->ticketAt('2026-09-14 09:00:00', $this->patient(), $facility, $user);
        $t2 = $this->ticketAt('2026-09-14 09:00:00', $this->patient(), $facility, $user);
        $t3 = $this->ticketAt('2026-09-14 09:00:00', $this->patient(), $facility, $user);
        $never = $this->ticketAt('2026-09-14 09:00:00', $this->patient(), $facility, $user);

        Carbon::setTestNow('2026-09-14 09:10:00'); $service->call($t1, $user->id);
        Carbon::setTestNow('2026-09-14 09:20:00'); $service->call($t2, $user->id);
        Carbon::setTestNow('2026-09-14 10:00:00'); $service->call($t3, $user->id);

        Carbon::setTestNow('2026-09-14 10:05:00'); $service->start($t3, $user->id);
        Carbon::setTestNow('2026-09-14 10:20:00'); $service->complete($t3, $user->id);

        $summary = $service->summaryFor($facility->id, now());

        $this->assertSame(4, $summary['checked_in']);
        $this->assertSame(1, $summary['waiting']);
        $this->assertSame(1, $summary['seen']);
        $this->assertSame(20 * 60, $summary['median_wait_seconds'], 'Median of 10, 20, 60 minutes is 20.');
        $this->assertSame(60 * 60, $summary['longest_wait_seconds']);
        $this->assertSame(15 * 60, $summary['median_service_seconds']);

        // The one never called contributes nothing to the wait figures — an
        // unmeasured wait is not a zero-minute wait.
        $this->assertNull($never->fresh()->called_at);
    }
}
