<?php
namespace App\Notifications;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;

class AppointmentSmsReminder extends Notification
{
    public function __construct(public readonly Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'vonage'];
    }

    public function toVonage(object $notifiable): \Illuminate\Notifications\Messages\VonageMessage
    {
        return (new \Illuminate\Notifications\Messages\VonageMessage)
            ->content($this->buildMessage($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'appointment_reminder',
            'appointment_id' => $this->appointment->id,
            'message'        => $this->buildMessage($notifiable),
        ];
    }

    private function buildMessage(object $notifiable): string
    {
        $appt     = $this->appointment;
        $date     = $appt->scheduled_at
            ? Carbon::parse($appt->scheduled_at)->toDateString()
            : 'TBD';
        $time     = $appt->scheduled_at
            ? Carbon::parse($appt->scheduled_at)->format('H:i')
            : 'TBD';
        $facility = $appt->facility?->name ?? 'your facility';

        // "Reply CANCEL to cancel" used to end this message. Nothing on this
        // platform reads inbound SMS, so it was an instruction the software
        // could not carry out; a patient who followed it would have kept an
        // appointment they believed they had cancelled.
        return "OpesCare Reminder: Your appointment on {$date} at {$time} at {$facility} is confirmed. " .
               "OpesCare Support: " . (config('opescare.support_phone') ?: config('opescare.support_email') ?: 'support@opescare.com');
    }
}
