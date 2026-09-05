<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Modules\Governance\Services\AccessLogService;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mobile Provider API — the clinician's half of patient/colleague messaging.
 *
 * THE SAME THREADS THE PATIENT SEES. This controller is the mirror of
 * MobileMessagingController, not a parallel system: identical tables
 * (`message_threads` / `message_thread_participants` / `messages`), identical
 * writer (MessagingService::sendMessage, which KMS-encrypts the body), identical
 * five-field message shape. A clinician's reply lands in the very thread row the
 * patient opened, with the clinician's own `users.id` as `sender_id` — which is
 * exactly the id MobileMessagingController::start() wrote into
 * `message_thread_participants` as the recipient. That is what makes `is_mine`
 * come out right on both phones without either side knowing about the other.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Who may read what
 * ──────────────────────────────────────────────────────────────────────────
 * Two gates, both server-side, both required:
 *
 *  1. PARTICIPATION. A thread is reachable only when this clinician holds a
 *     `message_thread_participants` row on it. There is no facility-wide or
 *     role-wide inbox — a colleague's conversation is not this clinician's to
 *     read, however senior they are.
 *
 *  2. FACILITY. `provider_facility_id` comes from `$request->attributes`, set by
 *     AuthenticateMobileProvider from the bearer token. It is NEVER read from a
 *     header, query string, body or session, and there is no parameter anywhere
 *     in this controller that names a facility — a spoofed `facility_id` is not
 *     "rejected", it is never looked at.
 *
 *     A patient thread's facility is derived from its appointment context
 *     (`context_type='appointment'` → `appointments.facility_id`), which is what
 *     MobileMessagingController::start() stamps on every thread it creates. A
 *     patient thread whose facility cannot be derived, or which derives to a
 *     DIFFERENT facility, is dropped — fail closed. A clinician who moves
 *     facilities does not carry the old facility's conversations with them.
 *
 *     Threads with no patient (colleague/internal) carry no patient record and
 *     are gated on participation alone; there is nothing to derive a facility
 *     from and inventing one would be a fabrication.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Every read is a disclosure
 * ──────────────────────────────────────────────────────────────────────────
 * A patient message thread carries clinical content, and this endpoint hands it
 * to someone who is NOT the patient. So every read of a patient thread is
 * written to `access_logs` through AccessLogService, patient-scoped, the same
 * way MedicalRecordExportController and PrescriptionVerifyController do it —
 * including the inbox listing, whose `last_message_preview` is itself a piece
 * of the conversation. The listing logs one entry per PATIENT disclosed (not
 * per thread), so a refresh cannot flood the patient's own access-log view.
 *
 * Auditing is never fatal: a failed audit is logged as a warning and the
 * clinician still gets the message, exactly as PrescriptionVerifyController
 * decided for the pharmacy counter.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Thread state is per-participant
 * ──────────────────────────────────────────────────────────────────────────
 * `archive` / `unread` / `reopen` write ONLY this clinician's participant row
 * (`status`, `last_read_at`). Archiving is a clinician tidying their own inbox;
 * it must not close the conversation for the patient, who never asked for it to
 * end. `message_threads.status` is left alone.
 *
 * And an archived thread comes BACK to the live inbox as soon as something
 * unread lands in it — see inFolder(). A patient's message must never be able
 * to disappear into a folder nobody opens.
 */
class MobileDoctorMessagingController extends Controller
{
    /** Hard ceiling on a page, whatever the client asks for. */
    private const MAX_PER_PAGE = 100;

    /** How much of the last message the inbox row shows. */
    private const PREVIEW_LENGTH = 140;

    public function __construct(private readonly MessagingService $messaging)
    {
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET /api/mobile/doctor/messages/threads
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The clinician's inbox: threads they participate in, at their own facility.
     *
     * Order is unread first, then most recent activity — so the top of the list
     * is literally "who is waiting on me".
     */
    public function index(Request $request): JsonResponse
    {
        // A query string has no booleans. Laravel's `boolean` rule accepts
        // 1/0/"1"/"0" but NOT "true"/"false" — which is the most idiomatic
        // spelling in a URL and exactly what the app sends
        // (`app/clinician/messages.tsx`: `waiting: { unread: true }`). So the
        // clinician's DEFAULT Messages tab 422'd on every load with "The unread
        // field must be true or false", while the consumer nine lines below
        // already used FILTER_VALIDATE_BOOLEAN, which would have accepted it.
        // The rule was stricter than its own reader.
        //
        // Normalised here rather than fixed in the client because a shipped
        // native build cannot be corrected on demand — this repairs the app
        // already on someone's phone, and any other spelling a caller picks.
        // FILTER_NULL_ON_FAILURE matters: a RECOGNISED spelling is normalised, and
        // anything else is left untouched so the rule below still rejects it.
        // Without it, `?unread=banana` would quietly become false and silently
        // change what the clinician sees instead of saying the request was wrong.
        if ($request->has('unread')) {
            $normalised = filter_var(
                $request->query('unread'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($normalised !== null) {
                $request->merge(['unread' => $normalised]);
            }
        }

        $validated = $request->validate([
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            'unread'   => ['nullable', 'boolean'],
            'status'   => ['nullable', 'in:archived'],
            'kind'     => ['nullable', 'in:patient,internal'],
        ]);

        $rows = $this->inFolder(
            $this->visibleThreads($request),
            ($validated['status'] ?? null) === 'archived',
        );

        if (! empty($validated['kind'])) {
            $rows = $rows->filter(fn (array $row) => $row['kind'] === $validated['kind']);
        }

        if (filter_var($validated['unread'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $rows = $rows->filter(fn (array $row) => $row['unread_count'] > 0);
        }

        // Unread first, then newest activity. Both halves come from the server's
        // own state; nothing here is derived on the phone.
        $rows = $rows->sort(function (array $a, array $b): int {
            $byUnread = ($b['unread_count'] > 0 ? 1 : 0) <=> ($a['unread_count'] > 0 ? 1 : 0);

            return $byUnread !== 0
                ? $byUnread
                : (string) $b['last_message_at'] <=> (string) $a['last_message_at'];
        })->values();

        $perPage = (int) ($validated['per_page'] ?? 25);
        $page    = (int) ($validated['page'] ?? 1);
        $total   = $rows->count();
        $slice   = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        // The preview text on each row is part of the conversation. Log it once
        // per patient rather than once per thread.
        $this->auditPatients(
            $request,
            $slice->pluck('patient_id')->filter()->unique()->all(),
            'read',
            'MessageThreadList',
            null,
        );

        return response()->json([
            'data' => $slice->map(fn (array $row) => $this->presentThread($row))->all(),
            'meta' => [
                'pagination' => [
                    'current_page' => $page,
                    'per_page'     => $perPage,
                    'total'        => $total,
                    'last_page'    => max(1, (int) ceil($total / max($perPage, 1))),
                    'from'         => $slice->isEmpty() ? null : (($page - 1) * $perPage) + 1,
                    'to'           => $slice->isEmpty() ? null : (($page - 1) * $perPage) + $slice->count(),
                ],
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET /api/mobile/doctor/messages/threads/{id}
    // ══════════════════════════════════════════════════════════════════════

    /**
     * One conversation, with its whole message list.
     *
     * `unread_count` is computed from the read cursor as it stood BEFORE this
     * call, and the cursor is advanced afterwards — so the body comes back as
     * it was, and the badge clears on the next listing rather than describing a
     * state the clinician has not seen yet.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $row = $this->findVisibleThread($request, $id);

        if (! $row) {
            return $this->notFound();
        }

        $providerId = $this->providerId($request);

        $messages = $row['thread']->messages()->orderBy('id')->get()->map(fn (Message $m) => [
            'id'         => $m->id,
            'is_mine'    => $m->sender_id === $providerId,
            'body'       => $this->messaging->decryptBody($m->body),
            'status'     => $m->status,
            'created_at' => $m->created_at?->toISOString(),
        ])->values()->all();

        $payload = $this->presentThread($row) + ['messages' => $messages];

        // Reading the conversation is what marks it read — never the listing.
        MessageThreadParticipant::whereKey($row['participant']->id)
            ->update(['last_read_at' => now()]);

        $this->auditPatients($request, [$row['patient_id']], 'read', 'MessageThread', $row['thread']->uuid);

        return response()->json(['data' => $payload]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // POST /api/mobile/doctor/messages/threads/{id}/messages
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Reply, into the SAME thread the patient reads.
     *
     * Rate-limited at the route (throttle:30,1), matching the patient side —
     * one budget per seat, not one shared budget for the conversation.
     */
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $row = $this->findVisibleThread($request, $id);

        if (! $row) {
            return $this->notFound();
        }

        $providerId = $this->providerId($request);

        try {
            // The patient's own writer. The body is KMS-encrypted here and
            // nowhere else, so both halves of the conversation are stored the
            // same way and decrypt through the same service.
            $message = $this->messaging->sendMessage($row['thread']->uuid, $providerId, $validated['body']);
        } catch (\Throwable $e) {
            // MessagingService raises MESSAGE_THREAD_CLOSED for a closed thread.
            return response()->json([
                'error_code' => 'MESSAGE_NOT_SENT',
                'message'    => __('doctor_messaging.send_failed'),
            ], 409);
        }

        // Message declares no $touches, so without this the parent thread's
        // updated_at never moves and the inbox stops sorting like an inbox
        // (the same trap MobileMessagingController documents).
        $row['thread']->touch();

        // Answering a conversation means having read it.
        MessageThreadParticipant::whereKey($row['participant']->id)
            ->update(['last_read_at' => now()]);

        $this->auditPatients($request, [$row['patient_id']], 'write', 'MessageThread', $row['thread']->uuid);

        return response()->json(['data' => [
            'id'         => $message->id,
            'is_mine'    => true,
            'body'       => $validated['body'],
            'status'     => $message->status,
            'created_at' => $message->created_at?->toISOString(),
        ]], 201);
    }

    // ══════════════════════════════════════════════════════════════════════
    // PATCH /api/mobile/doctor/messages/threads/{id}/state
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Move a conversation on THIS clinician's desk.
     *
     * Writes the participant row only. `reopen` un-archives it for this
     * clinician; it does not reopen a thread the facility closed, and `archive`
     * does not close the conversation for the patient.
     */
    public function state(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'in:unread,archive,reopen'],
        ]);

        $row = $this->findVisibleThread($request, $id);

        if (! $row) {
            return $this->notFound();
        }

        $changes = match ($validated['state']) {
            'unread'  => ['last_read_at' => null],
            'archive' => ['status' => 'archived'],
            'reopen'  => ['status' => 'active'],
        };

        MessageThreadParticipant::whereKey($row['participant']->id)->update($changes);

        // Re-resolve so the answer describes the stored state, not the intent.
        $fresh = $this->findVisibleThread($request, $id);

        return response()->json([
            'data' => $this->presentThread($fresh ?? $row),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET /api/mobile/doctor/messages/unread-count
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The badge the tab bar and the inbox header both read. Counts only the
     * live inbox — an archived conversation is off the desk and must not keep
     * a badge lit.
     *
     * Discloses no content, so it is not an access-log event.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $rows = $this->inFolder($this->visibleThreads($request), false)
            ->filter(fn (array $row) => $row['unread_count'] > 0);

        return response()->json(['data' => [
            'unread_threads'  => $rows->count(),
            'unread_messages' => (int) $rows->sum('unread_count'),
        ]]);
    }

    /**
     * Split the clinician's threads into the two folders the app shows.
     *
     * The archive is "I am done with this conversation" — and a NEW message
     * from the patient means it is not done. So a thread the clinician archived
     * comes back to the live inbox the moment something unread lands in it,
     * and the archive holds only what is genuinely settled.
     *
     * Without this, a patient could write to a clinician who archived the
     * thread months ago and the message would sit, unread and invisible, in a
     * folder nobody opens. Nothing in the patient's own send path
     * (MobileMessagingController) touches a clinician's participant row, so the
     * archive can only be corrected on the way out — here.
     *
     * The two folders stay mutually exclusive: a resurfaced thread is in the
     * live inbox and NOT in the archive, so it is never listed twice. It still
     * reports `status: 'archived'`, because that is what its participant row
     * says and the app's "Reopen" action is the honest offer for it.
     *
     * @param  Collection<int,array<string,mixed>>  $rows
     * @return Collection<int,array<string,mixed>>
     */
    private function inFolder(Collection $rows, bool $archived): Collection
    {
        return $rows->filter(function (array $row) use ($archived) {
            $settledInArchive = $row['archived'] && $row['unread_count'] === 0;

            return $archived ? $settledInArchive : ! $settledInArchive;
        })->values();
    }

    // ══════════════════════════════════════════════════════════════════════
    // Resolution — the only place a thread becomes visible
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Every thread this clinician may see — both folders — already
     * facility-filtered and with its counterpart, read cursor and unread count
     * resolved. `inFolder()` decides which of them the inbox or the archive
     * shows; opening and replying use the whole set, because a thread listed in
     * the archive must still be openable.
     *
     * @return Collection<int,array<string,mixed>>
     */
    private function visibleThreads(Request $request): Collection
    {
        $providerId = $this->providerId($request);
        $facilityId = $this->facilityId($request);

        // Gate 1 — participation. No participant row, no thread. Full stop.
        // A participant row in any other state (removed, blocked, …) is not
        // participation: only these two are.
        $participants = MessageThreadParticipant::where('user_id', $providerId)
            ->whereIn('status', ['active', 'archived'])
            ->get()
            ->keyBy('thread_id');

        if ($participants->isEmpty()) {
            return collect();
        }

        $threads = MessageThread::whereIn('id', $participants->keys())->get();

        // Legal hold: only compliance roles may read a held thread, and the
        // clinician mobile surface is never one. Dropped before anything else
        // is loaded, matching MessagePermissionService::canViewThread's rule.
        $threads = $threads->reject(fn (MessageThread $t) => (bool) $t->legal_hold);

        if ($threads->isEmpty()) {
            return collect();
        }

        // The appointment context is what carries the facility and the patient.
        // Non-uuid context ids are filtered out before the query: appointments.id
        // is a uuid column and Postgres raises 22P02 (a 500) on a malformed one.
        $appointmentIds = $threads
            ->filter(fn (MessageThread $t) => $t->context_type === 'appointment')
            ->pluck('context_id')
            ->filter(fn ($id) => is_string($id) && Str::isUuid($id))
            ->unique()
            ->values();

        $appointments = $appointmentIds->isEmpty()
            ? collect()
            : Appointment::with(['patient', 'facility:id,name', 'provider:id,name'])
                ->whereIn('id', $appointmentIds)
                ->get()
                ->keyBy('id');

        // Last message per thread, in two bounded queries (the patient
        // controller's shape) rather than one query per thread.
        $lastMessageIds = Message::whereIn('thread_id', $threads->modelKeys())
            ->groupBy('thread_id')
            ->selectRaw('thread_id, MAX(id) AS id')
            ->pluck('id');

        $lastMessages = $lastMessageIds->isEmpty()
            ? collect()
            : Message::whereIn('id', $lastMessageIds)->get()->keyBy('thread_id');

        // Inbound messages only (bodies are not loaded — this is a count, not a
        // read), bucketed per thread so each thread's own cursor decides.
        $inbound = Message::whereIn('thread_id', $threads->modelKeys())
            ->where('sender_id', '!=', $providerId)
            ->get(['id', 'thread_id', 'created_at'])
            ->groupBy('thread_id');

        return $threads->map(function (MessageThread $thread) use (
            $participants, $appointments, $lastMessages, $inbound, $providerId, $facilityId
        ) {
            $participant = $participants->get($thread->id);
            $appointment = $thread->context_type === 'appointment'
                ? $appointments->get($thread->context_id)
                : null;

            // A thread CLAIMS a patient when it is typed as patient/provider or
            // hangs off an appointment. The claim is what triggers the facility
            // gate — deciding "internal" because the appointment failed to load
            // would let an unresolvable patient thread slip past it.
            $claimsPatient = $thread->thread_type === 'patient_provider'
                || $thread->context_type === 'appointment';

            // Gate 2 — facility. Derived, never accepted from the caller.
            if ($claimsPatient) {
                // Fail closed: a patient thread with no derivable facility, or
                // one belonging to another facility, is not this session's.
                if (! $appointment || (string) $appointment->facility_id !== (string) $facilityId) {
                    return null;
                }
            }

            // `last_read_at` is a plain timestamp column on a model with no
            // casts — it arrives as a string, and calling ->lt() on a string is
            // fatal. Parse it (the same trap MobileMessagingController hit).
            $lastReadAt = $participant?->last_read_at
                ? Carbon::parse($participant->last_read_at)
                : null;

            // `>=`, not `>`. Both `message_thread_participants.last_read_at`
            // and `messages.created_at` are written through Eloquent's default
            // 'Y-m-d H:i:s' format, so both are truncated to WHOLE SECONDS. A
            // strict `lastReadAt < created_at` therefore swallows any message
            // that lands in the same second as a read: it is stamped with the
            // identical value, compares as "already read", and is never counted
            // again — a patient's message disappearing from a clinician's
            // inbox the instant they closed the thread.
            //
            // Treating an equal timestamp as unread costs at most one badge
            // that clears on the next read, one second later. Erring the other
            // way costs a message nobody ever sees.
            $unread = ($inbound->get($thread->id) ?? collect())
                ->filter(fn (Message $m) => ! $lastReadAt || ! $lastReadAt->gt($m->created_at))
                ->count();

            $lastMessage = $lastMessages->get($thread->id);
            $patient     = $appointment?->patient;

            return [
                'thread'       => $thread,
                'participant'  => $participant,
                'appointment'  => $appointment,
                'kind'         => $claimsPatient ? 'patient' : 'internal',
                'patient_id'   => $patient?->id,
                'patient_name' => $patient
                    ? trim($patient->first_name . ' ' . $patient->last_name)
                    : null,
                'patient_health_id' => $patient?->health_id,
                'facility_id'       => $appointment?->facility_id,
                'facility_name'     => $appointment?->facility?->name,
                'provider_id'       => $appointment?->provider_id,
                'provider_name'     => $appointment?->provider?->name,
                'unread_count'      => $unread,
                'last_message_at'   => $lastMessage?->created_at?->toISOString(),
                'last_message_preview' => $lastMessage
                    ? Str::limit($this->messaging->decryptBody($lastMessage->body), self::PREVIEW_LENGTH)
                    : null,
                'archived' => ($participant?->status ?? 'active') === 'archived',
            ];
        })->filter()->values();
    }

    /**
     * One thread, resolved through exactly the same two gates as the listing —
     * there is no second, looser path to a conversation.
     *
     * Both folders are in scope. A thread the clinician archived is still
     * theirs: they must be able to open it, reply in it, and reopen it. The
     * archive is a view, not a revocation of access.
     *
     * @return array<string,mixed>|null
     */
    private function findVisibleThread(Request $request, string $id): ?array
    {
        // message_threads.id is a bigint. A non-numeric segment reaching
        // Postgres as a bigint cast is a 500 where a 404 belongs.
        if (! ctype_digit($id)) {
            return null;
        }

        return $this->visibleThreads($request)
            ->first(fn (array $r) => (string) $r['thread']->id === $id);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Presentation
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The contract's `DoctorThread`. Ids go out as strings because the wire
     * type is `string` even though the column is a bigint.
     *
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    private function presentThread(array $row): array
    {
        /** @var MessageThread $thread */
        $thread = $row['thread'];

        // 'archived' is this clinician's own folder; 'closed' is the thread's
        // real state. The clinician's view of their desk wins on the label,
        // but a closed thread still reads closed so the composer stays shut.
        $status = $thread->status === 'closed'
            ? 'closed'
            : ($row['archived'] ? 'archived' : ($thread->status ?: 'open'));

        return [
            'id'                => (string) $thread->id,
            'kind'              => $row['kind'],
            'subject'           => $thread->title,
            'status'            => $status,
            'patient_id'        => $row['patient_id'],
            'patient_name'      => $row['patient_name'],
            'patient_health_id' => $row['patient_health_id'],
            'counterpart_name'  => $row['patient_name'] ?? $thread->title,
            'provider_id'       => $row['provider_id'],
            'provider_name'     => $row['provider_name'],
            'facility_id'       => $row['facility_id'],
            'facility_name'     => $row['facility_name'],
            'last_message_at'   => $row['last_message_at'],
            'last_message_preview' => $row['last_message_preview'],
            'unread_count'      => (int) $row['unread_count'],
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'error_code' => 'NOT_FOUND',
            'message'    => __('doctor_messaging.thread_not_found'),
        ], 404);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Identity + audit
    // ══════════════════════════════════════════════════════════════════════

    /** The clinician, stamped by AuthenticateMobileProvider from the token. */
    private function providerId(Request $request): string
    {
        $id = $request->attributes->get('provider_user_id');

        if (! $id) {
            abort(401, 'Unauthenticated.');
        }

        return (string) $id;
    }

    /**
     * The facility this session speaks for. Request ATTRIBUTES only — set by
     * the auth middleware from the bearer token. Never a header, query string,
     * body field or session value.
     */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return $id ? (string) $id : null;
    }

    /**
     * Record the disclosure, patient-scoped.
     *
     * Never fatal. A clinician standing in front of a patient does not lose the
     * conversation because the audit table was unavailable — the failure is
     * logged loudly instead (the call PrescriptionVerifyController makes for
     * the pharmacy counter).
     *
     * @param  array<int,string|null>  $patientIds
     */
    private function auditPatients(
        Request $request,
        array $patientIds,
        string $accessType,
        string $resourceType,
        ?string $resourceId,
    ): void {
        $providerId = $request->attributes->get('provider_user_id');

        if (! $providerId) {
            return;
        }

        foreach (array_unique(array_filter($patientIds)) as $patientId) {
            try {
                AccessLogService::log(
                    patientId: $patientId,
                    actorId: (string) $providerId,
                    actorType: 'provider',
                    organizationId: null,
                    facilityId: $this->facilityId($request),
                    purpose: 'clinical_messaging',
                    dataCategory: 'communication',
                    resourceType: $resourceType,
                    resourceId: $resourceId,
                    accessType: $accessType,
                );
            } catch (\Throwable $e) {
                Log::warning('Doctor messaging access log failed.', [
                    'patient_id'  => $patientId,
                    'provider_id' => $providerId,
                    'resource'    => $resourceType,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }
}
