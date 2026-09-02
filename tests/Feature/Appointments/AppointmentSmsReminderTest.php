<?php
namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AppointmentSmsReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppointmentSmsReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_reminder_notification_can_be_sent(): void
    {
        Notification::fake();

        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create(['name' => 'Hôpital Central']);

        $appointment = Appointment::create([
            'patient_id'       => $patient->id,
            'provider_id'      => $provider->id,
            'facility_id'      => $facility->id,
            'scheduled_at'     => '2026-07-01 09:00:00',
            'appointment_type' => 'consultation',
            'status'           => 'scheduled',
        ]);

        $patient->notify(new AppointmentSmsReminder($appointment));

        Notification::assertSentTo($patient, AppointmentSmsReminder::class);
    }

    public function test_sms_reminder_message_contains_appointment_details(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create(['name' => 'Hôpital Central']);

        $appointment = Appointment::create([
            'patient_id'       => $patient->id,
            'provider_id'      => $provider->id,
            'facility_id'      => $facility->id,
            'scheduled_at'     => '2026-07-01 09:00:00',
            'appointment_type' => 'consultation',
            'status'           => 'scheduled',
        ]);

        $notification = new AppointmentSmsReminder($appointment->load('facility'));
        $message      = $notification->toArray($patient);

        $this->assertStringContainsString('2026-07-01', $message['message']);
        $this->assertStringContainsString('Hôpital Central', $message['message']);
    }
}
