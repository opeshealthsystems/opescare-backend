<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Appointments\Services\AppointmentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The reminder ladder, end to end.
 *
 * Every assertion here would have failed before this change: nothing created a
 * reminder row, so `opescare:send-appointment-reminders` ran every five
 * minutes and reported success on an empty queue forever.
 */
class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(string $status = 'scheduled', string $when = '+10 days'): Appointment
    {
        return Appointment::create([
            'patient_id'       => Patient::factory()->create()->id,
            'provider_id'      => User::factory()->create()->id,
            'facility_id'      => Facility::factory()->create(['name' => 'Hôpital Central'])->id,
            'scheduled_at'     => now()->add(new \DateInterval('P10D'))->setTime(9, 0),
            'appointment_type' => 'consultation',
            'status'           => $status,
        ]);
    }

    public function test_booking_an_appointment_schedules_the_whole_ladder(): void
    {
        $appointment = $this->appointment();

        $hours = AppointmentReminder::where('appointment_id', $appointment->id)
            ->orderByDesc('remind_before_hours')
            ->pluck('remind_before_hours')
            ->map(fn ($h) => (int) $h)
            ->all();

        $this->assertSame(AppointmentReminderService::OFFSETS_HOURS, $hours);
    }

    public function test_a_rung_already_in_the_past_is_never_scheduled(): void
    {
        // Booked for three hours from now: the 48h and 24h rungs are behind us
        // and telling somebody their appointment is in two days would be false.
        $appointment = Appointment::create([
            'patient_id'       => Patient::factory()->create()->id,
            'provider_id'      => User::factory()->create()->id,
            'facility_id'      => Facility::factory()->create()->id,
            'scheduled_at'     => now()->addHours(3),
            'appointment_type' => 'consultation',
            'status'           => 'scheduled',
        ]);

        $this->assertSame(
            [2],
            AppointmentReminder::where('appointment_id', $appointment->id)
                ->pluck('remind_before_hours')->map(fn ($h) => (int) $h)->all()
        );
    }

    public function test_cancelling_the_appointment_stops_the_reminders(): void
    {
        $appointment = $this->appointment();

        $appointment->update(['status' => 'cancelled']);

        $this->assertSame(
            0,
            AppointmentReminder::where('appointment_id', $appointment->id)->where('status', 'pending')->count()
        );
    }

    public function test_moving_the_appointment_moves_the_reminders(): void
    {
        $appointment = $this->appointment();
        $moved = now()->addDays(20)->setTime(14, 0);

        $appointment->update(['scheduled_at' => $moved]);

        $latest = AppointmentReminder::where('appointment_id', $appointment->id)
            ->where('remind_before_hours', 24)
            ->value('scheduled_at');

        $this->assertSame(
            $moved->copy()->subHours(24)->format('Y-m-d H:i'),
            \Illuminate\Support\Carbon::parse($latest)->format('Y-m-d H:i')
        );
    }

    public function test_a_due_reminder_reaches_the_patient_bell_the_app_reads(): void
    {
        $appointment = $this->appointment();

        $reminder = AppointmentReminder::where('appointment_id', $appointment->id)
            ->where('remind_before_hours', 24)
            ->firstOrFail();

        // Due now.
        $reminder->update(['scheduled_at' => now()->subMinute()]);

        $this->artisan('opescare:send-appointment-reminders')->assertSuccessful();

        $this->assertSame('sent', $reminder->refresh()->status);

        // The patient app reads notifications on the PATIENT, not on a user —
        // the old command notified the user and nobody would have seen it.
        $row = DB::table('notifications')
            ->where('notifiable_id', $appointment->patient_id)
            ->where('type', 'appointment_reminder')
            ->first();

        $this->assertNotNull($row, 'the reminder never reached the bell the app reads');

        $data = json_decode($row->data, true);
        $this->assertStringContainsString('Hôpital Central', $data['message']);
        $this->assertSame('/appointments', $data['action_url']);
    }

    public function test_a_reminder_for_a_cancelled_appointment_is_retired_not_retried(): void
    {
        $appointment = $this->appointment();

        $reminder = AppointmentReminder::where('appointment_id', $appointment->id)->firstOrFail();
        $reminder->update(['scheduled_at' => now()->subMinute(), 'status' => 'pending']);

        // Straight on the row, so the observer does not tidy it away first and
        // the delivery path itself has to make the call.
        DB::table('appointments')->where('id', $appointment->id)->update(['status' => 'cancelled']);

        $this->artisan('opescare:send-appointment-reminders')->assertSuccessful();

        $this->assertSame('cancelled', $reminder->refresh()->status);
    }
}
