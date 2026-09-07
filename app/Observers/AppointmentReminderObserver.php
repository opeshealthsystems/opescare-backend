<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Modules\Appointments\Services\AppointmentReminderService;
use Illuminate\Support\Facades\Log;

/**
 * Keeps an appointment's reminder ladder in step with the appointment.
 *
 * ── Why an observer and not a call at each booking site ────────────────────
 * `AppointmentReminderService::scheduleReminders()` shipped with no callers at
 * all, and an appointment can be created or moved from at least six places:
 * patient self-booking, the front desk (`FrontDeskService`), the staff web
 * portal, the mobile reschedule endpoint, the waitlist, and the demo seeders.
 * Wiring six call sites is how a seventh gets added without one — which is
 * precisely the failure being repaired here. One observer cannot be forgotten.
 *
 * ── It must never break a booking ──────────────────────────────────────────
 * Everything here is wrapped: a reminder is a courtesy, and no patient may
 * fail to get an appointment because a courtesy row could not be written. A
 * failure is logged and the booking stands.
 */
class AppointmentReminderObserver
{
    public function __construct(private readonly AppointmentReminderService $reminders) {}

    public function created(Appointment $appointment): void
    {
        $this->guard(fn () => $this->reminders->scheduleFor($appointment), $appointment, 'created');
    }

    public function updated(Appointment $appointment): void
    {
        $this->guard(function () use ($appointment) {
            // Moved: the old ladder is about a time that is no longer true.
            if ($appointment->wasChanged('scheduled_at')) {
                $this->reminders->rescheduleFor($appointment);

                return;
            }

            if (! $appointment->wasChanged('status')) {
                return;
            }

            // Cancelled, completed, checked in, or anything else that is no
            // longer "booked and not yet arrived": stop reminding.
            in_array((string) $appointment->status, AppointmentReminderService::REMINDABLE_STATUSES, true)
                ? $this->reminders->scheduleFor($appointment)
                : $this->reminders->cancelFor($appointment);
        }, $appointment, 'updated');
    }

    public function deleted(Appointment $appointment): void
    {
        $this->guard(fn () => $this->reminders->cancelFor($appointment), $appointment, 'deleted');
    }

    private function guard(callable $work, Appointment $appointment, string $event): void
    {
        try {
            $work();
        } catch (\Throwable $e) {
            Log::warning('appointment reminders: could not be kept in step', [
                'appointment_id' => $appointment->id,
                'event'          => $event,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
