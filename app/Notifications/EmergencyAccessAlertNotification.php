<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
            'action_url'       => '/portals/patient/logs',
            'action_label'     => 'View Access Logs',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[OpesCare] Emergency Access Alert — ' . $this->patientHealthId)
            ->greeting('Health Record Access Alert')
            ->line("Your health record was accessed under emergency protocols.")
            ->line("**Health ID:** {$this->patientHealthId}")
            ->line("**Accessed at:** {$this->accessedAt}")
            ->line("**Emergency reason stated:** {$this->emergencyReason}")
            ->line("This access has been logged and audited. If you believe this was unauthorised, contact our support team immediately.")
            ->action('View Your Access Logs', url('/portals/patient/logs'))
            ->line('OpesCare — Protecting your health identity under Cameroon Law No. 2010/012.');
    }
}
