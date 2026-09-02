<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

/**
 * Mobile Patient API — Notification Center
 *
 * Reads Laravel's standard `notifications` table (populated today via
 * ->notify() for real patient-facing events: EmergencyAccessAlertNotification,
 * HealthIdExpiryNotification, AppointmentSmsReminder, FamilyEventNotification —
 * see app/Notifications/*). No separate notification store is introduced;
 * this controller only exposes what already gets written for the
 * authenticated patient (direct Patient-notifiable rows) and their linked
 * user account (User-notifiable rows), never any other patient's data.
 */
class MobileNotificationController extends Controller
{
    /**
     * GET /api/mobile/notifications
     * Query params: limit (default 30, max 100), before (ISO8601 cursor, optional)
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 30), 100);

        $query = $this->scopedQuery($request);
        if ($before = $request->query('before')) {
            $query->where('created_at', '<', $before);
        }

        $rows = $query->orderByDesc('created_at')->limit($limit)->get();

        return response()->json([
            'data'          => $rows->map(fn (DatabaseNotification $n) => $this->format($n))->values(),
            'unread_count'  => $this->scopedQuery($request)->whereNull('read_at')->count(),
        ]);
    }

    /**
     * GET /api/mobile/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->scopedQuery($request)->whereNull('read_at')->count(),
        ]);
    }

    /**
     * POST /api/mobile/notifications/{id}/read
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->scopedQuery($request)->where('id', $id)->first();
        if (!$notification) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json(['message' => __('api.notification_read')]);
    }

    /**
     * POST /api/mobile/notifications/mark-all-read
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $this->scopedQuery($request)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => __('api.all_notifications_read')]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Notifications belonging to the authenticated patient directly, plus
     * (when a linked user account exists) notifications sent to that user —
     * scoped strictly to the identity resolved by auth.mobile middleware,
     * never to a caller-supplied id.
     *
     * `notifiable_type` must be matched against the model's *morph class*, not
     * its FQCN: AppServiceProvider registers a morph map ('patient' =>
     * Patient::class), so Laravel writes `patient` — comparing to
     * Patient::class matched nothing and made every patient notification
     * invisible. Both spellings are accepted so rows written on either side of
     * the morph-map registration still resolve.
     */
    private function scopedQuery(Request $request)
    {
        $patientId     = $request->attributes->get('patient_id');
        $patientUserId = $request->attributes->get('patient_user_id');

        $patientTypes = array_values(array_unique([Patient::class, (new Patient)->getMorphClass()]));
        $userTypes    = array_values(array_unique([User::class, (new User)->getMorphClass()]));

        return DatabaseNotification::query()
            ->where(function ($q) use ($patientId, $patientUserId, $patientTypes, $userTypes) {
                $q->where(function ($q2) use ($patientId, $patientTypes) {
                    $q2->whereIn('notifiable_type', $patientTypes)->where('notifiable_id', $patientId);
                });
                if ($patientUserId) {
                    $q->orWhere(function ($q2) use ($patientUserId, $userTypes) {
                        $q2->whereIn('notifiable_type', $userTypes)->where('notifiable_id', $patientUserId);
                    });
                }
            });
    }

    /**
     * Normalize the heterogeneous `data` payloads written by the various
     * Notification classes (App\Notifications\*) into one flat shape the
     * mobile app can render without knowing each class's own field names.
     */
    private function format(DatabaseNotification $n): array
    {
        $data = $n->data ?? [];

        $type  = $data['type'] ?? $data['event_key'] ?? 'general';
        $title = $data['title'] ?? $this->titleFor($type, $data);

        return [
            'id'           => $n->id,
            'type'         => $type,
            'category'     => $this->categoryFor($type),
            'title'        => $title,
            'message'      => $data['message'] ?? $data['description'] ?? '',
            'severity'     => $data['severity'] ?? 'normal',
            'action_url'   => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'read'         => $n->read_at !== null,
            'created_at'   => $n->created_at?->toIso8601String(),
        ];
    }

    private function titleFor(string $type, array $data): string
    {
        return match ($type) {
            'appointment_reminder' => __('api.notif_title_appointment_reminder'),
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function categoryFor(string $type): string
    {
        return match (true) {
            str_contains($type, 'appointment') => 'appointments',
            str_contains($type, 'message') => 'messages',
            str_contains($type, 'health_id') => 'health',
            str_contains($type, 'lab') || str_contains($type, 'prescription') => 'health',
            str_contains($type, 'emergency') || str_contains($type, 'security') => 'system',
            str_contains($type, 'family') => 'system',
            default => 'system',
        };
    }
}
