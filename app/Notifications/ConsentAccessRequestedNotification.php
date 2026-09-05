<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * "A facility is asking to open your record."
 *
 * Raised once, at the moment a clinician creates a ConsentRequest. Without it
 * the ask is invisible: a request the patient never sees is the same as no
 * request at all, and the whole point of the consent loop is that the patient
 * decides.
 *
 * ── WHY THIS EXISTS ALONGSIDE notification_events ─────────────────────────
 *
 * This app has two notification stores, read by two different audiences:
 *
 *   notification_events   the STAFF/web bell (App\Support\Notify,
 *                         CommunicationRouterService) and the SMS/email
 *                         fan-out. ConsentService already writes here.
 *   notifications         Laravel's own store — the ONLY thing
 *                         GET /api/mobile/notifications reads
 *                         (MobileNotificationController::scopedQuery).
 *
 * The recipient of a consent request is a patient holding a phone, so the row
 * has to exist in the second one too. Nothing new is invented: this is the
 * same `app/Notifications/*` → `->notify()` path
 * EmergencyAccessAlertNotification already uses to tell a patient their record
 * was opened.
 *
 * ── DELIBERATELY NOT ShouldQueue ──────────────────────────────────────────
 *
 * QUEUE_CONNECTION is `database` here. A queued notification would sit in the
 * jobs table until a worker ran, so on a host with no worker the patient would
 * be told nothing at all while the API reported the request as created. The
 * write is one INSERT; it happens now.
 *
 * ── AND DELIBERATELY NOT 'mail' ───────────────────────────────────────────
 *
 * `via()` is database-only. The email/SMS half of this event is already routed
 * by NotificationService → CommunicationRouterService from the same call site;
 * adding 'mail' here would send the patient the same thing twice.
 *
 * No clinical content appears in the payload — a consent request names the
 * facility, the purpose and the scope being asked for, never anything from the
 * record it is asking about.
 */
class ConsentAccessRequestedNotification extends Notification
{
    /**
     * @param  list<string>  $requestedScope
     */
    public function __construct(
        public readonly string $consentRequestId,
        public readonly string $facilityName,
        public readonly string $purpose,
        public readonly array $requestedScope,
        public readonly int $durationMinutes,
        public readonly ?string $justification,
        public readonly ?string $requestExpiresAt,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string,mixed> */
    public function toDatabase(object $notifiable): array
    {
        // `type` is what MobileNotificationController::format() keys off, and
        // categoryFor() has no 'consent' arm — it falls through to 'system',
        // which is the right bucket for a privacy decision.
        return [
            'type'          => 'consent_request_received',
            'title'         => __('consent_requests.notify_title'),
            'message'       => __('consent_requests.notify_body', [
                'facility' => $this->facilityName,
                'purpose'  => __('consent_requests.purpose.' . $this->purpose),
            ]),
            'severity'      => 'high',
            'consent_request_id' => $this->consentRequestId,
            'facility_name'      => $this->facilityName,
            'purpose'            => $this->purpose,
            'requested_scope'    => $this->requestedScope,
            'duration_minutes'   => $this->durationMinutes,
            'justification'      => $this->justification,
            'request_expires_at' => $this->requestExpiresAt,
            'action_url'         => '/privacy',
            'action_label'       => __('consent_requests.notify_action'),
        ];
    }
}
