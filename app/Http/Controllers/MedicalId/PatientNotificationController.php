<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PatientNotificationController — patient notifications centre + channel
 * preferences. The inbox reads NotificationEvent rows addressed to the patient
 * (notifiable_type='Patient'); read-state lives on the event's read_at. Channel
 * preferences are stored per (user_id, category) in notification_preferences.
 */
class PatientNotificationController extends Controller
{
    private const CATEGORIES = ['appointments', 'lab_results', 'prescriptions', 'billing', 'messages', 'general'];
    private const CHANNELS    = ['email', 'sms', 'whatsapp', 'push', 'voice', 'dashboard'];

    /** Inbox — the patient's notifications, newest first. */
    public function index(Request $request): View
    {
        $user = Auth::user();
        abort_if($user?->patient === null, 403);

        $seenAt = $user->notifications_seen_at;

        $events = DB::table('notification_events')
            ->where('recipient_user_id', (string) $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unread = DB::table('notification_events')
            ->where('recipient_user_id', (string) $user->id)
            ->when($seenAt, fn ($q) => $q->where('created_at', '>', $seenAt))
            ->count();

        return view('portals.patient.notifications', compact('events', 'unread', 'seenAt'));
    }

    /** Mark all of the patient's notifications as read. */
    public function markAllRead(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_if($user?->patient === null, 403);

        DB::table('users')->where('id', $user->id)->update(['notifications_seen_at' => now()]);

        return redirect()->route('portals.patient.notifications')
            ->with('success', __('notifications.all_read'));
    }

    /** Preferences — per-category channel toggles + language. */
    public function preferences(Request $request): View
    {
        $userId = (string) Auth::id();

        $rows = DB::table('notification_preferences')->where('user_id', $userId)->get()->keyBy('category');

        $prefs = [];
        foreach (self::CATEGORIES as $cat) {
            $row = $rows->get($cat);
            $prefs[$cat] = [
                'email'     => $row->email_enabled     ?? true,
                'sms'       => $row->sms_enabled       ?? true,
                'whatsapp'  => $row->whatsapp_enabled  ?? true,
                'push'      => $row->push_enabled      ?? true,
                'voice'     => $row->voice_enabled     ?? false,
                'dashboard' => $row->dashboard_enabled ?? true,
            ];
        }

        $language = optional($rows->first())->language ?? app()->getLocale();

        return view('portals.patient.notification_preferences', [
            'categories' => self::CATEGORIES,
            'channels'   => self::CHANNELS,
            'prefs'      => $prefs,
            'language'   => $language,
        ]);
    }

    /** Persist preferences — upsert one row per category for this user. */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $userId = (string) Auth::id();

        $validated = $request->validate([
            'language'        => 'nullable|in:en,fr',
            'prefs'           => 'nullable|array',
        ]);

        $language = $validated['language'] ?? app()->getLocale();
        $posted   = $request->input('prefs', []);

        foreach (self::CATEGORIES as $cat) {
            $catChannels = $posted[$cat] ?? [];
            DB::table('notification_preferences')->updateOrInsert(
                ['user_id' => $userId, 'category' => $cat],
                [
                    'email_enabled'     => isset($catChannels['email']),
                    'sms_enabled'       => isset($catChannels['sms']),
                    'whatsapp_enabled'  => isset($catChannels['whatsapp']),
                    'push_enabled'      => isset($catChannels['push']),
                    'voice_enabled'     => isset($catChannels['voice']),
                    'dashboard_enabled' => isset($catChannels['dashboard']),
                    'language'          => $language,
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ],
            );
        }

        return redirect()->route('portals.patient.notifications.preferences')
            ->with('success', __('notifications.prefs_saved'));
    }
}
