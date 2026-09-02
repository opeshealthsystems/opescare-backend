<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Services\MessagePermissionService;
use App\Modules\Messaging\Services\MessagingService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * StaffMessagingController — the other end of patient messaging.
 *
 * Patients could already open a thread with their care team
 * (PatientMessagingController → MessagingService), and those threads were
 * persisted correctly with staff added as participants. There was simply no
 * route under portals/staff that read them, so every message a patient sent
 * went into a mailbox nobody could open.
 *
 * This controller adds no messaging machinery of its own. It reuses the
 * Messaging module — same models, same MessagingService, same
 * MessagePermissionService::canViewThread() gate the patient side and the
 * Connect API both use — and layers ONE extra restriction on top:
 *
 *   a staff member may only touch a thread that has, as an active participant,
 *   a patient registered at the staff member's OWN facility.
 *
 * That is strictly narrower than participation alone. Being a participant on
 * some thread is not, by itself, a right to read it from this portal; a stale
 * participant row on a patient who has since moved facilities must not become a
 * back door into another hospital's correspondence.
 *
 * The facility comes from PortalContextService::facilityId() — the session
 * context set by RequireFacilityContext — and never from the request.
 */
class StaffMessagingController extends Controller
{
    public function __construct(
        private readonly MessagingService $messaging,
        private readonly MessagePermissionService $permissions,
        private readonly PortalContextService $ctx,
    ) {}

    // ── Context helpers ──────────────────────────────────────────────────

    private function actorUserId(): string
    {
        $userId = Auth::id();

        if (! is_string($userId) || ! Str::isUuid($userId)) {
            abort(401, 'Authenticated user context is required.');
        }

        return $userId;
    }

    private function facilityId(): string
    {
        $facilityId = $this->ctx->facilityId();

        abort_if($facilityId === null, 403, 'No facility context for this account.');

        return $facilityId;
    }

    /**
     * Thread ids where this staff member is an active participant.
     *
     * This is the outer bound on everything below, which keeps the facility
     * check cheap: it only ever runs over threads the user is already in,
     * never over the whole table.
     */
    private function participatingThreadIds(string $userId): Collection
    {
        return MessageThreadParticipant::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->pluck('thread_id');
    }

    /**
     * Map of thread_id => patient_id for threads whose participants include a
     * patient of the given facility.
     *
     * Note the deliberate hop through PHP rather than a single SQL subquery:
     * message_thread_participants.user_id is a varchar column while users.id is
     * a uuid, and Postgres refuses `varchar IN (SELECT uuid)` outright. Casting
     * ids in PHP is both correct and bounded by the staff member's own threads.
     */
    private function facilityPatientByThread(Collection $threadIds, string $facilityId): Collection
    {
        if ($threadIds->isEmpty()) {
            return collect();
        }

        $rows = MessageThreadParticipant::query()
            ->whereIn('thread_id', $threadIds)
            ->where('status', 'active')
            ->get(['thread_id', 'user_id']);

        $candidateIds = $rows->pluck('user_id')
            ->filter(fn ($id) => is_string($id) && Str::isUuid($id))
            ->unique()
            ->values();

        if ($candidateIds->isEmpty()) {
            return collect();
        }

        // user_id => patient_id, for the participants that are patient accounts.
        $patientIdByUser = User::query()
            ->whereIn('id', $candidateIds->all())
            ->whereNotNull('patient_id')
            ->pluck('patient_id', 'id');

        if ($patientIdByUser->isEmpty()) {
            return collect();
        }

        // …narrowed to the patients registered at THIS facility.
        $ownPatientIds = Patient::query()
            ->whereIn('id', $patientIdByUser->values()->unique()->all())
            ->where('facility_id', $facilityId)
            ->pluck('id')
            ->flip();

        $map = collect();

        foreach ($rows as $row) {
            $patientId = $patientIdByUser[$row->user_id] ?? null;

            if ($patientId !== null && $ownPatientIds->has($patientId)) {
                $map->put($row->thread_id, $patientId);
            }
        }

        return $map;
    }

    /**
     * Full gate for a single thread: participation (the module's own rule,
     * including its legal-hold carve-out) AND facility ownership of the patient.
     * Returns the patient id, which the caller needs for the audit record.
     */
    private function authorizeThread(MessageThread $thread, string $userId, string $facilityId): string
    {
        if (! $this->permissions->canViewThread($userId, $thread->uuid)) {
            abort(403);
        }

        $patientId = $this->facilityPatientByThread(collect([$thread->id]), $facilityId)->get($thread->id);

        abort_if($patientId === null, 403);

        return $patientId;
    }

    // ── Inbox ────────────────────────────────────────────────────────────

    public function index(): View
    {
        $userId     = $this->actorUserId();
        $facilityId = $this->facilityId();

        $threadIds  = $this->participatingThreadIds($userId);
        $patientMap = $this->facilityPatientByThread($threadIds, $facilityId);

        $threads = $patientMap->isEmpty()
            ? collect()
            : MessageThread::query()
                ->whereIn('id', $patientMap->keys()->all())
                ->with([
                    'messages' => fn ($q) => $q->orderByDesc('created_at')->limit(1),
                    'participants',
                ])
                ->orderByDesc('updated_at')
                ->get();

        $patients = $threads->isEmpty()
            ? collect()
            : Patient::query()->whereIn('id', $patientMap->values()->unique()->all())->get()->keyBy('id');

        $rows = $threads->map(function (MessageThread $thread) use ($userId, $patientMap, $patients) {
            $last    = $thread->messages->first();
            $snippet = $last ? Str::limit($this->messaging->decryptBody($last->body), 120) : null;

            $participant = $thread->participants->firstWhere('user_id', $userId);
            $unread      = false;

            if ($last && $last->sender_id !== $userId) {
                $lastRead = $participant?->last_read_at;
                $unread   = $lastRead === null || $last->created_at?->gt($lastRead);
            }

            $patient = $patients->get($patientMap->get($thread->id));

            return [
                'uuid'         => $thread->uuid,
                'title'        => $thread->title,
                'status'       => $thread->status,
                'snippet'      => $snippet,
                'unread'       => $unread,
                'last_at'      => $last?->created_at ?? $thread->updated_at,
                'context_type' => $thread->context_type,
                'patient_name' => $patient ? trim($patient->first_name . ' ' . $patient->last_name) : null,
                'health_id'    => $patient?->health_id,
            ];
        });

        return view('portals.staff.messages', ['threads' => $rows]);
    }

    // ── Thread ───────────────────────────────────────────────────────────

    public function show(string $thread): View
    {
        $userId      = $this->actorUserId();
        $facilityId  = $this->facilityId();
        $threadModel = MessageThread::where('uuid', $thread)->firstOrFail();

        $patientId = $this->authorizeThread($threadModel, $userId, $facilityId);

        // Opening a thread is patient-data access. Audited the same way
        // PatientPortalController audits every record it opens.
        $this->ctx->auditPatientAccess(
            actionType:   'staff_message_thread_view',
            resourceType: 'MessageThread',
            resourceId:   $threadModel->uuid,
            patientId:    $patientId,
        );

        MessageThreadParticipant::query()
            ->where('thread_id', $threadModel->id)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        $threadModel->load(['messages' => fn ($q) => $q->orderBy('created_at')]);

        $messages = $threadModel->messages->map(fn ($msg) => [
            'uuid'      => $msg->uuid,
            'body'      => $this->messaging->decryptBody($msg->body),
            'sender_id' => $msg->sender_id,
            'sent_at'   => $msg->created_at,
        ]);

        return view('portals.staff.messages-thread', [
            'thread'   => $threadModel,
            'messages' => $messages,
            'userId'   => $userId,
            'patient'  => Patient::find($patientId),
        ]);
    }

    // ── Reply ────────────────────────────────────────────────────────────

    public function send(Request $request, string $thread): RedirectResponse
    {
        $userId      = $this->actorUserId();
        $facilityId  = $this->facilityId();
        $threadModel = MessageThread::where('uuid', $thread)->firstOrFail();

        $patientId = $this->authorizeThread($threadModel, $userId, $facilityId);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            // Same service the patient side calls, into the same thread — the
            // reply appears in the patient's own conversation view, encrypted
            // at rest by KmsEncryptionService like every other message.
            $this->messaging->sendMessage($threadModel->uuid, $userId, $validated['body']);
        } catch (\Throwable $e) {
            return redirect()
                ->route('portals.staff.messages.show', $threadModel->uuid)
                ->with('error', __('messaging.reply_failed'));
        }

        $this->ctx->auditPatientAccess(
            actionType:   'staff_message_reply_sent',
            resourceType: 'MessageThread',
            resourceId:   $threadModel->uuid,
            patientId:    $patientId,
        );

        return redirect()
            ->route('portals.staff.messages.show', $threadModel->uuid)
            ->with('success', __('messaging.reply_sent'));
    }
}
