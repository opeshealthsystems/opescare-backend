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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hospital appointment desk (/facility/appointments) — the web half of
 * "appointment request → confirm → check-in → queue".
 *
 * Found by docs/audits/HOSPITAL_DASHBOARD_AUDIT_2.md: a patient's own booking
 * from the app is written `booked`, and the web table knew only `scheduled`,
 * so the receptionist saw the request and had no button for it. Confirm
 * rewrote any status, and the web check-in went through a narrower service
 * than the queue desk's — two doors, two results for the same patient.
 */
class HospitalAppointmentDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: User, 1: Facility} */
    private function staff(string $roleName = 'receptionist', ?Facility $facility = null): array
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

    // ── The patient's own request reaches the desk with its buttons ───────

    public function test_a_booked_request_from_the_app_can_be_confirmed_and_checked_in_from_the_web(): void
    {
        [$user, $facility] = $this->staff('receptionist');
        $patient = $this->patient();

        $appointment = Appointment::factory()->create([
            'patient_id'   => $patient->id,
            'facility_id'  => $facility->id,
            'status'       => 'booked',
            'scheduled_at' => now()->addHour(),
        ]);

        // The row offers Confirm and Check in (both routes appear in the page).
        $this->as($user, $facility)->get('/facility/appointments')
            ->assertOk()
            ->assertSee(route('portals.staff.appointments.confirm', $appointment->id), false)
            ->assertSee(route('portals.staff.appointments.check-in', $appointment->id), false)
            ->assertSee($facility->name);

        $this->as($user, $facility)->post("/facility/appointments/{$appointment->id}/confirm")
            ->assertRedirect(route('portals.staff.appointments'));
        $this->assertSame('confirmed', $appointment->fresh()->status);

        // Check-in from the appointment book = the queue desk's check-in:
        // the booking is checked in, a visit opened, a ticket issued.
        $this->as($user, $facility)->post("/facility/appointments/{$appointment->id}/check-in")
            ->assertRedirect(route('portals.staff.appointments'))
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame('checked_in', $appointment->status);
        $this->assertNotNull($appointment->visit_id);

        $ticket = QueueTicket::where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame($facility->id, $ticket->facility_id);
        $this->assertSame('waiting', $ticket->status);
        $this->assertSame($appointment->visit_id, $ticket->visit_id);
    }

    public function test_confirm_and_no_show_refuse_a_closed_appointment_instead_of_rewriting_it(): void
    {
        [$user, $facility] = $this->staff('receptionist');

        $done = Appointment::factory()->create([
            'patient_id' => $this->patient()->id, 'facility_id' => $facility->id,
            'status' => 'completed', 'scheduled_at' => now()->subHour(),
        ]);

        $this->as($user, $facility)->post("/facility/appointments/{$done->id}/confirm")
            ->assertRedirect(route('portals.staff.appointments'))
            ->assertSessionHas('error', __('desk_api.not_confirm_eligible'));
        $this->assertSame('completed', $done->fresh()->status);

        $this->as($user, $facility)->post("/facility/appointments/{$done->id}/no-show")
            ->assertRedirect(route('portals.staff.appointments'))
            ->assertSessionHas('error', __('desk_api.not_no_show_eligible'));
        $this->assertSame('completed', $done->fresh()->status);

        // The table offers no action on a closed row.
        $this->as($user, $facility)->get('/facility/appointments')
            ->assertOk()
            ->assertDontSee(route('portals.staff.appointments.confirm', $done->id), false);
    }

    // ── Cross-facility: every {id} route answers 404 for a stranger's row ──

    public function test_every_appointment_id_route_is_a_404_for_another_facilitys_row(): void
    {
        [$mine, $myFacility]      = $this->staff('receptionist');
        [$theirs, $theirFacility] = $this->staff('receptionist');

        $foreign = Appointment::factory()->create([
            'patient_id' => $this->patient()->id, 'facility_id' => $theirFacility->id,
            'status' => 'booked', 'scheduled_at' => now()->addHour(),
        ]);

        $this->as($mine, $myFacility)->post("/facility/appointments/{$foreign->id}/confirm")->assertNotFound();
        $this->as($mine, $myFacility)->post("/facility/appointments/{$foreign->id}/check-in")->assertNotFound();
        $this->as($mine, $myFacility)->post("/facility/appointments/{$foreign->id}/no-show")->assertNotFound();
        $this->as($mine, $myFacility)->post("/facility/appointments/{$foreign->id}/cancel", ['reason' => 'a real reason'])->assertNotFound();

        $this->assertSame('booked', $foreign->fresh()->status);
        $this->assertSame(0, QueueTicket::count());

        // Not in the list either.
        $this->as($mine, $myFacility)->get('/facility/appointments')
            ->assertOk()
            ->assertDontSee($foreign->patient->health_id);

        // A waitlist entry of another facility cannot pre-fill a booking here.
        $entry = AppointmentWaitlist::create([
            'patient_id' => $foreign->patient_id, 'facility_id' => $theirFacility->id,
            'appointment_type' => 'general', 'urgency' => 'routine', 'status' => 'waiting',
        ]);
        $this->as($mine, $myFacility)->get('/facility/appointments/create?waitlist=' . $entry->id)->assertNotFound();
    }

    // ── The book is paginated and names people, not uuids ─────────────────

    public function test_the_book_is_paginated_and_shows_the_clinicians_name_not_a_uuid(): void
    {
        [$user, $facility] = $this->staff('receptionist');
        [$doctor]          = $this->staff('doctor', $facility);
        $patient           = $this->patient();

        Appointment::factory()->count(55)->create([
            'patient_id' => $patient->id, 'facility_id' => $facility->id,
            'provider_id' => $doctor->id, 'status' => 'scheduled',
            'scheduled_at' => now()->addDays(2),
        ]);

        $page = $this->as($user, $facility)->get('/facility/appointments')->assertOk();

        $page->assertSee($doctor->name)
            ->assertDontSeeText($doctor->id)
            ->assertSee('page=2', false)
            // The clinician filter offers this facility's clinicians by name.
            ->assertSee('<option value="' . $doctor->id . '"', false);

        // ?status= was always honoured by the controller; the form now offers it.
        $this->as($user, $facility)->get('/facility/appointments?status=cancelled')
            ->assertOk()
            ->assertSee(__('public.staff_portal.no_appointments_title'));
    }
}
