<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The platform-wide notification centre — every authenticated user (patient,
 * professional, facility staff, admin) reads their OWN notification_events by
 * recipient_user_id, exactly as the patient page always has. "Unread" means
 * created after users.notifications_seen_at.
 *
 *  - index() renders the full page (shared across all portals)
 *  - feed()  is the JSON the topbar bell polls: unread count + latest events,
 *            each flagged is_new, so the client can raise a sound/badge
 *  - markRead() stamps notifications_seen_at = now()
 */
class NotificationCenterController extends Controller
{
    private const FEED_LIMIT = 15;

    public function index(Request $request): View
    {
        $user   = Auth::user();
        $seenAt = $user->notifications_seen_at;

        $events = DB::table('notification_events')
            ->where('recipient_user_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        $unread = $this->unreadCount((string) $user->id, $seenAt);

        return view('portals.shared.notifications', compact('events', 'unread', 'seenAt'));
    }

    public function feed(Request $request): JsonResponse
    {
        $user   = Auth::user();
        $seenAt = $user->notifications_seen_at;

        $rows = DB::table('notification_events')
            ->where('recipient_user_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->limit(self::FEED_LIMIT)
            ->get();

        $events = $rows->map(function ($e) use ($seenAt) {
            $payload = json_decode($e->payload_json ?? '{}', true) ?: [];

            return [
                'id'         => $e->id,
                'event_type' => $e->event_type,
                'category'   => $e->communication_type,
                'priority'   => $e->priority,
                'title'      => $payload['title'] ?? null,
                'body'       => $payload['body'] ?? ($payload['message'] ?? null),
                'url'        => $payload['url'] ?? null,
                'created_at' => $e->created_at,
                'is_new'     => $seenAt === null || $e->created_at > $seenAt,
            ];
        });

        return response()->json([
            'unread' => $this->unreadCount((string) $user->id, $seenAt),
            'events' => $events,
        ]);
    }

    public function markRead(Request $request): RedirectResponse|JsonResponse
    {
        DB::table('users')->where('id', Auth::id())->update(['notifications_seen_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', __('notifications_ui.all_read'));
    }

    private function unreadCount(string $userId, $seenAt): int
    {
        return DB::table('notification_events')
            ->where('recipient_user_id', $userId)
            ->when($seenAt, fn ($q) => $q->where('created_at', '>', $seenAt))
            ->count();
    }
}
