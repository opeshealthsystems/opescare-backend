<?php

namespace App\Console\Commands;

use App\Modules\Appointments\Services\AppointmentReminderService;
use App\Models\NotificationPreference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Notifications\AppointmentSmsReminder;

class SendAppointmentRemindersCommand extends Command
{
    protected $signature = 'opescare:send-appointment-reminders';

    protected $description = 'Dispatch pending appointment reminders that are due to be sent';

    public function __construct(private readonly AppointmentReminderService $reminderService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $due = $this->reminderService->getDueReminders();

        if ($due->isEmpty()) {
            $this->info('No appointment reminders due.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($due as $reminder) {
            try {
                $appointment = $reminder->appointment;
                $patient     = $appointment?->patient;

                if (!$patient) {
                    $this->reminderService->markSent($reminder->id);
                    continue;
                }

                // Honour patient opt-out — skip if they opted out of notifications
                $prefs = NotificationPreference::where('user_id', $patient->user_id ?? $patient->id)->first();
                if ($prefs && !$prefs->appointment_reminders) {
                    $this->reminderService->markSent($reminder->id);
                    continue;
                }

                // Dispatch notification — NotificationService handles channel routing
                // (SMS via Twilio, push via FCM, email) based on patient preferences.
                if ($patient->user) {
                    $patient->user->notify(new AppointmentSmsReminder($appointment));
                }

                $this->reminderService->markSent($reminder->id);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('AppointmentReminder dispatch failed', [
                    'reminder_id' => $reminder->id,
                    'error'       => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->info("Reminders sent: {$sent}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
