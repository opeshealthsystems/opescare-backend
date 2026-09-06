<?php

namespace Tests\Feature\Security;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The staff appointment book is one facility's book. It never honours a
 * facility_id from the URL, and its patient filter is an exact Health ID —
 * a partial value is not a search, it matches nothing.
 */
class StaffAppointmentBookScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;
    private Facility $mine;
    private Facility $theirs;
    private Patient $patient;
    private Appointment $mineAppt;
    private Appointment $theirsAppt;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('demo.enabled', false);

        DB::table('dashboard_profiles')->updateOrInsert(['key' => 'receptionist'], [
            'name' => 'Receptionist', 'portal_prefix' => 'staff', 'landing_route' => 'portals.staff',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $role = Role::firstOrCreate(['name' => 'receptionist'], ['description' => 'Receptionist']);
        $role->forceFill(['dashboard_profile_key' => 'receptionist'])->save();

        $this->mine   = Facility::factory()->create();
        $this->theirs = Facility::factory()->create();
        $this->receptionist = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $this->mine->id]);
        $this->patient = Patient::factory()->create(['health_id' => 'CM-HID-TEST-BOOK-0001']);

        foreach ([[$this->mine, 'reason-mine'], [$this->theirs, 'reason-theirs']] as [$facility, $reason]) {
            $appt = Appointment::create([
                'patient_id'       => $this->patient->id,
                'facility_id'      => $facility->id,
                'appointment_type' => 'consultation',
                'status'           => 'scheduled',
                'scheduled_at'     => now()->addDay(),
                'booked_by_type'   => 'staff',
                'booked_by_id'     => $this->receptionist->id,
                'reason'           => $reason,
            ]);
            if ($facility->is($this->mine)) { $this->mineAppt = $appt; } else { $this->theirsAppt = $appt; }
        }
    }

    private function asReceptionist()
    {
        return $this->actingAs($this->receptionist)->withSession(['active_facility_id' => $this->mine->id, 'mfa_verified' => true]);
    }

    public function test_a_facility_id_in_the_url_cannot_open_another_facilitys_book(): void
    {
        $this->asReceptionist()
            ->get(route('portals.staff.appointments', ['facility_id' => $this->theirs->id]))
            ->assertOk()
            ->assertSee($this->mineAppt->id)
            ->assertDontSee($this->theirsAppt->id);
    }

    public function test_the_patient_filter_is_an_exact_health_id_never_a_partial_match(): void
    {
        $this->asReceptionist()
            ->get(route('portals.staff.appointments', ['patient_id' => 'cm-hid-test-book-0001']))
            ->assertOk()
            ->assertSee($this->mineAppt->id);

        $this->asReceptionist()
            ->get(route('portals.staff.appointments', ['patient_id' => 'BOOK']))
            ->assertOk()
            ->assertDontSee($this->mineAppt->id);
    }

    public function test_the_booking_form_offers_no_patient_list_and_books_by_exact_health_id(): void
    {
        $this->asReceptionist()
            ->get(route('portals.staff.appointments.create'))
            ->assertOk()
            ->assertDontSee('<select name="patient_id"', false);

        $this->asReceptionist()
            ->post(route('portals.staff.appointments.store'), [
                'patient_id'       => 'cm-hid-test-book-0001',
                'appointment_type' => 'consultation',
                'scheduled_at'     => now()->addDays(2)->toDateTimeString(),
            ])
            ->assertRedirect(route('portals.staff.appointments'));

        $this->assertDatabaseHas('appointments', [
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->mine->id,
            'status'      => 'scheduled',
        ]);

        $this->asReceptionist()
            ->from(route('portals.staff.appointments.create'))
            ->post(route('portals.staff.appointments.store'), [
                'patient_id'       => 'CM-HID-NOPE-NOPE-0000',
                'appointment_type' => 'consultation',
                'scheduled_at'     => now()->addDays(2)->toDateTimeString(),
            ])
            ->assertRedirect(route('portals.staff.appointments.create'))
            ->assertSessionHasErrors('patient_id');
    }

    public function test_check_in_and_no_show_cannot_touch_another_facilitys_appointment(): void
    {
        // check-in wraps its work in try/catch and reports the miss as a flash
        // (302 back); no-show has no such wrapper and 404s. Either way nothing
        // at the other facility changes and no visit is opened there.
        $this->asReceptionist()
            ->post(route('portals.staff.appointments.check-in', $this->theirsAppt->id))
            ->assertRedirect();
        $this->asReceptionist()
            ->post(route('portals.staff.appointments.no-show', $this->theirsAppt->id))
            ->assertNotFound();
        $this->assertDatabaseHas('appointments', ['id' => $this->theirsAppt->id, 'status' => 'scheduled']);
        $this->assertDatabaseMissing('visits', ['patient_id' => $this->patient->id, 'facility_id' => $this->theirs->id]);

        $this->asReceptionist()
            ->post(route('portals.staff.appointments.no-show', $this->mineAppt->id))
            ->assertRedirect(route('portals.staff.appointments'));
        $this->assertDatabaseHas('appointments', ['id' => $this->mineAppt->id, 'status' => 'no_show']);
    }
}
