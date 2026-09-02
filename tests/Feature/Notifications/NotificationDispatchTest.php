<?php

namespace Tests\Feature\Notifications;

use Tests\TestCase;
use Tests\Traits\WithMobileAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Notifications\Services\SmsNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationDispatchTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    public function test_sms_logs_message_when_twilio_not_configured(): void
    {
        config(['services.twilio.sid' => null]);

        $service = app(SmsNotificationService::class);

        Log::shouldReceive('channel')
            ->once()
            ->with('sms')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn ($msg, $ctx) =>
                str_contains($msg, 'SMS') &&
                $ctx['to'] === '+237600000001'
            );

        $service->send('+237600000001', 'Your appointment is confirmed.');
    }

    public function test_email_is_sent_via_laravel_mail(): void
    {
        Mail::fake();

        $service = app(\App\Modules\Notifications\Services\EmailNotificationService::class);
        $service->send('patient@test.com', 'Appointment Confirmed', 'Your appointment is confirmed.');

        Mail::assertSent(\App\Mail\OpesCareNotificationMail::class, function ($mail) {
            return $mail->hasTo('patient@test.com');
        });
    }

    public function test_notification_event_created_on_appointment_booking(): void
    {
        Mail::fake();
        config(['services.twilio.sid' => null]);

        $patient = \App\Models\Patient::create([
            'health_id'     => 'OC-TST-NOTIF-0001',
            'first_name'    => 'Notify',
            'last_name'     => 'Me',
            'sex'           => 'female',
            'date_of_birth' => '1992-03-10',
            'phone_number'  => '+237699000001',
            'is_demo'       => false,
        ]);

        $facilityRow = \App\Models\Facility::create([
            'name'   => 'Test Hospital',
            'type'   => 'hospital',
            'status' => 'active',
        ]);

        $provider = \App\Models\User::factory()->create();

        $slot = \App\Models\AppointmentSlot::create([
            'facility_id'  => $facilityRow->id,
            'provider_id'  => $provider->id,
            'starts_at'    => now()->addDay()->setTime(10, 0),
            'ends_at'      => now()->addDay()->setTime(10, 30),
            'capacity'     => 3,
            'booked_count' => 0,
            'status'       => 'open',
        ]);

        $response = $this->withHeaders($this->mobileAuthHeaders($patient))
            ->postJson('/api/mobile/appointments', [
                'facility_id'         => $facilityRow->id,
                'appointment_slot_id' => $slot->id,
                'appointment_type'    => 'consultation',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('notification_events', [
            'event_type'        => 'appointment.booked',
            'recipient_user_id' => $patient->id,
        ]);
    }

    public function test_notification_event_created_on_consent_request(): void
    {
        $patient = \App\Models\Patient::create([
            'health_id'     => 'OC-TST-NOTIF-0002',
            'first_name'    => 'Consent',
            'last_name'     => 'Test',
            'sex'           => 'male',
            'date_of_birth' => '1988-07-22',
            'phone_number'  => '+237699000002',
            'is_demo'       => false,
        ]);

        $facility = \App\Models\Facility::create([
            'name'   => 'Requesting Clinic',
            'type'   => 'clinic',
            'status' => 'active',
        ]);

        $service = app(\App\Modules\Governance\Services\ConsentService::class);
        $service->requestConsent(
            $patient->id,
            $facility->id,
            null,
            'treatment',
            ['patients:read'],
            60
        );

        $this->assertDatabaseHas('notification_events', [
            'event_type'        => 'consent.request.pending',
            'recipient_user_id' => $patient->id,
        ]);
    }
}
