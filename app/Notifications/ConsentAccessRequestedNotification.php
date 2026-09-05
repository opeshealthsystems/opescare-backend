<?php

namespace App\Notifications;

use App\Support\PatientVocabulary;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

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
        $locale = App::getLocale();

        // The purpose LABEL, resolved by the one vocabulary the platform has
        // (App\Support\PatientVocabulary → lang/{en,fr}/consent_requests.php),
        // not by a bare __() on a concatenated key. A purpose outside the
        // allow-list used to put `consent_requests.purpose.<whatever>` into the
        // sentence a patient reads before opening their medical record; it now
        // comes back as the raw code, marked unrecognised, which is the honest
        // thing to show and the thing this class must not dress up.
        $purpose = PatientVocabulary::purpose($this->purpose, $locale);

        // The sentence itself was rebuilt around this label rather than the
        // other way round: `notify_body` used to read "...for :purpose.", and
        // every label in the list is a standalone noun phrase, so it landed as
        // "...for Treating this patient." — and in French as "...pour La prise
        // en charge de ce patient.", article and capital and all. The reason
        // now has its own clause.
        $scopes = PatientVocabulary::scopes($this->requestedScope, $locale);

        // `type` is what MobileNotificationController::format() keys off, and
        // categoryFor() has no 'consent' arm — it falls through to 'system',
        // which is the right bucket for a privacy decision.
        return [
            'type'          => 'consent_request_received',
            'title'         => __('consent_requests.notify_title'),
            'message'       => __('consent_requests.notify_body', [
                'facility' => $this->facilityName,
                'purpose'  => $purpose['label'] ?? $this->purpose,
            ]),
            'severity'      => 'high',
            'consent_request_id' => $this->consentRequestId,
            'facility_name'      => $this->facilityName,
            'purpose'            => $this->purpose,
            'purpose_label'      => $purpose['label'] ?? null,
            'purpose_recognised' => $purpose['recognised'] ?? null,
            'requested_scope'    => $this->requestedScope,
            // The scope in words, so anything reading this row renders the
            // same names the clinician picked instead of re-deriving them.
            'scope_items'        => $scopes['items'],
            'scope_note'         => $scopes['note'],
            'duration_minutes'   => $this->durationMinutes,
            'duration_label'     => PatientVocabulary::duration($this->durationMinutes, $locale)['label'] ?? null,
            'justification'      => $this->justification,
            'request_expires_at' => $this->requestExpiresAt,
            'action_url'         => '/privacy',
            'action_label'       => __('consent_requests.notify_action'),
        ];
    }
}
