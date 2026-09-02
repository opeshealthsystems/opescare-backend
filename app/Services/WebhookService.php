<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\WebhookEvent;
use App\Models\WebhookSubscription;

/**
 * WebhookService
 *
 * FIX H-2 (audit 2026-06-07): dispatch() previously delivered events to ALL active
 * subscriptions with no facility or client scope. Facility A events were delivered
 * to Facility B subscribers â€” a cross-facility data leak (OWASP API1 / ISO 27001 A.9.1).
 *
 * Fix: dispatch() now accepts an optional $facilityId. When provided, only subscriptions
 * belonging to that facility receive the event. All patient-data events MUST pass
 * $facilityId. The $clientId filter further scopes to a single client when needed.
 *
 * FIX H-3 (audit 2026-06-07): replay() previously re-delivered to ALL active subscriptions
 * matching the event type, regardless of who triggered the replay.
 * Fix: replay() now requires the caller to pass their $clientId. Only subscriptions
 * belonging to that client receive the replay â€” preventing cross-client event injection.
 */
class WebhookService
{
    /** Webhook payload contract version — see docs/API-VERSIONING.md. */
    public const SCHEMA_VERSION = 'v1';

    /**
     * Dispatch an event to matching webhook subscriptions.
     *
     * @param  string       $eventType     e.g. 'patient.updated'
     * @param  array        $resourceData  Event payload body
     * @param  string|null  $facilityId    Scope delivery to this facility (required for all patient events)
     * @param  string|null  $clientId      Scope delivery to a single client (optional, more restrictive)
     * @return WebhookEvent
     */
    public static function dispatch(
        string  $eventType,
        array   $resourceData,
        ?string $facilityId = null,
        ?string $clientId   = null,
    ): WebhookEvent {
        $payload = [
            'event_type'     => $eventType,
            'schema_version' => self::SCHEMA_VERSION,
            'occurred_at'    => now()->toIso8601String(),
            'resource'       => $resourceData,
        ];

        $event = WebhookEvent::create([
            'event_type'     => $eventType,
            'schema_version' => self::SCHEMA_VERSION,
            'payload'        => $payload,
            'facility_id'    => $facilityId,
            'client_id'      => $clientId,
        ]);

        $payload['event_id'] = $event->id;

        self::dispatchToSubscriptions(
            eventId:    $event->id,
            eventType:  $eventType,
            payload:    $payload,
            facilityId: $facilityId,
            clientId:   $clientId,
        );

        return $event;
    }

    /**
     * Re-dispatch a previously persisted event.
     *
     * [FIX H-3] Now scoped to the $clientId of the caller â€” a client cannot replay
     * events into another client's delivery pipeline.
     *
     * FIX (audit 2026-06-18): If $clientId is null, throws \InvalidArgumentException
     * instead of silently broadcasting to all clients. Previously, a null $clientId
     * bypassed the scope filter entirely, allowing cross-client event injection.
     */
    public static function replay(
        WebhookEvent $event,
        ?string      $replayedBy = null,
        ?string      $clientId   = null,
    ): void {
        if ($clientId === null) {
            throw new \InvalidArgumentException(
                'replay() requires $clientId to prevent cross-client event injection. '
                . 'Pass the authenticated integration client ID.'
            );
        }

        $payload             = $event->payload;
        $payload['event_id'] = $event->id;

        self::dispatchToSubscriptions(
            eventId:    $event->id,
            eventType:  $event->event_type,
            payload:    $payload,
            facilityId: $event->facility_id ?? null,
            clientId:   $clientId,
            replayedBy: $replayedBy,
        );
    }

    // â”€â”€ Private helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function dispatchToSubscriptions(
        string  $eventId,
        string  $eventType,
        array   $payload,
        ?string $facilityId = null,
        ?string $clientId   = null,
        ?string $replayedBy = null,
    ): void {
        // [FIX H-2] Scoped query â€” never fan-out blindly to all clients/facilities.
        $query = WebhookSubscription::where('status', 'active');

        if ($facilityId !== null) {
            $query->where('facility_id', $facilityId);
        }

        if ($clientId !== null) {
            $query->where('client_id', $clientId);
        }

        $subscriptions = $query->get();

        foreach ($subscriptions as $subscription) {
            if (! in_array($eventType, (array) $subscription->subscribed_events)) {
                continue;
            }

            // replayed_by records the acting integration client_id (string column
            // as of 2026_06_12_000001 — previously uuid, which 500'd real clients).
            if ($replayedBy !== null) {
                \App\Models\WebhookReplay::create([
                    'webhook_event_id'    => $eventId,
                    'webhook_endpoint_id' => $subscription->id,
                    'replayed_by'         => $replayedBy,
                    'status'              => 'pending',
                ]);
            }

            SendWebhookJob::dispatch($subscription->id, $payload)
                ->onQueue('webhooks');
        }
    }
}
