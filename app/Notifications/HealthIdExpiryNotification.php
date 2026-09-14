<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\ComposedMail;
use App\Mail\Support\EmailContent;
use App\Mail\Support\RecipientLocale;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

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

    /**
     * Composed in the patient's language. The old button pointed at
     * /portals/patient/health-id — the portal moved to /patient/* and no such
     * page exists; the Health ID lives on the patient home page, so the button
     * now targets route('portals.patient'). The subject carries the day count
     * only, never the ID.
     */
    public function toMail(object $notifiable): ComposedMail
    {
        $locale = RecipientLocale::for($notifiable, $this->locale);
        $urgent = $this->daysLeft <= 30;

        return ComposedMail::inLocale($locale, function () use ($locale, $urgent) {
            $date = Carbon::parse($this->expiresAt)->locale($locale)->isoFormat('LL');

            return EmailContent::make(
                __($urgent ? 'mail.health_id_expiry.subject_urgent' : 'mail.health_id_expiry.subject', ['days' => $this->daysLeft]),
                __('mail.health_id_expiry.title'),
            )
                ->preheader(__('mail.health_id_expiry.preheader', ['date' => $date]))
                ->eyebrow(__($urgent ? 'mail.health_id_expiry.eyebrow_urgent' : 'mail.health_id_expiry.eyebrow'), $urgent ? 'urgent' : 'default')
                ->greeting(trim($this->name) !== ''
                    ? __('mail.common.greeting_named', ['name' => trim($this->name)])
                    : __('mail.common.greeting'))
                ->paragraph(__('mail.health_id_expiry.intro', ['date' => $date]))
                ->fact(__('mail.health_id_expiry.fact_health_id'), $this->healthId)
                ->fact(__('mail.health_id_expiry.fact_expires'), $date)
                ->fact(__('mail.health_id_expiry.fact_days_left'), (string) $this->daysLeft)
                ->paragraph(__('mail.health_id_expiry.days_left', ['days' => $this->daysLeft]))
                ->button(__('mail.health_id_expiry.action'), route('portals.patient'))
                ->panel(__('mail.health_id_expiry.panel_title'), [
                    __('mail.health_id_expiry.panel_1'),
                    __('mail.health_id_expiry.panel_2'),
                ])
                ->footnote(__('mail.health_id_expiry.error_note'))
                ->footnote(__('mail.health_id_expiry.legal'))
                ->signature(__('mail.chrome.signature'))
                ->preferences(route('portals.patient.notifications.preferences'));
        })->forNotifiable($notifiable, $this);
    }
}
