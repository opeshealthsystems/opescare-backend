<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Health ID Expiry Warning
 *
 * Sent to patients (via their linked user account) when their Health ID is
 * approaching expiry. The `NotifyExpiringHealthIds` Artisan command dispatches
 * this notification once the `renewal_required_at` window opens.
 *
 * Compliance: MINSANTE Digital Health Strategy 2026–2030 — 10-year validity,
 * 90-day renewal window with 3 monthly reminders before expiry.
 */
class HealthIdExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $healthId,
        public readonly string $name,
        public readonly string $expiresAt,   // Y-m-d
        public readonly int    $daysLeft,
    ) {}

    public function via(object $notifiable): array
    {
        // Database channel for the in-app notification bell.
        // Add 'mail' once SMTP is configured.
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'health_id_expiry_warning',
            'title'      => 'Your Health ID expires soon',
            'message'    => "Your Health ID ({$this->healthId}) will expire on {$this->expiresAt} "
                . "({$this->daysLeft} days remaining). Please visit your patient portal to request a renewal.",
            'health_id'  => $this->healthId,
            'expires_at' => $this->expiresAt,
            'days_left'  => $this->daysLeft,
            'severity'   => $this->daysLeft <= 30 ? 'high' : 'medium',
            'action_url' => '/portals/patient/health-id',
            'action_label' => 'Renew Health ID',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysLeft <= 30 ? 'URGENT: ' : '';

        return (new MailMessage)
            ->subject("{$urgency}[OpesCare] Your Health ID expires in {$this->daysLeft} days")
            ->greeting("Hello {$this->name},")
            ->line("Your OpesCare Health ID **{$this->healthId}** will expire on **{$this->expiresAt}**.")
            ->line("{$this->daysLeft} days remain before your ID becomes invalid.")
            ->line("To continue accessing health services uninterrupted, please renew your Health ID through your patient portal before the expiry date.")
            ->action('Renew My Health ID', url('/portals/patient/health-id'))
            ->line('If you believe you received this in error, contact our support team.')
            ->line('OpesCare — Digital Health Identity per MINSANTE Strategy 2026–2030.');
    }
}
