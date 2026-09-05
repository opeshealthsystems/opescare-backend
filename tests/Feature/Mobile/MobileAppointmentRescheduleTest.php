<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\AuditEvent;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

/**
 * POST /api/mobile/appointments/{id}/reschedule.
 *
 * The routes live in routes/mobile_appointments_reschedule.php, which
 * AppServiceProvider does not load yet (that one-line registration is a
 * separate change on a contended file). Registering the file here in setUp() is
 * exactly what that line will do — `Route::middleware('api')->group(...)` —
 * so these tests exercise the real route definitions, real middleware stack
 * included, and will keep passing unchanged once the provider loads it.
 */
class MobileAppointmentRescheduleTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Facility $facility;
    private CareFacility $listing;
    private User $provider;
    private Patient $patient;
    private Appointment $appointment;
    private AppointmentSlot $currentSlot;
    private AppointmentSlot $targetSlot;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->group(base_path('routes/mobile_appointments_reschedule.php'));

        $this->facility = Facility::create([
            'name'   => 'City Medical Centre',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $this->listing = CareFacility::create([
            'facility_name'  => 'City Medical Centre',
            'facility_type'  => 'hospital',
            'listing_status' => 'active',
            'city'           => 'Yaounde',
            'country_code'   => 'CM',
            'address'        => '12 Independence Ave',
            'phone_primary'  => '+237600000001',
            'facility_id'    => $this->facility->id,
        ]);

        $this->provider = User::factory()->create();

        $this->currentSlot = $this->slot(now()->addDay()->setTime(9, 0), capacity: 1, booked: 1);
        $this->targetSlot  = $this->slot(now()->addDay()->setTime(11, 0), capacity: 1, booked: 0);

        $this->patient     = $this->patient('OC-TST-9999-0001-01', 'Alice');
        $this->appointment = $this->appointmentOn($this->patient, $this->currentSlot);
    }

    // ── The happy path ──────────────────────────────────────────────────────

    public function test_patient_moves_an_appointment_and_the_two_slots_swap(): void
    {
        $response = $this->mobilePostJson($this->patient, $this->url(), [
            'appointment_slot_id' => $this->targetSlot->id,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $this->appointment->id)
                 ->assertJsonPath('data.status', 'booked');

        $this->appointment->refresh();

        $this->assertSame($this->targetSlot->id, $this->appointment->appointment_slot_id);
        $this->assertTrue($this->targetSlot->starts_at->equalTo($this->appointment->scheduled_at));

        // The swap: one released, one claimed. Never both, never neither.
        $this->assertSame(0, $this->currentSlot->fresh()->booked_count);
        $this->assertSame(1, $this->targetSlot->fresh()->booked_count);

        // The response tells the client where the appointment came from, so the
        // confirmation can name both times.
        $this->assertSame($this->currentSlot->id, $response->json('data.previous_appointment_slot_id'));
    }

    public function test_the_move_is_written_to_the_appointment_audit_trail(): void
    {
        $this->mobilePostJson($this->patient, $this->url(), [
            'appointment_slot_id' => $this->targetSlot->id,
        ])->assertStatus(200);

        $event = AuditEvent::where('resource_type', 'appointment')
            ->where('resource_id', $this->appointment->id)
            ->where('action_type', 'reschedule')
            ->first();

        $this->assertNotNull($event, 'A reschedule must leave an AuditEvent behind.');
        $this->assertSame($this->patient->id, $event->patient_id);
        $this->assertSame('patient', $event->actor_role);
        $this->assertSame($this->currentSlot->id, $event->before_state['appointment_slot_id']);
        $this->assertSame($this->targetSlot->id, $event->after_state['appointment_slot_id']);
    }

    public function test_the_clinician_moves_with_the_slot(): void
    {
        $otherProvider = User::factory()->create();
        $slot = $this->slot(now()->addDay()->setTime(14, 0), capacity: 1, booked: 0, provider: $otherProvider);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $slot->id])
             ->assertStatus(200);

        $this->assertSame($otherProvider->id, $this->appointment->fresh()->provider_id);
    }

    // ── Patient scoping ─────────────────────────────────────────────────────

    public function test_a_body_supplied_patient_id_is_ignored(): void
    {
        $intruder    = $this->patient('OC-TST-9999-0002-01', 'Mallory');
        $victimSlot  = $this->slot(now()->addDay()->setTime(15, 0), capacity: 1, booked: 1);
        $victimAppt  = $this->appointmentOn($this->patient, $victimSlot);

        // Mallory authenticates as herself and puts Alice's id in the body in
        // every spelling the codebase has ever used for one.
        $response = $this->mobilePostJson($intruder, "/api/mobile/appointments/{$victimAppt->id}/reschedule", [
            'appointment_slot_id' => $this->targetSlot->id,
            'patient_id'          => $this->patient->id,
            '_patient_id'         => $this->patient->id,
        ]);

        $response->assertStatus(404)->assertJsonPath('error_code', 'APPOINTMENT_NOT_FOUND');

        // Alice's appointment and both slot counts are exactly as they were.
        $this->assertSame($victimSlot->id, $victimAppt->fresh()->appointment_slot_id);
        $this->assertSame(1, $victimSlot->fresh()->booked_count);
        $this->assertSame(0, $this->targetSlot->fresh()->booked_count);
    }

    public function test_a_body_supplied_patient_id_cannot_reassign_the_appointment(): void
    {
        $other = $this->patient('OC-TST-9999-0003-01', 'Bob');

        $this->mobilePostJson($this->patient, $this->url(), [
            'appointment_slot_id' => $this->targetSlot->id,
            'patient_id'          => $other->id,
        ])->assertStatus(200);

        // The appointment moved in time, not to another person.
        $this->assertSame($this->patient->id, $this->appointment->fresh()->patient_id);
    }

    public function test_a_malformed_appointment_id_is_a_not_found_not_a_crash(): void
    {
        // Postgres raises on `uuid = 'not-a-uuid'`, which without the guard in
        // findOwnAppointment() surfaces as a 500 with a driver message in it.
        $this->mobilePostJson($this->patient, '/api/mobile/appointments/not-a-uuid/reschedule', [
            'appointment_slot_id' => $this->targetSlot->id,
        ])->assertStatus(404)->assertJsonPath('error_code', 'APPOINTMENT_NOT_FOUND');

        $this->mobileGetJson($this->patient, '/api/mobile/appointments/not-a-uuid/reschedule-options')
             ->assertStatus(404);
    }

    public function test_an_unauthenticated_request_is_rejected(): void
    {
        $this->postJson($this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(401);

        $this->assertSame(0, $this->targetSlot->fresh()->booked_count);
    }

    // ── The appointment cannot be moved — and it says which ─────────────────

    public function test_a_cancelled_appointment_is_refused_by_name(): void
    {
        $this->appointment->update(['status' => 'cancelled']);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'APPOINTMENT_CANCELLED')
             ->assertJsonFragment(['message' => __('appointments_reschedule.cancelled')]);

        $this->assertSame(0, $this->targetSlot->fresh()->booked_count);
    }

    public function test_a_completed_appointment_is_refused_by_name(): void
    {
        $this->appointment->update(['status' => 'completed']);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'APPOINTMENT_COMPLETED')
             ->assertJsonFragment(['message' => __('appointments_reschedule.completed')]);
    }

    public function test_a_checked_in_appointment_is_refused_by_name(): void
    {
        $this->appointment->update(['status' => 'checked_in']);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'APPOINTMENT_CHECKED_IN');
    }

    public function test_a_past_appointment_is_refused_by_name(): void
    {
        $this->appointment->update(['scheduled_at' => now()->subHours(3)]);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'APPOINTMENT_IN_PAST');

        $this->assertSame(0, $this->targetSlot->fresh()->booked_count);
    }

    // ── The chosen slot cannot be taken ─────────────────────────────────────

    public function test_moving_onto_the_same_slot_is_refused(): void
    {
        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->currentSlot->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'SAME_SLOT');

        // Critically: the count was not decremented-then-incremented into a
        // different number by a swap onto itself.
        $this->assertSame(1, $this->currentSlot->fresh()->booked_count);
    }

    public function test_a_full_slot_is_refused_and_the_original_is_kept(): void
    {
        $this->targetSlot->update(['booked_count' => 1, 'capacity' => 1]);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(409)
             ->assertJsonPath('error_code', 'SLOT_FULL')
             ->assertJsonPath('appointment.id', $this->appointment->id);

        $this->assertSame($this->currentSlot->id, $this->appointment->fresh()->appointment_slot_id);
        $this->assertSame(1, $this->currentSlot->fresh()->booked_count);
        $this->assertSame(1, $this->targetSlot->fresh()->booked_count);
    }

    public function test_a_slot_in_the_past_is_refused(): void
    {
        $past = $this->slot(now()->subDay()->setTime(9, 0), capacity: 1, booked: 0);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $past->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'SLOT_NOT_BOOKABLE');

        $this->assertSame(0, $past->fresh()->booked_count);
    }

    public function test_a_closed_slot_is_refused(): void
    {
        $this->targetSlot->update(['status' => 'closed']);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'SLOT_NOT_BOOKABLE');
    }

    public function test_a_slot_at_another_facility_is_refused(): void
    {
        $otherFacility = Facility::create(['name' => 'Coastal Clinic', 'type' => 'clinic', 'status' => 'active']);
        $foreign = AppointmentSlot::create([
            'facility_id'  => $otherFacility->id,
            'provider_id'  => $this->provider->id,
            'starts_at'    => now()->addDay()->setTime(10, 0),
            'ends_at'      => now()->addDay()->setTime(10, 30),
            'capacity'     => 1,
            'booked_count' => 0,
            'status'       => 'open',
        ]);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $foreign->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'FACILITY_SLOT_MISMATCH');

        $this->assertSame($this->currentSlot->id, $this->appointment->fresh()->appointment_slot_id);
        $this->assertSame(0, $foreign->fresh()->booked_count);
    }

    public function test_a_suspended_directory_listing_stops_reschedules(): void
    {
        $this->listing->update(['listing_status' => 'suspended']);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'FACILITY_NOT_BOOKABLE');
    }

    public function test_a_suspended_facility_stops_reschedules(): void
    {
        $this->facility->update(['status' => 'suspended']);

        $this->mobilePostJson($this->patient, $this->url(), ['appointment_slot_id' => $this->targetSlot->id])
             ->assertStatus(422)
             ->assertJsonPath('error_code', 'FACILITY_NOT_BOOKABLE');
    }

    public function test_an_unknown_slot_is_a_validation_error_not_a_crash(): void
    {
        $this->mobilePostJson($this->patient, $this->url(), [
            'appointment_slot_id' => '11111111-1111-4111-8111-111111111111',
        ])->assertStatus(422)->assertJsonValidationErrors('appointment_slot_id');
    }

    // ── The picker's data source ────────────────────────────────────────────

    public function test_options_lists_the_appointments_own_facility_slots(): void
    {
        $elsewhere = Facility::create(['name' => 'Far Hospital', 'type' => 'hospital', 'status' => 'active']);
        AppointmentSlot::create([
            'facility_id'  => $elsewhere->id,
            'provider_id'  => $this->provider->id,
            'starts_at'    => now()->addDay()->setTime(12, 0),
            'ends_at'      => now()->addDay()->setTime(12, 30),
            'capacity'     => 3,
            'booked_count' => 0,
            'status'       => 'open',
        ]);

        $response = $this->mobileGetJson($this->patient, $this->url('reschedule-options'));

        $response->assertStatus(200)->assertJsonPath('data.can_reschedule', true);

        $ids = array_column($response->json('data.slots'), 'id');

        $this->assertContains($this->targetSlot->id, $ids);
        // The current slot is listed even though the patient's own booking
        // filled it, so the picker can show "you are here".
        $this->assertContains($this->currentSlot->id, $ids);
        $this->assertCount(2, $ids, 'Only this facility\'s slots may be offered.');

        $current = collect($response->json('data.slots'))->firstWhere('id', $this->currentSlot->id);
        $this->assertTrue($current['is_current']);
    }

    public function test_options_explains_a_blocked_appointment_before_the_patient_picks(): void
    {
        $this->appointment->update(['status' => 'cancelled']);

        $this->mobileGetJson($this->patient, $this->url('reschedule-options'))
             ->assertStatus(200)
             ->assertJsonPath('data.can_reschedule', false)
             ->assertJsonPath('data.blocked_reason.error_code', 'APPOINTMENT_CANCELLED')
             ->assertJsonPath('data.slots', []);
    }

    public function test_options_for_another_patients_appointment_is_not_found(): void
    {
        $intruder = $this->patient('OC-TST-9999-0004-01', 'Eve');

        $this->mobileGetJson($intruder, $this->url('reschedule-options'))->assertStatus(404);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function url(string $action = 'reschedule'): string
    {
        return "/api/mobile/appointments/{$this->appointment->id}/{$action}";
    }

    private function slot($startsAt, int $capacity, int $booked, ?User $provider = null): AppointmentSlot
    {
        return AppointmentSlot::create([
            'facility_id'  => $this->facility->id,
            'provider_id'  => ($provider ?? $this->provider)->id,
            'starts_at'    => $startsAt,
            'ends_at'      => (clone $startsAt)->addMinutes(30),
            'capacity'     => $capacity,
            'booked_count' => $booked,
            'status'       => 'open',
        ]);
    }

    private function patient(string $healthId, string $firstName): Patient
    {
        return Patient::create([
            'health_id'     => $healthId,
            'first_name'    => $firstName,
            'last_name'     => 'Tester',
            'sex'           => 'female',
            'date_of_birth' => '1990-01-01',
            'is_demo'       => false,
        ]);
    }

    private function appointmentOn(Patient $patient, AppointmentSlot $slot): Appointment
    {
        return Appointment::create([
            'patient_id'          => $patient->id,
            'facility_id'         => $slot->facility_id,
            'provider_id'         => $slot->provider_id,
            'appointment_slot_id' => $slot->id,
            'appointment_type'    => 'consultation',
            'status'              => 'booked',
            'scheduled_at'        => $slot->starts_at,
            'booked_by_type'      => 'patient',
            'booked_by_id'        => $patient->id,
        ]);
    }
}
