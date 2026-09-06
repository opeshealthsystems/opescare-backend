<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\FacilitySchedule;
use App\Models\ProviderAvailability;
use App\Models\Visit;
use Illuminate\Support\Str;

/**
 * Writing the facility's appointment book from the front desk app: booking by
 * exact Health ID, moving, routing, confirming, checking in (which opens the
 * visit), no-show and cancellation — each with its audit and history rows,
 * each telling the patient, none reaching across facilities.
 */
class FrontDeskAppointmentsTest extends FrontDeskTestCase
{
    // ── Booking ───────────────────────────────────────────────────────────

    public function test_booking_by_exact_health_id_writes_the_row_the_web_desk_writes(): void
    {
        $when = $this->tomorrow(9, 30);

        $response = $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id'        => self::HEALTH_ID,
            'scheduled_at'     => $when->toIso8601String(),
            'appointment_type' => 'consultation',
            'reason'           => 'Persistent cough',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.appointment_type', 'consultation')
            ->assertJsonPath('data.reason', 'Persistent cough')
            ->assertJsonPath('data.patient.health_id', self::HEALTH_ID)
            ->assertJsonPath('data.provider', null)
            ->assertJsonPath('data.facility_id', $this->facility->id)
            ->assertJsonPath('data.booked_by_type', 'staff');

        $appointment = Appointment::findOrFail($response->json('data.id'));
        $this->assertSame($this->facility->id, $appointment->facility_id);
        $this->assertSame($this->patient->id, $appointment->patient_id);
        $this->assertNull($appointment->provider_id);
        $this->assertSame('staff', $appointment->booked_by_type);
        $this->assertSame($this->users['receptionist']->id, $appointment->booked_by_id);
        $this->assertTrue($appointment->scheduled_at->equalTo($when));
        $this->assertTrue($appointment->billing_deferred);

        // Audit row in AppointmentService::audit()'s shape, plus the ledger.
        $this->assertDatabaseHas('audit_events', [
            'actor_id'      => $this->users['receptionist']->id,
            'facility_id'   => $this->facility->id,
            'patient_id'    => $this->patient->id,
            'action_type'   => 'create',
            'resource_type' => 'appointment',
            'resource_id'   => $appointment->id,
        ]);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id'  => $appointment->id,
            'from_status'     => null,
            'to_status'       => 'scheduled',
            'changed_by'      => $this->users['receptionist']->id,
            'changed_by_type' => 'provider',
        ]);

        // The patient's bell rang, in the payload shape the mobile feed reads.
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'patient',
            'notifiable_id'   => $this->patient->id,
            'type'            => 'appointment_booked',
        ]);
        $data = json_decode(\DB::table('notifications')->where('notifiable_id', $this->patient->id)->value('data'), true);
        $this->assertSame('appointment_booked', $data['type']);
        $this->assertSame($appointment->id, $data['appointment_id']);
        $this->assertSame('/appointments', $data['action_url']);
        foreach (['title', 'message', 'severity', 'action_label'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    public function test_the_health_id_is_folded_whole_but_never_matched_in_part(): void
    {
        // Case and spacing forgiven — one whole identifier, compared as an equality.
        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => ' cm-hid-test-desk-0001 ', 'scheduled_at' => $this->tomorrow(9)->toIso8601String(), 'appointment_type' => 'consultation',
        ])->assertCreated()->assertJsonPath('data.patient.health_id', self::HEALTH_ID);

        // A prefix, a suffix, a wildcard: nothing, and nothing revealed.
        foreach (['CM-HID-TEST-DESK', 'DESK-0001', 'CM-HID-TEST-DESK-%', 'CM-HID-TEST-DESK-000', '%'] as $partial) {
            $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
                'health_id' => $partial, 'scheduled_at' => $this->tomorrow(9)->toIso8601String(), 'appointment_type' => 'consultation',
            ])
                ->assertNotFound()
                ->assertJsonPath('error_code', 'patient_not_found')
                ->assertJsonMissingPath('data');
        }

        $this->assertSame(1, Appointment::count());
    }

    public function test_booking_can_name_a_clinician_posted_here_and_nobody_else(): void
    {
        $body = fn (string $providerId) => [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(9)->toIso8601String(),
            'appointment_type' => 'consultation', 'provider_id' => $providerId,
        ];

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', $body($this->users['nurse']->id))
            ->assertCreated()
            ->assertJsonPath('data.provider.id', $this->users['nurse']->id)
            ->assertJsonPath('data.provider.role', 'nurse');

        // A doctor at another facility, a non-clinician posted here, a stranger.
        foreach ([$this->otherDoctor->id, $this->users['receptionist']->id, $this->users['pharmacist']->id, (string) Str::uuid()] as $notHere) {
            $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', $body($notHere))
                ->assertStatus(422)
                ->assertJsonPath('error_code', 'provider_not_at_facility');
        }

        $this->assertSame(1, Appointment::count());
    }

    public function test_booking_needs_a_future_time_a_type_and_a_health_id(): void
    {
        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => now()->subHour()->toIso8601String(), 'appointment_type' => 'consultation',
        ])->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['health_id', 'scheduled_at', 'appointment_type']);

        // A facility id in the body is not a field this API has.
        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(9)->toIso8601String(),
            'appointment_type' => 'consultation', 'facility_id' => $this->otherFacility->id,
        ])->assertCreated()->assertJsonPath('data.facility_id', $this->facility->id);
    }

    public function test_configured_opening_hours_are_enforced_and_unconfigured_ones_are_not(): void
    {
        $when = $this->tomorrow(15);

        // No facility_schedules row anywhere: the web desk's rule — nothing to
        // enforce, the booking is written.
        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $when->toIso8601String(), 'appointment_type' => 'consultation',
        ])->assertCreated();

        // The facility publishes 08:00–12:00 for that weekday: 15:00 is refused, 10:00 goes through the service.
        FacilitySchedule::create([
            'facility_id' => $this->facility->id, 'day_of_week' => $when->dayOfWeekIso,
            'opens_at' => '08:00:00', 'closes_at' => '12:00:00', 'is_active' => true,
        ]);

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $when->toIso8601String(), 'appointment_type' => 'consultation',
        ])->assertStatus(422)->assertJsonPath('error_code', 'facility_not_available');

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(10)->toIso8601String(), 'appointment_type' => 'consultation',
        ])->assertCreated();

        // The doctor posts 08:00–10:00 here: 11:00 with the doctor is refused,
        // 09:00 is booked, and the nurse (no posted hours) is bookable at 11:00.
        ProviderAvailability::create([
            'facility_id' => $this->facility->id, 'provider_id' => $this->users['doctor']->id, 'day_of_week' => $when->dayOfWeekIso,
            'starts_at' => '08:00:00', 'ends_at' => '10:00:00', 'is_active' => true,
        ]);

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(11)->toIso8601String(),
            'appointment_type' => 'consultation', 'provider_id' => $this->users['doctor']->id,
        ])->assertStatus(422)->assertJsonPath('error_code', 'provider_not_available');

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(9)->toIso8601String(),
            'appointment_type' => 'consultation', 'provider_id' => $this->users['doctor']->id,
        ])->assertCreated()->assertJsonPath('data.provider.id', $this->users['doctor']->id);

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(11)->toIso8601String(),
            'appointment_type' => 'consultation', 'provider_id' => $this->users['nurse']->id,
        ])->assertCreated();

        $this->assertSame(4, Appointment::count());
    }

    public function test_a_clinicians_posted_hours_count_even_when_the_facility_has_none(): void
    {
        $when = $this->tomorrow(15);

        ProviderAvailability::create([
            'facility_id' => $this->facility->id, 'provider_id' => $this->users['doctor']->id, 'day_of_week' => $when->dayOfWeekIso,
            'starts_at' => '08:00:00', 'ends_at' => '12:00:00', 'is_active' => true,
        ]);

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $when->toIso8601String(),
            'appointment_type' => 'consultation', 'provider_id' => $this->users['doctor']->id,
        ])->assertStatus(422)->assertJsonPath('error_code', 'provider_not_available');

        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments', [
            'health_id' => self::HEALTH_ID, 'scheduled_at' => $this->tomorrow(9)->toIso8601String(),
            'appointment_type' => 'consultation', 'provider_id' => $this->users['doctor']->id,
        ])->assertCreated();
    }

    // ── Moving and routing ────────────────────────────────────────────────

    public function test_reschedule_moves_the_same_row_and_gives_the_slot_back(): void
    {
        $slot = AppointmentSlot::create([
            'facility_id' => $this->facility->id, 'provider_id' => $this->users['doctor']->id,
            'starts_at' => $this->today(10), 'ends_at' => $this->today(10, 30), 'capacity' => 1, 'booked_count' => 1, 'status' => 'open',
        ]);
        $appointment = $this->appointment(['appointment_slot_id' => $slot->id, 'status' => 'confirmed']);
        $when        = $this->tomorrow(14);

        $this->hit('receptionist', 'PUT', "/api/mobile/desk/appointments/{$appointment->id}", ['scheduled_at' => $when->toIso8601String()])
            ->assertOk()
            ->assertJsonPath('data.id', $appointment->id)
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.scheduled_at', $when->toIso8601String());

        $appointment->refresh();
        $this->assertTrue($appointment->scheduled_at->equalTo($when));
        $this->assertNull($appointment->appointment_slot_id);
        $this->assertSame(0, $slot->fresh()->booked_count);
        $this->assertSame(1, Appointment::count(), 'moved in place, not replaced');

        $this->assertDatabaseHas('audit_events', ['resource_id' => $appointment->id, 'action_type' => 'reschedule', 'actor_id' => $this->users['receptionist']->id]);
        $this->assertDatabaseHas('appointment_status_histories', ['appointment_id' => $appointment->id, 'from_status' => 'confirmed', 'to_status' => 'confirmed']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->patient->id, 'type' => 'appointment_rescheduled']);

        $this->hit('receptionist', 'PUT', "/api/mobile/desk/appointments/{$appointment->id}", ['scheduled_at' => now()->subDay()->toIso8601String()])
            ->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);

        $closed = $this->appointment(['status' => 'completed']);
        $this->hit('receptionist', 'PUT', "/api/mobile/desk/appointments/{$closed->id}", ['scheduled_at' => $when->toIso8601String()])
            ->assertStatus(409)->assertJsonPath('error_code', 'appointment_closed');
    }

    public function test_routing_hands_the_appointment_to_a_clinician_posted_here(): void
    {
        $appointment = $this->appointment(['provider_id' => null]);

        $this->hit('receptionist', 'PATCH', "/api/mobile/desk/appointments/{$appointment->id}/route", ['provider_id' => $this->users['specialist']->id])
            ->assertOk()
            ->assertJsonPath('data.provider.id', $this->users['specialist']->id)
            ->assertJsonPath('data.provider.role', 'specialist')
            ->assertJsonPath('data.status', 'scheduled');

        $this->assertSame($this->users['specialist']->id, $appointment->fresh()->provider_id);
        $this->assertDatabaseHas('audit_events', ['resource_id' => $appointment->id, 'action_type' => 'route']);

        $history = \DB::table('appointment_status_histories')->where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($history);
        $this->assertSame($this->users['specialist']->id, json_decode($history->metadata, true)['provider_id']);
        $this->assertSame('mobile_desk', json_decode($history->metadata, true)['channel']);

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->patient->id, 'type' => 'appointment_routed']);

        // Not a clinician here: the other facility's doctor, the pharmacist, a stranger, garbage.
        foreach ([$this->otherDoctor->id, $this->users['pharmacist']->id, (string) Str::uuid()] as $notHere) {
            $this->hit('receptionist', 'PATCH', "/api/mobile/desk/appointments/{$appointment->id}/route", ['provider_id' => $notHere])
                ->assertStatus(422)->assertJsonPath('error_code', 'provider_not_at_facility');
        }
        $this->hit('receptionist', 'PATCH', "/api/mobile/desk/appointments/{$appointment->id}/route", ['provider_id' => 'nope'])
            ->assertStatus(422)->assertJsonValidationErrors(['provider_id']);

        $this->assertSame($this->users['specialist']->id, $appointment->fresh()->provider_id, 'refusals change nothing');

        $closed = $this->appointment(['status' => 'no_show']);
        $this->hit('receptionist', 'PATCH', "/api/mobile/desk/appointments/{$closed->id}/route", ['provider_id' => $this->users['doctor']->id])
            ->assertStatus(409)->assertJsonPath('error_code', 'appointment_closed');
    }

    // ── Confirm / check-in / no-show / cancel ─────────────────────────────

    public function test_confirm_follows_the_web_desk_and_tells_the_patient(): void
    {
        $appointment = $this->appointment(['status' => 'booked', 'booked_by_type' => 'patient']);

        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertDatabaseHas('audit_events', ['resource_id' => $appointment->id, 'action_type' => 'confirm', 'actor_id' => $this->users['receptionist']->id]);
        $this->assertDatabaseHas('appointment_status_histories', ['appointment_id' => $appointment->id, 'from_status' => 'booked', 'to_status' => 'confirmed']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->patient->id, 'type' => 'appointment_confirmed']);

        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/confirm")
            ->assertStatus(409)->assertJsonPath('error_code', 'already_confirmed');

        $arrived = $this->appointment(['status' => 'checked_in']);
        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$arrived->id}/confirm")
            ->assertStatus(422)->assertJsonPath('error_code', 'not_confirm_eligible');

        $cancelled = $this->appointment(['status' => 'cancelled']);
        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$cancelled->id}/confirm")
            ->assertStatus(409)->assertJsonPath('error_code', 'appointment_closed');

        $this->assertSame(1, \DB::table('notifications')->count(), 'refusals ring no bell');
    }

    public function test_check_in_opens_the_visit_exactly_as_the_web_desk_does(): void
    {
        $appointment = $this->appointment(['status' => 'confirmed', 'appointment_type' => 'follow_up']);

        $response = $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/check-in")
            ->assertOk()
            ->assertJsonPath('data.status', 'checked_in');

        $appointment->refresh();
        $this->assertSame('checked_in', $appointment->status);
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertNotNull($appointment->visit_id);
        $this->assertSame($appointment->visit_id, $response->json('data.visit_id'));
        $this->assertNotNull($response->json('data.checked_in_at'));

        $visit = Visit::findOrFail($appointment->visit_id);
        $this->assertSame($this->patient->id, $visit->patient_id);
        $this->assertSame($this->facility->id, $visit->facility_id);
        $this->assertSame($this->users['doctor']->id, $visit->provider_id);
        $this->assertSame('follow_up', $visit->visit_type);
        $this->assertSame('open', $visit->status);
        $this->assertNotNull($visit->started_at);

        // The visit's own audit row (VisitManagementService) names the receptionist as the actor.
        $this->assertDatabaseHas('audit_events', ['resource_type' => 'visit', 'resource_id' => $visit->id, 'actor_id' => $this->users['receptionist']->id]);
        $this->assertDatabaseHas('audit_events', ['resource_type' => 'appointment', 'resource_id' => $appointment->id, 'action_type' => 'check_in', 'encounter_id' => $visit->id]);
        $this->assertDatabaseHas('appointment_status_histories', ['appointment_id' => $appointment->id, 'from_status' => 'confirmed', 'to_status' => 'checked_in']);

        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/check-in")
            ->assertStatus(409)->assertJsonPath('error_code', 'already_checked_in');
        $this->assertSame(1, Visit::count(), 'one arrival, one visit');

        // A patient-app booking (`booked`) can be checked in too; a cancelled one cannot.
        $booked = $this->appointment(['status' => 'booked', 'booked_by_type' => 'patient']);
        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$booked->id}/check-in")->assertOk();

        $cancelled = $this->appointment(['status' => 'cancelled']);
        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$cancelled->id}/check-in")
            ->assertStatus(422)->assertJsonPath('error_code', 'not_check_in_eligible');
        $this->assertNull($cancelled->fresh()->visit_id);
    }

    public function test_no_show_mirrors_the_web_desk(): void
    {
        $appointment = $this->appointment(['status' => 'confirmed']);

        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/no-show")
            ->assertOk()
            ->assertJsonPath('data.status', 'no_show');

        $appointment->refresh();
        $this->assertSame('no_show', $appointment->status);
        $this->assertNotNull($appointment->no_show_at);
        $this->assertDatabaseHas('audit_events', ['resource_id' => $appointment->id, 'action_type' => 'no_show']);
        $this->assertDatabaseHas('appointment_status_histories', ['appointment_id' => $appointment->id, 'from_status' => 'confirmed', 'to_status' => 'no_show']);

        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/no-show")
            ->assertStatus(409)->assertJsonPath('error_code', 'already_no_show');

        $done = $this->appointment(['status' => 'completed']);
        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$done->id}/no-show")
            ->assertStatus(422)->assertJsonPath('error_code', 'not_no_show_eligible');
        $this->assertSame('completed', $done->fresh()->status);
    }

    public function test_cancel_needs_a_reason_and_goes_through_the_appointment_service(): void
    {
        $appointment = $this->appointment(['status' => 'confirmed']);

        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/cancel")
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/cancel", ['reason' => 'no'])
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->assertSame('confirmed', $appointment->fresh()->status);

        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/cancel", ['reason' => 'Patient called to cancel.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Patient called to cancel.');

        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->status);
        $this->assertSame($this->users['receptionist']->id, $appointment->cancelled_by_id);
        $this->assertNotNull($appointment->cancelled_at);

        // AppointmentService::cancel()'s audit row, plus the ledger row the web desk never wrote.
        $this->assertDatabaseHas('audit_events', ['resource_id' => $appointment->id, 'action_type' => 'cancel', 'actor_id' => $this->users['receptionist']->id]);
        $this->assertDatabaseHas('appointment_status_histories', ['appointment_id' => $appointment->id, 'from_status' => 'confirmed', 'to_status' => 'cancelled', 'reason' => 'Patient called to cancel.']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->patient->id, 'type' => 'appointment_cancelled']);

        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$appointment->id}/cancel", ['reason' => 'Patient called to cancel.'])
            ->assertStatus(422)->assertJsonPath('error_code', 'already_cancelled');

        // A visit that happened is not a booking that can be withdrawn.
        $done = $this->appointment(['status' => 'completed']);
        $this->hit('receptionist', 'POST', "/api/mobile/desk/appointments/{$done->id}/cancel", ['reason' => 'Trying to erase a visit.'])
            ->assertStatus(409)->assertJsonPath('error_code', 'appointment_closed');
        $this->assertSame('completed', $done->fresh()->status);
    }

    public function test_another_facilitys_appointment_is_not_found_not_forbidden(): void
    {
        $theirs = $this->appointment(['facility_id' => $this->otherFacility->id, 'provider_id' => $this->otherDoctor->id]);

        foreach ([
            ['PUT', "/api/mobile/desk/appointments/{$theirs->id}", ['scheduled_at' => $this->tomorrow(9)->toIso8601String()]],
            ['PATCH', "/api/mobile/desk/appointments/{$theirs->id}/route", ['provider_id' => $this->users['doctor']->id]],
            ['POST', "/api/mobile/desk/appointments/{$theirs->id}/confirm", []],
            ['POST', "/api/mobile/desk/appointments/{$theirs->id}/check-in", []],
            ['POST', "/api/mobile/desk/appointments/{$theirs->id}/no-show", []],
            ['POST', "/api/mobile/desk/appointments/{$theirs->id}/cancel", ['reason' => 'Not ours to cancel.']],
        ] as [$method, $path, $body]) {
            $this->hit('receptionist', $method, $path, $body)
                ->assertNotFound()
                ->assertJsonPath('error_code', 'appointment_not_found');
        }

        $this->assertSame('scheduled', $theirs->fresh()->status);
        $this->assertSame($this->otherDoctor->id, $theirs->fresh()->provider_id);

        // A malformed id never reaches Postgres.
        $this->hit('receptionist', 'POST', '/api/mobile/desk/appointments/not-a-uuid/confirm')->assertNotFound();
    }
}
