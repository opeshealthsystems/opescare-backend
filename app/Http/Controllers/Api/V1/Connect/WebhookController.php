<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Http\Controllers\Controller;
use App\Enums\OpesCareErrorCode;
use App\Models\WebhookEvent;
use App\Models\WebhookSubscription;
use App\Services\AuditLogger;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WebhookController
 *
 * FIX H-3 (audit 2026-06-07): replayEvent() previously allowed any authenticated client
 * to replay ANY event regardless of ownership — a BOLA/IDOR vulnerability (OWASP API1).
 * Fix: replay() now passes the caller's $clientId so delivery is scoped to their own
 * subscriptions only.
 *
 * FIX M-5 (audit 2026-06-07): createSubscription() accepted any URL including
 * http://127.0.0.1, http://localhost, http://10.x.x.x — internal SSRF targets (OWASP API7).
 * Fix: callback_url is validated against private/loopback address ranges before saving.
 */
class WebhookController extends Controller
{
    /**
     * POST /api/v1/connect/webhooks/subscriptions
     */
    public function createSubscription(Request $request): JsonResponse
    {
        $data = $request->validate([
            'callback_url'        => ['required', 'url', 'max:500'],
            'subscribed_events'   => 'required|array|min:1',
            'subscribed_events.*' => 'string|max:100',
        ]);

        $callbackUrl = $data['callback_url'];
        $clientId    = $request->attributes->get('integration_client_id', 'unknown_client');
        $facilityId  = $request->attributes->get('facility_id');

        // [FIX M-5] Reject SSRF targets — internal/loopback addresses must not be
        // reachable from the server. OWASP API7 / ISO 27001 A.13.1.
        if ($this->isPrivateUrl($callbackUrl)) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::VALIDATION_FAILED->value,
                'message'    => 'callback_url must point to a publicly reachable HTTPS endpoint. '
                    . 'Loopback, private, and link-local addresses are not permitted.',
            ], 422);
        }

        $secret = 'whsec_' . bin2hex(random_bytes(16));

        $sub = WebhookSubscription::create([
            'client_id'         => $clientId,
            'facility_id'       => $facilityId,
            'callback_url'      => $callbackUrl,
            'webhook_secret'    => $secret,
            'subscribed_events' => $data['subscribed_events'],
            'status'            => 'active',
        ]);

        AuditLogger::log($request, 'webhook_subscription_created', 'webhook_subscription', $sub->id);

        return response()->json([
            'status'            => 'active',
            'subscription_id'   => $sub->id,
            'callback_url'      => $callbackUrl,
            'subscribed_events' => $data['subscribed_events'],
            'webhook_secret'    => $secret, // shown once — store securely, not retrievable again
            'created_at'        => $sub->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ], 201);
    }

    /**
     * POST /api/v1/connect/webhooks/events/{eventId}/replay
     *
     * [FIX H-3] Replay is now scoped to the requesting client's own subscriptions.
     * A client cannot force delivery into another client's webhook pipeline.
     */
    public function replayEvent(Request $request, string $eventId): JsonResponse
    {
        $clientId = $request->attributes->get('integration_client_id', 'unknown_client');

        $event = WebhookEvent::find($eventId);

        if (! $event) {
            return response()->json([
                'status'     => 'not_found',
                'error_code' => OpesCareErrorCode::RESOURCE_NOT_FOUND->value,
                'message'    => __('api.webhook_event_not_found'),
            ], 404);
        }

        // [FIX H-3] Pass $clientId to replay() — delivery scoped to this client only.
        WebhookService::replay($event, $clientId, $clientId);

        AuditLogger::log($request, 'webhook_event_replayed', 'webhook_event', $event->id);

        return response()->json([
            'status'      => 'replayed',
            'event_id'    => $event->id,
            'event_type'  => $event->event_type,
            'replayed_at' => now()->toIso8601String(),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Returns true if the URL resolves to a private/loopback/link-local address.
     *
     * Blocks loopback (127.x, ::1), private (10.x, 172.16-31.x, 192.168.x),
     * link-local (169.254.x), localhost hostname, *.local domains.
     * In production also blocks non-HTTPS schemes.
     */
    private function isPrivateUrl(string $url): bool
    {
        $parsed = parse_url($url);
        $host   = $parsed['host'] ?? '';

        // Reject non-HTTPS in production
        if (app()->isProduction() && ($parsed['scheme'] ?? '') !== 'https') {
            return true;
        }

        // Reject localhost / .local hostnames
        if (
            strtolower($host) === 'localhost' ||
            str_ends_with(strtolower($host), '.local')
        ) {
            return true;
        }

        // Resolve hostname → IP for range checks
        $ip = filter_var($host, FILTER_VALIDATE_IP)
            ? $host
            : gethostbyname($host);

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false; // Unresolvable — allow (will fail at delivery time)
        }

        // FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE rejects:
        // 10.x, 172.16-31.x, 192.168.x, 127.x, 169.254.x, ::1
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
