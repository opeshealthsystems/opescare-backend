<?php
namespace App\Notifications;

use App\Models\FamilyLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly FamilyLink $link,
        public readonly string $eventKey,
        public readonly string $eventDescription,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        if ($this->link->notificationPrefFor($this->eventKey, 'portal')) {
            $channels[] = 'database';
        }
        if ($this->link->notificationPrefFor($this->eventKey, 'email')) {
            $channels[] = 'mail';
        }
        // SMS: add 'vonage' or 'twilio' channel here when gateway is configured
        return $channels ?: ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->link->dependentPatient?->first_name ?? 'your dependent';
        return (new MailMessage)
            ->subject("OpesCare: Update for {$name}")
            ->line($this->eventDescription)
            ->action('View in Portal', route('portals.patient.family'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event_key'    => $this->eventKey,
            'description'  => $this->eventDescription,
            'patient_id'   => $this->link->dependent_patient_id,
            'patient_name' => trim(
                ($this->link->dependentPatient?->first_name ?? '') . ' ' .
                ($this->link->dependentPatient?->last_name ?? '')
            ),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
