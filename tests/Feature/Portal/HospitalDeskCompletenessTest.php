<?php

namespace Tests\Feature\Portal;

use App\Models\Appointment;
use App\Models\AppointmentWaitlist;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\QueueTicket;
use App\Models\Role;
use App\Models\User;
use App\Services\Queue\PatientQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The hospital / clinic / health-centre desk, run end-to-end from the web:
 * no list without a way on, no irreversible action without a confirmation,
 * no KPI tile that is a dead number, no empty state without a next step, and
 * no desk reachable only by typing its URL.
 *
 * Follows docs/audits/HOSPITAL_DASHBOARD_AUDIT_2.md (#14 waitlist bounded
 * list, #15 foreign prescription id) and the clinic / health-centre audits.
 */
class HospitalDeskCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Facility} a professional posted at one facility */
    private function staff(string $roleName, ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility = $facility ?? Facility::factory()->create(['type' => 'hospital', 'name' => 'Hôpital Test ' . uniqid()]);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function as(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function patient(): Patient
    {
        return Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
    }

    private function waitlistEntry(Facility $facility, ?Patient $patient = null): AppointmentWaitlist
    {
        return AppointmentWaitlist::create([
            'patient_id' => ($patient ?? $this->patient())->id, 'facility_id' => $facility->id,
            'appointment_type' => 'general', 'urgency' => 'routine', 'status' => 'waiting',
        ]);
    }

    // ── H-14: the waitlist is paginated, not silently cut at 200 ──────────

    public function test_the_waitlist_is_paginated_and_says_how_many_are_waiting(): void
    {
        [$user, $facility] = $this->staff('receptionist');
        $patient = $this->patient();
        for ($i = 0; $i < 51; $i++) {
            $this->waitlistEntry($facility, $patient);
        }

        $page1 = $this->as($user, $facility)->get('/facility/appointments/waitlist')->assertOk();
        $page1->assertSee(__('hospital_desk.waitlist_showing', ['from' => 1, 'to' => 50, 'total' => 51]));
        $page1->assertSee('page=2');

        $this->as($user, $facility)->get('/facility/appointments/waitlist?page=2')
            ->assertOk()
            ->assertSee(__('hospital_desk.waitlist_showing', ['from' => 51, 'to' => 51, 'total' => 51]));
    }

    public function test_an_empty_waitlist_offers_to_book_and_a_row_opens_the_chart_for_a_clinician(): void
    {
        [$reception, $facility] = $this->staff('receptionist');
        $this->as($reception, $facility)->get('/facility/appointments/waitlist')
            ->assertOk()
            ->assertSee(__('hospital_desk.empty_waitlist_hint'))
            ->assertSee(route('portals.staff.appointments.create'), false);

        $patient = $this->patient();
        $this->waitlistEntry($facility, $patient);

        // The receptionist holds no clinical_chart: no chart link, book link only.
        $this->as($reception, $facility)->get('/facility/appointments/waitlist')
            ->assertOk()
            ->assertDontSee(route('portals.chart.index', ['health_id' => $patient->health_id]), false);

        [$doctor] = $this->staff('doctor', $facility);
        $this->as($doctor, $facility)->get('/facility/appointments/waitlist')
            ->assertOk()
            ->assertSee(route('portals.chart.index', ['health_id' => $patient->health_id]), false);
    }

    // ── Queue: a ticket leads on to the chart and to triage ───────────────

    public function test_a_queue_ticket_leads_to_the_chart_and_triage_for_a_nurse_but_not_for_the_front_desk(): void
    {
        [$nurse, $facility] = $this->staff('nurse');
        [$reception]        = $this->staff('receptionist', $facility);
        $patient = $this->patient();

        $ticket = app(PatientQueueService::class)->checkIn([
            'patient' => $patient, 'facility_id' => $facility->id, 'actor_id' => $reception->id,
            'priority_level' => 5, 'priority_reason' => null,
        ]);

        $chart  = route('portals.chart.index', ['health_id' => $patient->health_id]);
        $triage = route('portals.triage.create', ['health_id' => $patient->health_id]);

        $this->as($nurse, $facility)->get('/facility/queue')->assertOk()->assertSee($chart, false);
        $this->as($nurse, $facility)->get('/facility/queue/' . $ticket->id)
            ->assertOk()
            ->assertSee(__('hospital_desk.patient_next_steps'))
            ->assertSee($chart, false)
            ->assertSee($triage, false)
            ->assertSee(__('hospital_desk.confirm_queue_cancel'));

        $this->as($reception, $facility)->get('/facility/queue/' . $ticket->id)
            ->assertOk()
            ->assertDontSee($chart, false)
            ->assertDontSee($triage, false);
    }

    public function test_the_queue_terminal_actions_ask_before_they_run(): void
    {
        [$nurse, $facility] = $this->staff('nurse');
        $ticket = app(PatientQueueService::class)->checkIn([
            'patient' => $this->patient(), 'facility_id' => $facility->id, 'actor_id' => $nurse->id,
            'priority_level' => 5, 'priority_reason' => null,
        ]);
        app(PatientQueueService::class)->call($ticket, (string) $nurse->id);

        // Called → no-show is offered, with its confirmation.
        $this->as($nurse, $facility)->get('/facility/queue')
            ->assertOk()
            ->assertSee(__('hospital_desk.confirm_queue_no_show'));

        app(PatientQueueService::class)->start($ticket->fresh(), (string) $nurse->id);
        $this->as($nurse, $facility)->get('/facility/queue')
            ->assertOk()
            ->assertSee(__('hospital_desk.confirm_queue_complete'));
    }

    // ── Appointment book: confirm / check-in / no-show ask first ──────────

    public function test_appointment_actions_ask_before_they_run_and_an_empty_book_offers_to_book(): void
    {
        [$user, $facility] = $this->staff('receptionist');

        $this->as($user, $facility)->get('/facility/appointments')
            ->assertOk()
            ->assertSee(__('hospital_desk.empty_appointments_action'))
            ->assertSee(route('portals.staff.appointments.create'), false);

        Appointment::factory()->create([
            'patient_id' => $this->patient()->id, 'facility_id' => $facility->id,
            'status' => 'booked', 'scheduled_at' => now()->addHour(),
        ]);

        $this->as($user, $facility)->get('/facility/appointments')
            ->assertOk()
            ->assertSee(__('hospital_desk.confirm_appt_confirm'))
            ->assertSee(__('hospital_desk.confirm_appt_check_in'));
    }

    // ── KPI tiles link where the role may go ──────────────────────────────

    public function test_landing_tiles_link_for_the_roles_that_hold_the_desk_and_stay_figures_otherwise(): void
    {
        [$reception, $facility] = $this->staff('receptionist');
        $home = $this->as($reception, $facility)->get('/facility')->assertOk();
        // front_desk → the book; billing → the billing desk; queue → the queue.
        $home->assertSee(route('portals.staff.appointments', ['date' => now()->toDateString()]), false)
            ->assertSee(route('portals.billing.index'), false)
            ->assertSee(route('portals.queue.index'), false)
            // No clinical_chart: the referral tile is a figure, not a 403 link.
            ->assertDontSee(route('portals.referrals.index', ['tab' => 'received']), false);

        [$doctor] = $this->staff('doctor', $facility);
        $this->as($doctor, $facility)->get('/facility')->assertOk()
            ->assertSee(route('portals.referrals.index', ['tab' => 'received']), false)
            ->assertDontSee(route('portals.billing.index'), false);
    }

    public function test_the_admin_overview_tiles_each_open_a_page(): void
    {
        [$admin, $facility] = $this->staff('facility_admin');
        $page = $this->as($admin, $facility)->get('/facility/team/overview')->assertOk();

        $html = $page->getContent();
        // Every KPI tile is an anchor: no stat-card <div> carries a data-kpi value.
        preg_match_all('/<div class="stat-card[ "][^>]*>\s*<div class="stat-card__label">/', $html, $deadTiles);
        $this->assertSame([], $deadTiles[0], 'Every overview KPI tile must link somewhere');

        $page->assertSee(route('portals.facility.admin.reports'), false)
            ->assertSee(route('portals.facility.admin.invoices'), false)
            ->assertSee(route('portals.queue.index'), false);
    }

    // ── Empty states say what to do next ──────────────────────────────────

    public function test_clinical_empty_states_name_the_next_action(): void
    {
        [$doctor, $facility] = $this->staff('doctor');

        $this->as($doctor, $facility)->get('/facility/triage')->assertOk()
            ->assertSee(__('hospital_desk.empty_triage_action'))
            ->assertSee(route('portals.triage.create'), false);

        $this->as($doctor, $facility)->get('/facility/referrals')->assertOk()
            ->assertSee(__('hospital_desk.empty_referrals_sent_hint'))
            ->assertSee(__('hospital_desk.empty_referrals_action'));
        $this->as($doctor, $facility)->get('/facility/referrals?tab=received')->assertOk()
            ->assertSee(__('hospital_desk.empty_referrals_received_hint'));

        $this->as($doctor, $facility)->get('/facility/prescriptions')->assertOk()
            ->assertSee(__('hospital_desk.empty_prescriber_action'))
            ->assertSee(route('portals.prescriber.create'), false);

        $this->as($doctor, $facility)->get('/facility/blood-requests')->assertOk()
            ->assertSee(__('hospital_desk.empty_blood_requests_action'));

        $this->as($doctor, $facility)->get('/facility/messages')->assertOk()
            ->assertSee(__('hospital_desk.empty_messages_hint'));

        $this->as($doctor, $facility)->get('/facility/tasks')->assertOk()
            ->assertSee(__('hospital_desk.empty_tasks_hint'));

        $this->as($doctor, $facility)->get('/facility/support')->assertOk()
            ->assertSee(__('hospital_desk.empty_support_hint'));
    }

    // ── No desk reachable only by URL: the sidebar links every role's pages ─

    public static function sidebarRoles(): array
    {
        return array_map(fn ($r) => [$r], [
            'doctor', 'nurse', 'midwife', 'triage_nurse', 'ward_nurse', 'nurse_supervisor',
            'specialist', 'resident', 'receptionist', 'records_officer',
        ]);
    }

    #[DataProvider('sidebarRoles')]
    public function test_every_operational_role_reaches_tasks_help_and_its_waitlist_from_its_landing_page(string $roleName): void
    {
        [$user, $facility] = $this->staff($roleName);
        $home = $this->as($user, $facility)->get('/facility')->assertOk();

        preg_match_all('/<a\b[^>]*href="([^"]+)"/i', $home->getContent(), $m);
        $linked = array_map(fn ($h) => parse_url($h, PHP_URL_PATH), $m[1]);

        foreach (['portals.staff.tasks', 'portals.staff.support', 'portals.staff.appointments.waitlist'] as $route) {
            $path = parse_url(route($route), PHP_URL_PATH);
            $this->assertContains($path, $linked, "{$roleName}: landing page has no link to {$path}");
            $this->as($user, $facility)->get($path)->assertOk();
        }
    }

    // ── H-15: a foreign prescription id is a 404, never a leak ────────────

    public function test_a_prescription_at_another_facility_is_a_404_on_the_prescriber_desk(): void
    {
        [$doctor, $mine]  = $this->staff('doctor');
        [, $theirs]       = $this->staff('doctor');

        $rx = app(\App\Services\Clinical\PrescriptionService::class)->issue([
            'patient_id'  => $this->patient()->id,
            'facility_id' => $theirs->id,
            'items'       => [['drug_name' => 'Paracetamol 500 mg', 'dosage' => '1 tab', 'frequency' => 'tid', 'duration_days' => 3, 'quantity' => 9]],
        ], $doctor->id);

        $this->as($doctor, $mine)->get('/facility/prescriptions/' . $rx->id)->assertNotFound();
        $this->as($doctor, $mine)->get('/facility/prescriptions/' . $rx->id . '/print')->assertNotFound();
        $this->as($doctor, $mine)->post('/facility/prescriptions/' . $rx->id . '/void', ['reason' => 'x'])->assertNotFound();
        $this->assertSame('active', $rx->fresh()->status);
    }
}
