<?php

namespace App\Modules\Appointments\Services;

use App\Models\Appointment;
use App\Models\AppointmentReminder;

/**
 * AppointmentReminderService — Schedules and tracks appointment reminders.
 *
 * Reminders are sent via SMS, WhatsApp, push notification, and email
 * based on patient communication preferences and facility configuration.
 *
 * Reminder schedule (configurable per facility):
 *  - 48 hours before appointment
 *  - 24 hours before appointment
 *  - 2 hours before appointment (SMS/push only)
 *
 * Opt-out: patients who have opted out of notifications must not receive reminders.
 */
class AppointmentReminderService
{
    /**
     * Schedule all reminders for an appointment.
     * Called when appointment is confirmed.
     */
    public function scheduleReminders(string $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->status !== 'confirmed') {
            return;
        }

        $reminderIntervals = [
            ['hours_before' => 48, 'channels' => ['sms', 'email', 'push']],
            ['hours_before' => 24, 'channels' => ['sms', 'push']],
            ['hours_before' => 2,  'channels' => ['sms', 'push']],
        ];

        foreach ($reminderIntervals as $interval) {
            $scheduledAt = $appointment->scheduled_at->subHours($interval['hours_before']);

            if ($scheduledAt->isFuture()) {
                AppointmentReminder::firstOrCreate(
                    [
                        'appointment_id'  => $appointmentId,
                        'hours_before'    => $interval['hours_before'],
                    ],
                    [
                        'channels'         => $interval['channels'],
                        'scheduled_at'     => $scheduledAt,
                        'status'           => 'pending',
                    ]
                );
            }
        }
    }

    /**
     * Cancel all pending reminders for an appointment (on cancellation/reschedule).
     */
    public function cancelReminders(string $appointmentId): int
    {
        return AppointmentReminder::where('appointment_id', $appointmentId)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    /**
     * Mark a reminder as sent.
     */
    public function markSent(string $reminderId): AppointmentReminder
    {
        $reminder = AppointmentReminder::findOrFail($reminderId);
        $reminder->update(['status' => 'sent', 'sent_at' => now()]);
        return $reminder->fresh();
    }

    /**
     * Returns all pending reminders due to be sent now.
     */
    public function getDueReminders(): \Illuminate\Database\Eloquent\Collection
    {
        return AppointmentReminder::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with('appointment.patient')
            ->get();
    }
}
