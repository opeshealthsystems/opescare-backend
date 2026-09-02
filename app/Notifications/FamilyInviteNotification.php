<?php
namespace App\Notifications;

use App\Models\FamilyLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly FamilyLink $link,
        public readonly string $rawToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $guardian   = $this->link->guardianUser?->name ?? $this->link->guardianUser?->email ?? 'Someone';
        $patient    = $this->link->dependentPatient?->first_name ?? 'you';
        $acceptUrl  = route('portals.patient.family.invite.accept', $this->rawToken);
        $ttlHours   = config('family.invite_ttl_hours', 48);

        return (new MailMessage)
            ->subject("OpesCare: Family Access Request")
            ->greeting("Hello,")
            ->line("{$guardian} is requesting guardian access to {$patient}'s OpesCare health record.")
            ->line("Access level: " . ($this->link->access_level === 'full' ? 'Full access' : 'Read only'))
            ->line("Relationship: " . ucfirst(str_replace('_', ' ', $this->link->relationship)))
            ->action('Review & Accept', $acceptUrl)
            ->line("This invite expires in {$ttlHours} hours.")
            ->line("If you did not expect this request, you can safely ignore this email.");
    }

    public function toArray(object $notifiable): array
    {
        return ['invite_token_hint' => substr($this->rawToken, 0, 8) . '...'];
    }
}
