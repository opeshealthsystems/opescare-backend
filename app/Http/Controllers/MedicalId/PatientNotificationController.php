<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Http\Middleware\GuardianAccessMiddleware;
use App\Models\AuditEvent;
use App\Models\Patient;
use App\Models\User;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PatientNotificationController — patient notifications centre + channel
 * preferences.
 *
 * The inbox reads notification_events rows addressed to the patient whose
 * record is open (recipient_user_id — see inboxIds() for the two id
 * conventions that coexist there). Channel preferences are stored per
 * (user_id, category) in notification_preferences.
 *
 * WHOSE INBOX. The signed-in user's own patient, or — while a guardian has
 * switched to a dependent (GuardianAccessMiddleware sets `viewing_patient`
 * and `guardian_link`) — the dependent's.
 *
 * PER PERSON. Read state and preferences belong to a person, not to a record:
 *  - the "seen" watermark on the own inbox is users.notifications_seen_at, as
 *    it always was; while switched it is the guardian's OWN watermark for THAT
 *    dependent (see seenAt()), so a guardian marking things read never clears
 *    the dependent's badge and vice-versa;
 *  - a dependent's preferences are shown read-only and cannot be changed from
 *    a guardian's session at all, whatever the link's access level.
 *
 * GUARDIANS. A read-only link may READ but not mark read — refused with a
 * flash and a redirect back, never a 403 page or a 500. A full link may mark
 * read; the AuditEvent records the guardian as actor on the dependent's
 * record, as PatientPortalController::generateTemporaryQr does.
 */
class PatientNotificationController extends Controller
{
    private const CATEGORIES = ['appointments', 'lab_results', 'prescriptions', 'billing', 'messages', 'general'];
    private const CHANNELS    = ['email', 'sms', 'whatsapp', 'push', 'voice', 'dashboard'];

    /** audit_events.action_type written when a guardian marks a dependent's inbox read. */
    public const MARK_READ_ACTION = 'notifications_marked_read';

    public function __construct(private readonly PortalContextService $ctx) {}

    // ── Whose inbox is open ─────────────────────────────────────────────────

    /**
     * The patient whose inbox is open: the dependent while a guardian has
     * switched, else the signed-in user's own patient (null for an account
     * that holds no Health ID yet).
     */
    private function inboxPatient(): ?Patient
    {
        if (request()->attributes->has('viewing_patient')) {
            return request()->attributes->get('viewing_patient');
        }
        return Auth::user()?->patient;
    }

    /** True while a guardian has a dependent's record open. */
    private function isSwitched(): bool
    {
        return request()->attributes->has('viewing_patient');
    }

    /** True while a guardian views a dependent through a link that does not grant writes. */
    private function isReadOnlyGuardian(): bool
    {
        return GuardianAccessMiddleware::isReadOnly(request()->attributes->get('guardian_link'));
    }

    private function viewingName(): string
    {
        $p = request()->attributes->get('viewing_patient');
        return $p ? trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) : '';
    }

    /**
     * The polite refusal for a read-only guardian: back to where they were
     * with a flash, never a 403 page and never a 500.
     */
    private function refuseReadOnly(): RedirectResponse
    {
        return back()->with('error', __('family.read_only_refused', ['name' => $this->viewingName()]));
    }

    /**
     * The ids a patient's notifications are addressed to.
     *
     * notification_events.recipient_user_id normally holds a users.id, but
     * ConsentService falls back to the patients.id when the patient has no
     * account — and a child registered by a guardian never has one, so the
     * patient id is often the only key. Off a switch the signed-in user's own
     * id is always included: an account without a Health ID still has a feed.
     * On a switch the guardian's own id is deliberately NOT included.
     *
     * @return list<string>
     */
    private function inboxIds(?Patient $patient): array
    {
        $ids = $this->isSwitched() ? [] : [(string) Auth::id()];

        if ($patient) {
            $ids[] = (string) $patient->id;
            foreach (User::where('patient_id', $patient->id)->pluck('id') as $uid) {
                $ids[] = (string) $uid;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * The "seen" watermark for the open inbox — per PERSON.
     *
     * Own inbox: users.notifications_seen_at. While switched: the newest
     * `notifications_marked_read` AuditEvent this guardian wrote against this
     * dependent — the audit trail is the durable, attributable record of the
     * guardian having read the inbox, and it needs no column on a dependent
     * who may have no account. Best-effort, like every audit write.
     */
    private function seenAt(?Patient $patient): ?Carbon
    {
        if ($this->isSwitched() && $patient) {
            $at = AuditEvent::query()
                ->where('actor_id', (string) Auth::id())
                ->where('patient_id', (string) $patient->id)
                ->where('action_type', self::MARK_READ_ACTION)
                ->max('created_at');

            // audit_events.created_at is timestamptz; notification_events.created_at
            // is a plain timestamp in app-local wall time. Normalise before the
            // value becomes a query binding, or the comparison drifts by the offset.
            return $at ? Carbon::parse($at)->setTimezone(config('app.timezone')) : null;
        }

        $at = Auth::user()?->notifications_seen_at;

        return $at ? Carbon::parse($at) : null;
    }

    // ── Pages ───────────────────────────────────────────────────────────────

    /** Inbox — the open patient's notifications, newest first. */
    public function index(Request $request): View
    {
        $patient = $this->inboxPatient();
        abort_if($patient === null, 403);

        $inboxIds = $this->inboxIds($patient);
        $seenAt   = $this->seenAt($patient);

        $events = DB::table('notification_events')
            ->whereIn('recipient_user_id', $inboxIds)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unread = DB::table('notification_events')
            ->whereIn('recipient_user_id', $inboxIds)
            ->when($seenAt, fn ($q) => $q->where('created_at', '>', $seenAt))
            ->count();

        return view('portals.patient.notifications', [
            'events'   => $events,
            'unread'   => $unread,
            'seenAt'   => $seenAt,
            'readOnly' => $this->isReadOnlyGuardian(),
            'viewing'  => $this->isSwitched() ? $patient : null,
        ]);
    }

    /** Mark all of the open patient's notifications as read — for the person reading them. */
    public function markAllRead(Request $request): RedirectResponse
    {
        $patient = $this->inboxPatient();
        abort_if($patient === null, 403);

        if ($this->isSwitched()) {
            if ($this->isReadOnlyGuardian()) {
                return $this->refuseReadOnly();
            }

            // The guardian's watermark for this dependent IS the audit event
            // (see seenAt()); the dependent's own account is left untouched.
            $link = $request->attributes->get('guardian_link');
            $this->ctx->auditPatientAccess(
                actionType:   self::MARK_READ_ACTION,
                resourceType: 'Patient',
                resourceId:   $patient->id,
                patientId:    $patient->id,
                extra: [
                    'after_state' => [
                        'on_behalf_of_patient_id' => (string) $patient->id,
                        'family_link_id'          => $link?->id,
                        'access_level'            => $link?->access_level,
                    ],
                ],
            );

            return redirect()->route('portals.patient.notifications')
                ->with('success', __('notifications.all_read'));
        }

        DB::table('users')->where('id', Auth::id())->update(['notifications_seen_at' => now()]);

        return redirect()->route('portals.patient.notifications')
            ->with('success', __('notifications.all_read'));
    }

    /**
     * Preferences — per-category channel toggles + language. While switched
     * these are the DEPENDENT's (their account's rows when they have one,
     * defaults otherwise) and render read-only.
     */
    public function preferences(Request $request): View
    {
        $switched = $this->isSwitched();
        $patient  = $this->inboxPatient();

        $prefUserId = $switched
            ? ($patient ? User::where('patient_id', $patient->id)->value('id') : null)
            : Auth::id();

        $rows = $prefUserId
            ? DB::table('notification_preferences')->where('user_id', (string) $prefUserId)->get()->keyBy('category')
            : collect();

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
            'readOnly'   => $switched,
            'viewing'    => $switched ? $patient : null,
        ]);
    }

    /** Persist preferences — upsert one row per category for this user. Never for a dependent. */
    public function updatePreferences(Request $request): RedirectResponse
    {
        if ($this->isSwitched()) {
            return back()->with('error', __('family.inbox_prefs_refused', ['name' => $this->viewingName()]));
        }

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
