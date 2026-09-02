<?php

namespace App\Modules\Communications\Services;

use App\Models\Patient;
use App\Modules\Notifications\Models\NotificationEvent;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Services\NotificationPreferenceService;
use App\Modules\Notifications\Services\SmsNotificationService;
use App\Modules\Notifications\Services\EmailNotificationService;
use Illuminate\Support\Str;

class CommunicationRouterService
{
    public function __construct(
        private NotificationPreferenceService $preferenceService,
        private SmsNotificationService        $smsService,
        private EmailNotificationService      $emailService,
    ) {}

    public function route(
        string $eventType,
        string $recipientUserId,
        array  $payload,
        string $priority = 'normal',
        string $category = 'health_updates'
    ): array {
        // 1. Privacy gate: strip PHI from external delivery payload
        $securePayload = $this->enforcePrivacy($eventType, $payload);

        // 2. Deduplication — skip for critical / urgent events
        if ($priority !== 'critical' && $priority !== 'urgent') {
            if ($this->isDuplicateEvent($recipientUserId, $eventType)) {
                return ['status' => 'suppressed', 'reason' => 'DEDUPLICATION_LIMIT'];
            }
        }

        // 3. Persist the notification event record
        $event = NotificationEvent::create([
            'uuid'                     => Str::uuid(),
            'event_type'               => $eventType,
            'communication_type'       => $category,
            'recipient_user_id'        => $recipientUserId,
            'recipient_type'           => 'user',
            'payload_json'             => json_encode($securePayload),
            'priority'                 => $priority,
            'status'                   => 'routed',
            'requires_acknowledgement' => in_array($priority, ['critical', 'urgent']),
        ]);

        $deliveries = [];
        // voice / whatsapp / push are handled by dedicated providers — not wired here
        $channels = ['sms', 'email'];

        foreach ($channels as $channel) {
            if (!$this->preferenceService->isChannelEnabled($recipientUserId, $category, $channel, $priority)) {
                continue;
            }

            $recipient = $this->getRecipientContact($recipientUserId, $channel);
            if (!$recipient) {
                continue; // no contact info for this channel — skip silently
            }

            $delivery = NotificationDelivery::create([
                'uuid'                  => Str::uuid(),
                'notification_event_id' => $event->id,
                'channel'               => $channel,
                'recipient'             => $recipient,
                'provider'              => $this->resolveProvider($channel),
                'status'                => 'pending',
            ]);

            $this->dispatchDelivery($delivery, $securePayload, $channel, $recipient);
            $deliveries[] = $delivery;
        }

        return [
            'status'     => 'success',
            'event'      => $event,
            'deliveries' => $deliveries,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Look up the patient's real contact for a given channel.
     * Returns null when no contact info is available.
     */
    private function getRecipientContact(string $userId, string $channel): ?string
    {
        $patient = Patient::find($userId);

        return match ($channel) {
            'sms'   => $patient?->phone_number ?: null,
            'email' => $patient?->email ?: null,
            default => null,
        };
    }

    private function resolveProvider(string $channel): string
    {
        return match ($channel) {
            'sms'   => config('services.twilio.sid') ? 'twilio' : 'log',
            'email' => config('mail.default', 'smtp'),
            default => 'demo_provider',
        };
    }

    /**
     * Dispatch a delivery and update its status (delivered or failed).
     * Errors are caught so a single failed channel never aborts the others.
     */
    private function dispatchDelivery(
        NotificationDelivery $delivery,
        array  $payload,
        string $channel,
        string $recipient
    ): void {
        try {
            match ($channel) {
                'sms'   => $this->smsService->send($recipient, $payload['body'] ?? ''),
                'email' => $this->emailService->send(
                    $recipient,
                    $payload['subject'] ?? 'OpesCare Notification',
                    $payload['body']    ?? ''
                ),
                default => null,
            };

            $delivery->update([
                'status'       => 'delivered',
                'sent_at'      => now(),
                'delivered_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                'status'        => 'failed',
                'failed_at'     => now(),
                'error_code'    => 'DISPATCH_ERROR',
                'error_message' => substr($e->getMessage(), 0, 255),
            ]);
        }
    }

    /**
     * Absolute masking of protected health information for external channels.
     */
    private function enforcePrivacy(string $eventType, array $payload): array
    {
        if (isset($payload['diagnosis']) || isset($payload['result_value']) || isset($payload['sensitive'])) {
            $payload['body'] = 'A new health update is available in OpesCare. Log in securely to view it.';
            unset($payload['diagnosis'], $payload['result_value'], $payload['sensitive']);
        }
        return $payload;
    }

    /**
     * Simple deduplication — suppress identical events within 5 minutes.
     */
    private function isDuplicateEvent(string $recipientUserId, string $eventType): bool
    {
        return NotificationEvent::where('recipient_user_id', $recipientUserId)
            ->where('event_type', $eventType)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
    }
}
