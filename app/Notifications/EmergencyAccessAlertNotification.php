<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\ComposedMail;
use App\Mail\Support\EmailContent;
use App\Mail\Support\RecipientLocale;
use Illuminate\Notifications\Notification;

/**
 * Emergency Access Alert
 *
 * Fired immediately after every successful emergency health profile pull.
 * Notifiable target: the patient's registered email (if any) and platform
 * admin users (fetched by EmergencyAccessController before dispatching).
 *
 * Compliance: MINSANTE Law No. 2010/012 requires patients to be informed
 * when their data is accessed — emergency access is no exception.
 */
class EmergencyAccessAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string  $patientHealthId,
        public readonly string  $patientName,
        public readonly string  $accessedAt,
        public readonly string  $facilityId,
        public readonly string  $emergencyReason,
        public readonly string  $ipAddress,
    ) {}

    public function via(object $notifiable): array
    {
        // Always store in database so the patient can see it in Access Logs.
        // Add 'mail' when SMTP is configured.
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'             => 'emergency_access_alert',
            'title'            => 'Emergency Access to Your Health Record',
            'message'          => "Your health record ({$this->patientHealthId}) was accessed under emergency "
                . "protocols at {$this->accessedAt}. Reason stated: \"{$this->emergencyReason}\". "
                . "This access has been audited. If you believe this was unauthorised, contact support immediately.",
            'health_id'        => $this->patientHealthId,
            'patient_name'     => $this->patientName,
            'facility_id'      => $this->facilityId,
            'emergency_reason' => $this->emergencyReason,
            'accessed_at'      => $this->accessedAt,
            'ip_address'       => $this->ipAddress,
            'severity'         => 'high',
            'action_url'       => '/patient/access-logs',
            'action_label'     => 'View Access Logs',
        ];
    }

    /**
     * Branded, bilingual, multipart. Composed in the PATIENT's language
     * (RecipientLocale), not the clinician's session. The subject deliberately
     * carries no Health ID — it used to; the ID stays in the body, where the
     * previous email already placed it. The old link pointed at
     * /portals/patient/logs, a path that no longer exists.
     */
    public function toMail(object $notifiable): ComposedMail
    {
        $locale = RecipientLocale::for($notifiable, $this->locale);

        return ComposedMail::inLocale($locale, fn () => EmailContent::make(
            __('mail.emergency.subject'),
            __('mail.emergency.title'),
        )
            ->preheader(__('mail.emergency.preheader'))
            ->eyebrow(__('mail.emergency.eyebrow'), 'urgent')
            ->greeting(trim($this->patientName) !== ''
                ? __('mail.common.greeting_named', ['name' => trim($this->patientName)])
                : __('mail.common.greeting'))
            ->paragraph(__('mail.emergency.intro'))
            ->fact(__('mail.emergency.fact_health_id'), $this->patientHealthId)
            ->fact(__('mail.emergency.fact_when'), $this->accessedAt)
            ->fact(__('mail.emergency.fact_reason'), $this->emergencyReason)
            ->paragraph(__('mail.emergency.logged'))
            ->button(__('mail.emergency.action'), route('portals.patient.access-logs'))
            ->panel(__('mail.emergency.panel_title'), [
                __('mail.emergency.panel_1'),
                __('mail.emergency.panel_2'),
            ])
            ->footnote(__('mail.emergency.legal'))
            ->signature(__('mail.chrome.signature'))
            ->preferences(route('portals.patient.notifications.preferences')))->forNotifiable($notifiable, $this);
    }
}
