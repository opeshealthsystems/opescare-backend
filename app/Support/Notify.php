<?php

namespace App\Support;

use App\Models\FacilityRoleAssignment;
use App\Modules\Notifications\Models\NotificationEvent;
use App\Models\ProviderDevice;
use App\Modules\Notifications\Services\PushNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * One tiny front door for raising an in-app notification. Everything writes a
 * row to `notification_events` keyed by `recipient_user_id` — the same table the
 * bell and the notification centre read, and unread is "created after the
 * recipient's users.notifications_seen_at". The heavier CommunicationRouterService
 * fans SMS/email out from the same table; this helper is for the in-app feed and
 * for the sound/push signal, which only need the event row to exist.
 *
 * Callers pass a `category` (blood | pharmacy | appointment | admin | account …)
 * — the sound layer keys its alert off `event_type`, so use a stable, descriptive
 * event_type such as 'blood_order_received' or 'pharmacy_order_received'.
 */
class Notify
{
    /** Raise one in-app notification for a single user. Returns the event id. */
    public static function toUser(
        string $userId,
        string $eventType,
        string $category,
        array $payload = [],
        string $priority = 'normal',
        ?string $actorId = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
    ): string {
        $event = NotificationEvent::create([
            'uuid'                     => (string) Str::uuid(),
            'event_type'               => $eventType,
            'communication_type'       => $category,
            'actor_id'                 => $actorId,
            'recipient_user_id'        => $userId,
            'recipient_type'           => 'user',
            'related_resource_type'    => $resourceType,
            'related_resource_id'      => $resourceId,
            'payload_json'             => json_encode($payload),
            'priority'                 => $priority,
            'status'                   => 'routed',
            'requires_acknowledgement' => in_array($priority, ['critical', 'urgent'], true),
        ]);

        // Mirror the in-app event out to the user's registered mobile devices via
        // FCM. Best-effort only: a push failure must never break the in-app feed.
        self::pushToUserDevices($userId, $eventType, $payload);

        return (string) $event->id;
    }

    /**
     * Raise the same notification for every active staff member at a facility —
     * used when an order lands at a blood bank or pharmacy and any of the desk
     * team should see (and hear) it. Optionally restrict to certain role names.
     * Returns how many people were notified.
     */
    public static function toFacilityStaff(
        string $facilityId,
        string $eventType,
        string $category,
        array $payload = [],
        array $roleNames = [],
        string $priority = 'normal',
        ?string $actorId = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
    ): int {
        $query = FacilityRoleAssignment::query()
            ->where('facility_id', $facilityId)
            ->where('is_active', true);

        if (! empty($roleNames)) {
            $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');
            $query->whereIn('role_id', $roleIds);
        }

        // Fall back to primary_facility_id holders if the facility has no explicit
        // role assignments yet, so a freshly-set-up blood bank still gets pinged.
        $userIds = $query->pluck('user_id')->all();
        if (empty($userIds)) {
            $userIds = DB::table('users')->where('primary_facility_id', $facilityId)->pluck('id')->all();
        }

        $userIds = array_unique(array_map('strval', $userIds));

        foreach ($userIds as $uid) {
            self::toUser($uid, $eventType, $category, $payload, $priority, $actorId, $resourceType, $resourceId);
        }

        return count($userIds);
    }

    /**
     * Fan the just-written in-app event out to the user's registered mobile
     * devices as an FCM push. Strictly best-effort and silent: if push is not
     * configured, the user has no active device tokens, or the send throws for
     * any reason, this degrades to a no-op — it must NEVER break the in-app path.
     *
     * One lookup per user (indexed on provider_devices.[user_id, status]), so a
     * many-staff facility fan-out stays at a single query per recipient.
     */
    private static function pushToUserDevices(string $userId, string $eventType, array $payload): void
    {
        try {
            $service = app(PushNotificationService::class);
            if (! $service->isConfigured()) {
                return;
            }

            // Active, push-enabled devices for this user that actually carry a token.
            $tokens = ProviderDevice::query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('push_active', true)
                ->whereNotNull('push_token')
                ->pluck('push_token')
                ->unique()
                ->all();

            if (empty($tokens)) {
                return;
            }

            $title = $payload['title'] ?? Str::headline($eventType);
            $body  = $payload['body'] ?? ($payload['message'] ?? '');
            $data  = [
                'event_type' => $eventType,
                'url'        => $payload['url'] ?? '',
            ];

            foreach ($tokens as $token) {
                $service->sendToDevice((string) $token, (string) $title, (string) $body, $data);
            }
        } catch (\Throwable $e) {
            // Swallow — push is a signal, not the source of truth. Debug-level only.
            Log::debug('Notify: push fan-out failed', [
                'user_id'    => $userId,
                'event_type' => $eventType,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
