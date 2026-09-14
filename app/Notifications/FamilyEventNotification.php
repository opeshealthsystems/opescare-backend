<?php
namespace App\Notifications;

use App\Models\FamilyLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\ComposedMail;
use App\Mail\Support\EmailContent;
use App\Mail\Support\RecipientLocale;
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

    /**
     * The subject no longer names the dependent — a child's name in a subject
     * line is visible on a locked phone and in every inbox preview. The name
     * moves into the title; the event description stays in the body as the
     * caller wrote it.
     */
    public function toMail(object $notifiable): ComposedMail
    {
        $locale = RecipientLocale::for($notifiable, $this->locale);

        return ComposedMail::inLocale($locale, function () {
            $name = trim((string) ($this->link->dependentPatient?->first_name ?? ''));
            $name = $name !== '' ? $name : __('mail.family_event.dependent_fallback');

            return EmailContent::make(__('mail.family_event.subject'), __('mail.family_event.title', ['name' => $name]))
                ->preheader(__('mail.family_event.preheader'))
                ->eyebrow(__('mail.family_event.eyebrow'))
                ->greeting(__('mail.common.greeting'))
                ->paragraph(__('mail.family_event.intro'))
                ->paragraph($this->eventDescription)
                ->button(__('mail.family_event.action'), route('portals.patient.family'))
                ->footnote(__('mail.family_event.note'))
                ->signature(__('mail.chrome.signature'))
                ->preferences(route('portals.patient.notifications.preferences'));
        })->forNotifiable($notifiable, $this);
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
