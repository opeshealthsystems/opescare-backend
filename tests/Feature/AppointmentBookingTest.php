<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Facility;
use App\Models\FacilitySchedule;
use App\Models\Patient;
use App\Models\ProviderAvailability;
use App\Models\User;
use App\Modules\Appointments\Services\AppointmentService;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_book_an_available_slot()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $slot = $this->openSlot($facility, $provider, '2026-06-01 09:00:00');

        $appointment = app(AppointmentService::class)->book([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type' => 'outpatient',
            'booked_by_type' => 'patient',
            'booked_by_id' => $patient->id,
        ]);

        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals($slot->starts_at->toDateTimeString(), $appointment->scheduled_at->toDateTimeString());
        $this->assertDatabaseHas('appointment_slots', ['id' => $slot->id, 'booked_count' => 1]);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'appointment',
            'resource_id' => $appointment->id,
            'action_type' => 'create',
        ]);
    }

    public function test_staff_can_book_for_a_patient()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $staff = User::create(['name' => 'Front Desk', 'email' => 'frontdesk@test.com', 'password' => 'password', 'primary_facility_id' => $facility->id]);
        $this->providerAvailability($facility, $provider, 1, '08:00', '16:00');

        $appointment = app(AppointmentService::class)->book([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'scheduled_at' => '2026-06-01 10:30:00',
            'appointment_type' => 'outpatient',
            'booked_by_type' => 'staff',
            'booked_by_id' => $staff->id,
        ]);

        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals($staff->id, $appointment->booked_by_id);
    }

    public function test_slot_capacity_blocks_double_booking()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $secondPatient = Patient::create(['health_id' => 'OC-APT-002', 'first_name' => 'Second', 'last_name' => 'Patient']);
        $slot = $this->openSlot($facility, $provider, '2026-06-01 09:00:00', 1);

        app(AppointmentService::class)->book([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type' => 'outpatient',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('APPOINTMENT_SLOT_FULL');

        app(AppointmentService::class)->book([
            'patient_id' => $secondPatient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_slot_id' => $slot->id,
            'appointment_type' => 'outpatient',
        ]);
    }

    public function test_patient_can_reschedule_with_audit_trail()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $oldSlot = $this->openSlot($facility, $provider, '2026-06-01 09:00:00');
        $newSlot = $this->openSlot($facility, $provider, '2026-06-02 11:00:00');
        $service = app(AppointmentService::class);

        $appointment = $service->book([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_slot_id' => $oldSlot->id,
            'appointment_type' => 'outpatient',
        ]);

        $rescheduled = $service->reschedule($appointment, [
            'appointment_slot_id' => $newSlot->id,
            'reason' => 'Patient requested later date.',
            'actor_id' => $patient->id,
        ]);

        $this->assertEquals('rescheduled', $appointment->fresh()->status);
        $this->assertEquals('scheduled', $rescheduled->status);
        $this->assertEquals($appointment->id, $rescheduled->rescheduled_from_appointment_id);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'appointment',
            'resource_id' => $appointment->id,
            'action_type' => 'reschedule',
        ]);
    }

    public function test_cancellation_requires_reason_and_writes_audit_event()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $appointment = app(AppointmentService::class)->book([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_slot_id' => $this->openSlot($facility, $provider, '2026-06-01 09:00:00')->id,
            'appointment_type' => 'outpatient',
        ]);

        try {
            app(AppointmentService::class)->cancel($appointment, '', $patient->id);
            $this->fail('Cancellation without reason should fail.');
        } catch (Exception $exception) {
            $this->assertSame('APPOINTMENT_CANCELLATION_REASON_REQUIRED', $exception->getMessage());
        }

        $cancelled = app(AppointmentService::class)->cancel($appointment, 'Patient unavailable.', $patient->id);

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'appointment',
            'resource_id' => $appointment->id,
            'action_type' => 'cancel',
        ]);
    }

    public function test_check_in_creates_a_linked_visit()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $appointment = app(AppointmentService::class)->book([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_slot_id' => $this->openSlot($facility, $provider, '2026-06-01 09:00:00')->id,
            'appointment_type' => 'outpatient',
        ]);

        $checkedIn = app(AppointmentService::class)->checkIn($appointment, $provider->id);

        $this->assertEquals('checked_in', $checkedIn->status);
        $this->assertNotNull($checkedIn->visit_id);
        $this->assertDatabaseHas('visits', ['id' => $checkedIn->visit_id, 'patient_id' => $patient->id]);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'appointment',
            'resource_id' => $appointment->id,
            'action_type' => 'check_in',
        ]);
    }

    public function test_no_show_marks_only_past_unchecked_appointments()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $past = Appointment::create([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_type' => 'outpatient',
            'status' => 'scheduled',
            'scheduled_at' => now()->subDay(),
        ]);
        $future = Appointment::create([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_type' => 'outpatient',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $count = app(AppointmentService::class)->markNoShows(now(), $provider->id);

        $this->assertEquals(1, $count);
        $this->assertEquals('no_show', $past->fresh()->status);
        $this->assertEquals('scheduled', $future->fresh()->status);
    }

    public function test_api_patient_scope_cannot_list_another_patients_appointments()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $otherPatient = Patient::create(['health_id' => 'OC-APT-OTHER', 'first_name' => 'Other', 'last_name' => 'Patient']);

        Appointment::create([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_type' => 'outpatient',
            'status' => 'scheduled',
            'scheduled_at' => '2026-06-01 09:00:00',
        ]);
        Appointment::create([
            'patient_id' => $otherPatient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_type' => 'outpatient',
            'status' => 'scheduled',
            'scheduled_at' => '2026-06-01 10:00:00',
        ]);

        $response = $this->withHeaders($this->clientHeadersFor($facility))
            ->getJson('/api/v1/appointments?patient_id='.$patient->id.'&scope=patient');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($patient->id, $response->json('data.0.patient_id'));
    }

    public function test_api_provider_and_facility_filters_return_assigned_appointments()
    {
        [$patient, $facility, $provider] = $this->appointmentActors();
        $otherFacility = Facility::create(['name' => 'Other Facility', 'type' => 'clinic']);
        $otherProvider = User::create(['name' => 'Dr Other', 'email' => 'other@test.com', 'password' => 'password', 'primary_facility_id' => $otherFacility->id]);

        Appointment::create([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'appointment_type' => 'outpatient',
            'status' => 'scheduled',
            'scheduled_at' => '2026-06-01 09:00:00',
        ]);
        Appointment::create([
            'patient_id' => $patient->id,
            'facility_id' => $otherFacility->id,
            'provider_id' => $otherProvider->id,
            'appointment_type' => 'outpatient',
            'status' => 'scheduled',
            'scheduled_at' => '2026-06-01 10:00:00',
        ]);

        $response = $this->withHeaders($this->clientHeadersFor($facility))
            ->getJson('/api/v1/appointments?facility_id='.$facility->id.'&provider_id='.$provider->id);

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($facility->id, $response->json('data.0.facility_id'));
        $this->assertSame($provider->id, $response->json('data.0.provider_id'));
    }

    /**
     * Create an active IntegrationClient bound to the given facility so
     * VerifyIntegrationClient resolves facility_id to the test's facility.
     */
    private function clientHeadersFor(Facility $facility): array
    {
        $clientId = 'client_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12));
        \App\Models\IntegrationClient::factory()->create([
            'client_id'     => $clientId,
            'client_secret' => hash('sha256', 'integration_secret'),
            'facility_id'   => $facility->id,
        ]);

        return ['X-Client-ID' => $clientId, 'X-Client-Secret' => 'integration_secret'];
    }

    private function appointmentActors(): array
    {
        $patient = Patient::create(['health_id' => 'OC-APT-001', 'first_name' => 'Amina', 'last_name' => 'Patient']);
        $facility = Facility::create(['name' => 'Pilot Clinic', 'type' => 'clinic', 'status' => 'active']);
        $provider = User::create(['name' => 'Dr Pilot', 'email' => 'pilot@test.com', 'password' => 'password', 'primary_facility_id' => $facility->id]);

        FacilitySchedule::create([
            'facility_id' => $facility->id,
            'day_of_week' => 1,
            'opens_at' => '08:00',
            'closes_at' => '17:00',
            'is_active' => true,
        ]);

        return [$patient, $facility, $provider];
    }

    private function providerAvailability(Facility $facility, User $provider, int $dayOfWeek, string $startsAt, string $endsAt): ProviderAvailability
    {
        return ProviderAvailability::create([
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'day_of_week' => $dayOfWeek,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
        ]);
    }

    private function openSlot(Facility $facility, User $provider, string $startsAt, int $capacity = 1): AppointmentSlot
    {
        $start = CarbonImmutable::parse($startsAt);
        FacilitySchedule::firstOrCreate([
            'facility_id' => $facility->id,
            'day_of_week' => (int) $start->dayOfWeekIso,
        ], [
            'opens_at' => '08:00',
            'closes_at' => '17:00',
            'is_active' => true,
        ]);
        $this->providerAvailability($facility, $provider, (int) $start->dayOfWeekIso, '08:00', '17:00');

        return AppointmentSlot::create([
            'facility_id' => $facility->id,
            'provider_id' => $provider->id,
            'starts_at' => $start,
            'ends_at' => $start->addMinutes(30),
            'capacity' => $capacity,
            'booked_count' => 0,
            'status' => 'open',
        ]);
    }
}
