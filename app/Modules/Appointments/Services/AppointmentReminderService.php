<?php

namespace App\Modules\Appointments\Services;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Facility;
use App\Models\Patient;
use App\Services\FrontDesk\FrontDeskService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Appointment reminders — scheduling them, cancelling them, and delivering one.
 *
 * ── What was wrong before ──────────────────────────────────────────────────
 * `opescare:send-appointment-reminders` has run every five minutes, reported
 * success on every run, and never sent a single reminder. Four separate
 * reasons, each of which alone was enough:
 *
 *   1. `scheduleReminders()` had NO CALLERS. Nothing in the codebase ever
 *      created a reminder row, so the due query was always empty and the cron
 *      was permanently, quietly green.
 *   2. It wrote columns the table does not have — `hours_before`, `channels`,
 *      `cancelled_at`. The real ones are `remind_before_hours`, `channel` and
 *      `status`. The first row it ever tried to write would have thrown.
 *   3. It refused any appointment that was not already `confirmed`, while a
 *      patient booking themselves in is created `scheduled`
 *      (PatientSelfBookingService) — so the commonest appointment on this
 *      platform could never have qualified.
 *   4. Delivery went over `vonage`, which is not configured on this
 *      deployment, and wrote its `database` copy against the USER while the
 *      patient app reads notifications on the PATIENT
 *      (MobileNotificationController). Even had one sent, nobody would have
 *      seen it.
 *
 * ── What it does now ───────────────────────────────────────────────────────
 * A reminder is about ATTENDANCE, not paperwork, so any appointment that is
 * booked and not yet arrived earns the ladder — the same set the front desk
 * treats as "booked, nobody has checked in". Delivery is the patient's own
 * bell plus a push, in the patient's language, over the exact path every other
 * patient notification here already takes. No SMS is attempted: no SMS
 * transport is configured on this deployment, and a reminder that claims to
 * have been texted is worse than one that was never sent.
 */
class AppointmentReminderService
{
    /**
     * How far ahead of the appointment each rung goes out.
     *
     * Deliberately not per-facility configurable yet: one ladder that
     * demonstrably fires beats three that are settings nobody has set.
     */
    public const OFFSETS_HOURS = [48, 24, 2];

    /**
     * Statuses that still deserve a reminder — booked, nobody has arrived.
     *
     * Taken from the front desk rather than restated, so a status added to its
     * vocabulary cannot silently stop earning reminders.
     */
    public const REMINDABLE_STATUSES = FrontDeskService::CHECK_IN_FROM;

    /**
     * Schedule the ladder. Idempotent: it adds only the rungs that are missing
     * and never resurrects one that was cancelled.
     *
     * @return int how many rows were created
     */
    public function scheduleFor(Appointment|string $appointment): int
    {
        $appointment = $appointment instanceof Appointment
            ? $appointment
            : Appointment::query()->find($appointment);

        if (! $appointment || ! $appointment->scheduled_at) {
            return 0;
        }

        if (! in_array((string) $appointment->status, self::REMINDABLE_STATUSES, true)) {
            return 0;
        }

        $created = 0;

        foreach (self::OFFSETS_HOURS as $hours) {
            $sendAt = $appointment->scheduled_at->copy()->subHours($hours);

            // A rung already in the past is not scheduled at all. Telling
            // somebody who booked for tomorrow that their appointment is in 48
            // hours would be false, and sending it late is worse than silence.
            if ($sendAt->isPast()) {
                continue;
            }

            $exists = AppointmentReminder::query()
                ->where('appointment_id', $appointment->id)
                ->where('remind_before_hours', $hours)
                ->exists();

            if ($exists) {
                continue;
            }

            AppointmentReminder::create([
                'appointment_id'      => $appointment->id,
                'channel'             => 'push',
                'status'              => 'pending',
                'remind_before_hours' => $hours,
                'scheduled_at'        => $sendAt,
            ]);

            $created++;
        }

        return $created;
    }

    /** Cancel every pending rung — the appointment went away. */
    public function cancelFor(Appointment|string $appointment): int
    {
        $id = $appointment instanceof Appointment ? $appointment->id : $appointment;

        return AppointmentReminder::query()
            ->where('appointment_id', $id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /**
     * The appointment moved: drop the pending ladder and build one against the
     * new time. Rungs already SENT are left alone — they were true when they
     * went out, and a record of what the patient was told is not ours to erase.
     */
    public function rescheduleFor(Appointment|string $appointment): int
    {
        $appointment = $appointment instanceof Appointment
            ? $appointment
            : Appointment::query()->find($appointment);

        if (! $appointment) {
            return 0;
        }

        AppointmentReminder::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', 'pending')
            ->delete();

        return $this->scheduleFor($appointment);
    }

    /** Every pending reminder whose moment has come. */
    public function getDueReminders(): \Illuminate\Database\Eloquent\Collection
    {
        return AppointmentReminder::query()
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with('appointment.patient')
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Deliver one reminder.
     *
     * True only when the patient actually has a notification waiting. A
     * reminder for an appointment that has since been cancelled, or whose
     * patient is gone, is retired as `cancelled` rather than sent: that is not
     * a failure, and it must not be retried every five minutes forever.
     */
    public function deliver(AppointmentReminder $reminder): bool
    {
        $appointment = $reminder->appointment;

        if (! $appointment || ! in_array((string) $appointment->status, self::REMINDABLE_STATUSES, true)) {
            $reminder->update(['status' => 'cancelled']);

            return false;
        }

        $patient = $appointment->patient
            ?? Patient::withoutGlobalScopes()->find($appointment->patient_id);

        if (! $patient) {
            $reminder->update(['status' => 'cancelled']);

            return false;
        }

        $locale = $this->patientLocale((string) $patient->getKey());
        $when   = $appointment->scheduled_at?->copy()
            ->timezone(config('app.clinic_timezone', 'Africa/Douala'));

        $replace = [
            'facility' => $appointment->facility?->name
                ?? Facility::withoutGlobalScope('isolate_demo')->whereKey($appointment->facility_id)->value('name')
                ?? __('appointments_reminder.the_facility', [], $locale),
            'date'     => $when?->locale($locale)->translatedFormat('j F Y') ?? '',
            'time'     => $when?->format('H:i') ?? '',
        ];

        $title = __('appointments_reminder.title_' . (int) $reminder->remind_before_hours . 'h', [], $locale);
        $body  = __('appointments_reminder.body', $replace, $locale);

        try {
            DatabaseNotification::create([
                'id'              => (string) Str::uuid(),
                'type'            => 'appointment_reminder',
                'notifiable_type' => $patient->getMorphClass(),
                'notifiable_id'   => $patient->getKey(),
                'data'            => [
                    'type'           => 'appointment_reminder',
                    'severity'       => 'normal',
                    'title'          => $title,
                    'message'        => $body,
                    'appointment_id' => $appointment->id,
                    'action_url'     => '/appointments',
                    'action_label'   => __('appointments_reminder.action', [], $locale),
                ],
                'read_at'         => null,
            ]);
        } catch (\Throwable $e) {
            // The bell IS the reminder. If it could not be written the row
            // stays pending and the next run tries again.
            Log::error('appointment reminder: bell failed', [
                'reminder_id' => $reminder->id,
                'error'       => $e->getMessage(),
            ]);

            $reminder->update([
                'error_message' => Str::limit($e->getMessage(), 500),
                'retry_count'   => (int) $reminder->retry_count + 1,
            ]);

            return false;
        }

        // Push is a signal on top of the bell, never the record of it: a device
        // with no token, or a push service that is not configured, must not
        // turn a delivered reminder into a failed one.
        if (class_exists(\App\Support\PatientPush::class)) {
            try {
                \App\Support\PatientPush::send($patient, 'appointment_reminder', $title, $body, [
                    'appointment_id' => $appointment->id,
                    'action_url'     => '/appointments',
                ]);
            } catch (\Throwable $e) {
                Log::debug('appointment reminder: push failed', [
                    'reminder_id' => $reminder->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $reminder->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

        return true;
    }

    /** The language this patient reads the app in, falling back to the app's. */
    private function patientLocale(string $patientId): string
    {
        try {
            $preferred = DB::table('mobile_app_settings')->where('patient_id', $patientId)->value('preferred_language');
        } catch (\Throwable) {
            $preferred = null;
        }

        $preferred = strtolower(substr((string) $preferred, 0, 2));

        return in_array($preferred, ['en', 'fr'], true) ? $preferred : app()->getLocale();
    }
}
