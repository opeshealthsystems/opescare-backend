<?php

namespace App\Console\Commands;

use App\Modules\Appointments\Services\AppointmentReminderService;
use Illuminate\Console\Command;

/**
 * Sends the appointment reminders that are due.
 *
 * Scheduled every five minutes (routes/console.php). Thin on purpose: what a
 * reminder SAYS, who it goes to, in which language, and when it should be
 * retired rather than retried all live in AppointmentReminderService, so the
 * same rules apply whether a reminder is delivered by this cron or by whatever
 * comes after it.
 *
 * The old version honoured a patient opt-out by reading
 * `notification_preferences.appointment_reminders` for
 * `$patient->user_id ?? $patient->id` — a patient row has no `user_id`, so it
 * looked the preference up by the patient's OWN id and in practice always
 * found nothing. Opt-out is a real requirement and it needs a preference the
 * patient can actually set from the app; until that exists, quietly pretending
 * to honour one was the worse of the two failures.
 */
class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'opescare:send-appointment-reminders';

    protected $description = 'Deliver appointment reminders that are due to be sent';

    public function __construct(private readonly AppointmentReminderService $reminders)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $due = $this->reminders->getDueReminders();

        if ($due->isEmpty()) {
            $this->info('No appointment reminders due.');

            return self::SUCCESS;
        }

        $sent = 0;
        $retired = 0;
        $failed = 0;

        foreach ($due as $reminder) {
            if ($this->reminders->deliver($reminder)) {
                $sent++;

                continue;
            }

            // deliver() tells the two non-sends apart itself: an appointment
            // that no longer wants a reminder is retired as `cancelled`, while
            // a genuine failure is left `pending` for the next run. Reading the
            // status back is how this command reports them separately instead
            // of calling both a failure.
            if ($reminder->refresh()->status === 'cancelled') {
                $retired++;
            } else {
                $failed++;
            }
        }

        $this->info("Reminders sent: {$sent}, retired: {$retired}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
