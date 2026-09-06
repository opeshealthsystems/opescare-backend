<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\ConsentGrant;
use App\Models\Patient;
use App\Models\ProviderAvailability;
use Illuminate\Support\Str;

/**
 * POST /api/mobile/doctor/patients/{id}/follow-up — a clinician books the
 * patient's next visit with themselves, at the facility their token names.
 */
class DoctorFollowUpTest extends FrontDeskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Booking a follow-up is a clinical act on a record, so it needs the
        // same standing everything else clinical needs. The ordinary way a
        // walk-in patient gives it: they granted this facility access.
        ConsentGrant::create([
            'patient_id'        => $this->patient->id,
            'facility_id'       => $this->facility->id,
            'authorizing_actor' => 'patient',
            'scope'             => ['patient_identity', 'appointments'],
            'status'            => 'active',
            'expires_at'        => now()->addDay(),
        ]);
    }

    private function path(string $patient): string
    {
        return "/api/mobile/doctor/patients/{$patient}/follow-up";
    }

    /**
     * The gate that stops a follow-up being the back door into a chart.
     *
     * Booking one MINTS a relationship (an appointment is the gate's first
     * basis), so a clinician who has none with this patient cannot book their
     * way into one. The refusal names the way forward and says nothing about
     * whether the record exists.
     */
    public function test_a_clinician_with_no_standing_cannot_book_and_is_told_how_to_ask(): void
    {
        $stranger = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);

        $this->hit('doctor', 'POST', $this->path($stranger->id), [
            'scheduled_at' => $this->tomorrow(10)->toIso8601String(),
        ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'NO_CLINICAL_RELATIONSHIP')
            ->assertJsonPath('required_action', 'request_consent');

        $this->assertDatabaseMissing('appointments', ['patient_id' => $stranger->id]);
    }

    public function test_a_doctor_books_a_follow_up_with_themselves(): void
    {
        $when = $this->tomorrow(11);

        $response = $this->hit('doctor', 'POST', $this->path($this->patient->id), [
            'scheduled_at' => $when->toIso8601String(),
            'reason'       => 'Review blood pressure after dose change',
        ])
            ->assertCreated()
            // show()'s StaffAppointment shape.
            ->assertJsonStructure(['data' => [
                'id', 'patient_id', 'patient_name', 'patient_health_id', 'provider_id', 'provider_name', 'facility_id', 'facility_name',
                'scheduled_at', 'duration_minutes', 'appointment_type', 'reason', 'status', 'checked_in_at', 'encounter_id', 'cancel_reason', 'cancelled_at', 'created_at',
            ]])
            ->assertJsonPath('data.appointment_type', 'follow_up')
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.provider_id', $this->users['doctor']->id)
            ->assertJsonPath('data.provider_name', $this->users['doctor']->name)
            ->assertJsonPath('data.facility_id', $this->facility->id)
            ->assertJsonPath('data.patient_id', $this->patient->id)
            ->assertJsonPath('data.patient_health_id', self::HEALTH_ID)
            ->assertJsonPath('data.reason', 'Review blood pressure after dose change')
            ->assertJsonPath('data.scheduled_at', $when->toIso8601String());

        $appointment = Appointment::findOrFail($response->json('data.id'));
        $this->assertSame('follow_up', $appointment->appointment_type);
        $this->assertSame($this->users['doctor']->id, $appointment->provider_id);
        $this->assertSame($this->facility->id, $appointment->facility_id);
        $this->assertSame('staff', $appointment->booked_by_type);
        $this->assertSame($this->users['doctor']->id, $appointment->booked_by_id);

        // Audit + ledger, stamped as the clinician app.
        $this->assertDatabaseHas('audit_events', ['resource_type' => 'appointment', 'resource_id' => $appointment->id, 'action_type' => 'create', 'actor_id' => $this->users['doctor']->id]);
        $history = \DB::table('appointment_status_histories')->where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($history);
        $this->assertNull($history->from_status);
        $this->assertSame('scheduled', $history->to_status);
        $this->assertSame($this->users['doctor']->id, $history->changed_by);
        $this->assertSame(__('doctor_schedule.reason_follow_up'), $history->reason);
        $this->assertSame('mobile_doctor', json_decode($history->metadata, true)['channel']);

        // The patient is told.
        $this->assertDatabaseHas('notifications', ['notifiable_type' => 'patient', 'notifiable_id' => $this->patient->id, 'type' => 'appointment_follow_up']);
        $data = json_decode(\DB::table('notifications')->where('notifiable_id', $this->patient->id)->value('data'), true);
        $this->assertSame($appointment->id, $data['appointment_id']);
        $this->assertStringContainsString($this->users['doctor']->name, $data['message']);

        // It is now on the doctor's own diary AND on the front desk's book.
        $this->hit('doctor', 'GET', '/api/mobile/doctor/appointments/' . $appointment->id)->assertOk()->assertJsonPath('data.appointment_type', 'follow_up');
        $this->hit('receptionist', 'GET', '/api/mobile/desk/appointments?date=' . $when->toDateString())
            ->assertOk()->assertJsonPath('data.0.id', $appointment->id)->assertJsonPath('data.0.provider.id', $this->users['doctor']->id);
    }

    public function test_the_patient_may_be_named_by_exact_health_id(): void
    {
        $this->hit('doctor', 'POST', $this->path(strtolower(self::HEALTH_ID)), ['scheduled_at' => $this->tomorrow(9)->toIso8601String()])
            ->assertCreated()
            ->assertJsonPath('data.patient_id', $this->patient->id);

        // Never in part.
        $this->hit('doctor', 'POST', $this->path('CM-HID-TEST-DESK'), ['scheduled_at' => $this->tomorrow(9)->toIso8601String()])
            ->assertNotFound()->assertJsonPath('error_code', 'PATIENT_NOT_FOUND');
        $this->hit('doctor', 'POST', $this->path((string) Str::uuid()), ['scheduled_at' => $this->tomorrow(9)->toIso8601String()])
            ->assertNotFound()->assertJsonPath('error_code', 'PATIENT_NOT_FOUND');

        $this->assertSame(1, Appointment::count());
    }

    public function test_every_clinician_may_book_one_and_nobody_else(): void
    {
        $body = ['scheduled_at' => $this->tomorrow(9)->toIso8601String()];

        $this->hit('nurse', 'POST', $this->path($this->patient->id), $body)
            ->assertCreated()
            ->assertJsonPath('data.provider_id', $this->users['nurse']->id);
        $this->hit('specialist', 'POST', $this->path($this->patient->id), $body)->assertCreated();

        foreach (['receptionist', 'pharmacist', 'labtech', 'cashier'] as $role) {
            $this->hit($role, 'POST', $this->path($this->patient->id), $body)
                ->assertForbidden()
                ->assertJsonPath('error_code', 'role_forbidden')
                ->assertJsonPath('required', ['clinical_chart']);
        }

        $this->flushHeaders()->json('POST', $this->path($this->patient->id), $body)->assertUnauthorized();

        $this->assertSame(2, Appointment::count());
    }

    public function test_a_past_or_missing_time_is_refused(): void
    {
        $this->hit('doctor', 'POST', $this->path($this->patient->id), ['scheduled_at' => now()->subHour()->toIso8601String()])
            ->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);
        $this->hit('doctor', 'POST', $this->path($this->patient->id), [])
            ->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);
        $this->hit('doctor', 'POST', $this->path($this->patient->id), ['scheduled_at' => $this->tomorrow(9)->toIso8601String(), 'reason' => str_repeat('x', 501)])
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);

        $this->assertSame(0, Appointment::count());
        $this->assertSame(0, \DB::table('notifications')->count());
    }

    public function test_the_clinicians_posted_hours_are_enforced_when_they_exist(): void
    {
        $when = $this->tomorrow(16);

        ProviderAvailability::create([
            'facility_id' => $this->facility->id, 'provider_id' => $this->users['doctor']->id, 'day_of_week' => $when->dayOfWeekIso,
            'starts_at' => '08:00:00', 'ends_at' => '13:00:00', 'is_active' => true,
        ]);

        $this->hit('doctor', 'POST', $this->path($this->patient->id), ['scheduled_at' => $when->toIso8601String()])
            ->assertStatus(422)->assertJsonPath('error_code', 'PROVIDER_NOT_AVAILABLE');

        $this->hit('doctor', 'POST', $this->path($this->patient->id), ['scheduled_at' => $this->tomorrow(10)->toIso8601String()])
            ->assertCreated();

        // The nurse posted no hours here: nothing to enforce.
        $this->hit('nurse', 'POST', $this->path($this->patient->id), ['scheduled_at' => $when->toIso8601String()])->assertCreated();
    }
}
