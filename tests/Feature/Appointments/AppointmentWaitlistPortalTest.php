<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentWaitlist;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\Notifications\Models\NotificationEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The appointment waitlist as the two portals expose it:
 *
 *   PATIENT — joins, sees only their own entries, leaves their own (never
 *   anyone else's: 404), and a read-only guardian is refused on every write.
 *   STAFF   — sees only the session facility's queue, urgent first; a
 *   foreign entry does not exist for the booking prefill (404).
 *   CANCEL  — freeing a slot moves the next-in-line entry to `notified` and
 *   raises one in-app notification for that patient.
 */
class AppointmentWaitlistPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: Patient, 1: User} */
    private function patientUser(string $healthId = 'OC-WL-001'): array
    {
        $patient = Patient::factory()->create(['health_id' => $healthId, 'is_demo' => false]);
        $user    = User::factory()->create(['patient_id' => $patient->id]);

        return [$patient, $user];
    }

    /** @return array{0: User, 1: Facility} a professional posted at one facility */
    private function staff(string $roleName = 'receptionist', ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'hospital']);

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

    private function entry(Patient $patient, Facility $facility, array $overrides = []): AppointmentWaitlist
    {
        return AppointmentWaitlist::create(array_merge([
            'patient_id'       => $patient->id,
            'facility_id'      => $facility->id,
            'appointment_type' => 'consultation',
            'urgency'          => 'routine',
            'status'           => 'waiting',
        ], $overrides));
    }

    // ── Patient ─────────────────────────────────────────────────────────────

    public function test_patient_joins_and_sees_only_their_own_entries(): void
    {
        [$patient, $user] = $this->patientUser();
        $facility = Facility::factory()->create(['name' => 'Hôpital Laquintinie']);

        $this->actingAs($user)
            ->from(route('portals.patient.waitlist'))
            ->post(route('portals.patient.waitlist.join'), [
                'facility_id'             => $facility->id,
                'appointment_type'        => 'consultation',
                'preferred_earliest_date' => now()->addDays(2)->toDateString(),
                'preferred_latest_date'   => now()->addDays(20)->toDateString(),
                'urgency'                 => 'urgent',
                'notes'                   => 'MY-WAITLIST-NOTE',
            ])
            ->assertRedirect(route('portals.patient.waitlist'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointment_waitlists', [
            'patient_id'  => $patient->id,
            'facility_id' => $facility->id,
            'urgency'     => 'urgent',
            'status'      => 'waiting',
        ]);

        // Someone else's entry at the same facility must not show.
        $other = Patient::factory()->create(['health_id' => 'OC-WL-002', 'is_demo' => false]);
        $this->entry($other, $facility, ['notes' => 'NOT-MINE-MARKER']);

        $this->actingAs($user)
            ->get(route('portals.patient.waitlist'))
            ->assertOk()
            ->assertSee('MY-WAITLIST-NOTE')
            ->assertSee('Hôpital Laquintinie')
            ->assertSee(route('portals.patient.waitlist'), false) // sidebar link
            ->assertDontSee('NOT-MINE-MARKER');
    }

    public function test_appointments_page_offers_the_waitlist(): void
    {
        [, $user] = $this->patientUser();

        $this->actingAs($user)
            ->get(route('portals.patient.appointments'))
            ->assertOk()
            ->assertSee(route('portals.patient.waitlist'), false);
    }

    public function test_latest_date_before_earliest_is_rejected(): void
    {
        [, $user] = $this->patientUser();
        $facility = Facility::factory()->create();

        $this->actingAs($user)
            ->from(route('portals.patient.waitlist'))
            ->post(route('portals.patient.waitlist.join'), [
                'facility_id'             => $facility->id,
                'appointment_type'        => 'consultation',
                'preferred_earliest_date' => now()->addDays(10)->toDateString(),
                'preferred_latest_date'   => now()->addDays(3)->toDateString(),
                'urgency'                 => 'routine',
            ])
            ->assertRedirect(route('portals.patient.waitlist'))
            ->assertSessionHasErrors('preferred_latest_date');

        $this->assertSame(0, AppointmentWaitlist::count());
    }

    public function test_patient_cancels_own_entry_without_deleting_it(): void
    {
        [$patient, $user] = $this->patientUser();
        $facility = Facility::factory()->create();
        $entry    = $this->entry($patient, $facility);

        $this->actingAs($user)
            ->post(route('portals.patient.waitlist.cancel', $entry->id))
            ->assertRedirect(route('portals.patient.waitlist'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointment_waitlists', ['id' => $entry->id, 'status' => 'cancelled', 'deleted_at' => null]);
    }

    public function test_patient_cannot_cancel_another_patients_entry(): void
    {
        [, $user] = $this->patientUser();
        $other    = Patient::factory()->create(['health_id' => 'OC-WL-003', 'is_demo' => false]);
        $entry    = $this->entry($other, Facility::factory()->create());

        $this->actingAs($user)
            ->post(route('portals.patient.waitlist.cancel', $entry->id))
            ->assertNotFound();

        $this->assertSame('waiting', $entry->fresh()->status);
    }

    public function test_read_only_guardian_is_refused_on_writes(): void
    {
        $guardian  = User::factory()->create(['patient_id' => null]);
        $dependent = Patient::factory()->create(['health_id' => 'OC-WL-DEP', 'is_demo' => false]);
        $facility  = Facility::factory()->create();

        FamilyLink::create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'relationship'         => 'parent',
            'access_level'         => 'read_only',
            'status'               => 'active',
            'created_by'           => 'self_registered',
        ]);

        $this->actingAs($guardian)
            ->withSession(['guardian_viewing_patient_id' => $dependent->id])
            ->from(route('portals.patient.waitlist'))
            ->post(route('portals.patient.waitlist.join'), [
                'facility_id'      => $facility->id,
                'appointment_type' => 'consultation',
                'urgency'          => 'routine',
            ])
            ->assertRedirect(route('portals.patient.waitlist'))
            ->assertSessionHas('error');

        $this->assertSame(0, AppointmentWaitlist::count());

        $entry = $this->entry($dependent, $facility);
        $this->actingAs($guardian)
            ->withSession(['guardian_viewing_patient_id' => $dependent->id])
            ->from(route('portals.patient.waitlist'))
            ->post(route('portals.patient.waitlist.cancel', $entry->id))
            ->assertRedirect(route('portals.patient.waitlist'))
            ->assertSessionHas('error');

        $this->assertSame('waiting', $entry->fresh()->status);
    }

    // ── Staff ───────────────────────────────────────────────────────────────

    public function test_staff_sees_only_their_facilitys_entries_urgent_first(): void
    {
        [$clerk, $mine]  = $this->staff();
        $theirs          = Facility::factory()->create();

        $routine = Patient::factory()->create(['health_id' => 'OC-ROUTINE', 'is_demo' => false]);
        $urgent  = Patient::factory()->create(['health_id' => 'OC-URGENT', 'is_demo' => false]);
        $foreign = Patient::factory()->create(['health_id' => 'OC-FOREIGN', 'is_demo' => false]);

        $this->entry($routine, $mine, ['urgency' => 'routine', 'created_at' => now()->subDay()]);
        $this->entry($urgent, $mine, ['urgency' => 'urgent']);
        $this->entry($foreign, $theirs);
        $this->entry($routine, $mine, ['status' => 'cancelled', 'notes' => 'CLOSED-MARKER']);

        $response = $this->actingAsStaff($clerk, $mine)
            ->get(route('portals.staff.appointments.waitlist'))
            ->assertOk()
            ->assertSee('OC-URGENT')
            ->assertSee('OC-ROUTINE')
            ->assertDontSee('OC-FOREIGN')
            ->assertDontSee('CLOSED-MARKER')
            ->assertSee(route('portals.staff.appointments.waitlist'), false); // sidebar link

        $this->assertLessThan(
            strpos($response->getContent(), 'OC-ROUTINE'),
            strpos($response->getContent(), 'OC-URGENT'),
            'urgent entries are listed before routine ones'
        );
    }

    public function test_booking_prefill_from_a_foreign_entry_is_404(): void
    {
        [$clerk, $mine] = $this->staff();
        $foreign        = $this->entry(Patient::factory()->create(), Facility::factory()->create());
        $own            = $this->entry(Patient::factory()->create(['health_id' => 'OC-PREFILL']), $mine);

        $this->actingAsStaff($clerk, $mine)
            ->get(route('portals.staff.appointments.create', ['waitlist' => $foreign->id, 'patient_id' => $foreign->patient_id]))
            ->assertNotFound();

        $this->actingAsStaff($clerk, $mine)
            ->get(route('portals.staff.appointments.create', ['waitlist' => $own->id, 'patient_id' => $own->patient_id]))
            ->assertOk()
            ->assertSee('OC-PREFILL')
            ->assertSee('name="waitlist_id" value="' . $own->id . '"', false);
    }

    public function test_storing_an_appointment_with_a_waitlist_id_books_the_entry(): void
    {
        [$clerk, $mine] = $this->staff();
        $patient        = Patient::factory()->create(['health_id' => 'OC-BOOKME', 'is_demo' => false]);
        $own            = $this->entry($patient, $mine, ['status' => 'notified']);

        $this->actingAsStaff($clerk, $mine)
            ->post(route('portals.staff.appointments.store'), [
                'patient_id'       => 'OC-BOOKME',
                'appointment_type' => 'general',
                'scheduled_at'     => now()->addDays(3)->format('Y-m-d H:i:s'),
                'waitlist_id'      => $own->id,
            ])
            ->assertRedirect(route('portals.staff.appointments'));

        $appointment = Appointment::where('patient_id', $patient->id)->firstOrFail();
        $own->refresh();
        $this->assertSame('booked', $own->status);
        $this->assertSame($appointment->id, $own->booked_appointment_id);
    }

    // ── Cancellation frees a slot ──────────────────────────────────────────

    public function test_staff_cancelling_an_appointment_notifies_the_next_in_line(): void
    {
        [$clerk, $mine] = $this->staff();
        [$waiting, $waitingUser] = $this->patientUser('OC-NEXT');

        $entry = $this->entry($waiting, $mine, ['urgency' => 'urgent']);

        $appointment = Appointment::factory()->create([
            'facility_id'  => $mine->id,
            'status'       => 'scheduled',
            'scheduled_at' => now()->addDays(5),
        ]);

        $this->actingAsStaff($clerk, $mine)
            ->post(route('portals.staff.appointments.cancel', $appointment->id), ['reason' => 'Doctor unavailable'])
            ->assertRedirect(route('portals.staff.appointments'));

        $this->assertSame('cancelled', $appointment->fresh()->status);

        $entry->refresh();
        $this->assertSame('notified', $entry->status);
        $this->assertNotNull($entry->notified_at);

        $this->assertSame(1, NotificationEvent::where('recipient_user_id', $waitingUser->id)
            ->where('event_type', 'waitlist_slot_opened')
            ->count());
    }

    public function test_patient_cancelling_an_appointment_notifies_the_next_in_line(): void
    {
        [$owner, $ownerUser]     = $this->patientUser('OC-OWNER');
        [$waiting, $waitingUser] = $this->patientUser('OC-NEXT2');
        $facility = Facility::factory()->create();

        $entry = $this->entry($waiting, $facility);

        $appointment = Appointment::factory()->create([
            'patient_id'   => $owner->id,
            'facility_id'  => $facility->id,
            'status'       => 'confirmed',
            'scheduled_at' => now()->addDays(4),
        ]);

        $this->actingAs($ownerUser)
            ->post(route('portals.patient.appointments.cancel', $appointment->id))
            ->assertRedirect(route('portals.patient.appointments'));

        $this->assertSame('notified', $entry->fresh()->status);
        $this->assertSame(1, NotificationEvent::where('recipient_user_id', $waitingUser->id)
            ->where('event_type', 'waitlist_slot_opened')
            ->count());
    }

    // ── Console ─────────────────────────────────────────────────────────────

    public function test_waitlist_expire_command_reports_a_count(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();
        $this->entry($patient, $facility, ['preferred_latest_date' => now()->subDays(2)->toDateString()]);
        $this->entry($patient, $facility, ['preferred_latest_date' => now()->addDays(2)->toDateString()]);

        $this->artisan('waitlist:expire')
            ->expectsOutputToContain('1')
            ->assertSuccessful();

        $this->assertSame(1, AppointmentWaitlist::where('status', 'expired')->count());
    }
}
