<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Services\MessagePermissionService;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Mobile Patient API — Messaging
 *
 * Wraps the Messaging module (App\Modules\Messaging) for the patient mobile
 * app. No patient-facing entry point existed before this — the module was
 * only reachable via the B2B/staff `CommunicationController` (integration
 * client auth). This controller is the real, additive patient surface.
 *
 * Identity: the patient's own `patient_id` (from AuthenticateMobilePatient
 * middleware, never request input) is used directly as the actor identity
 * in message_thread_participants.user_id / messages.sender_id — that column
 * is a plain string with no FK constraint, so no dependency on a linked
 * `users` row existing for every patient.
 *
 * Note: message_threads.patient_id/facility_id/organization_id are typed
 * unsignedBigInteger in the schema, incompatible with this platform's UUID
 * patient/facility ids — deliberately left null on create (see start()) to
 * avoid a Postgres type error; context_type/context_id (string columns)
 * carry the appointment linkage instead.
 */
class MobileMessagingController extends Controller
{
    public function __construct(
        private readonly MessagingService $messagingService,
        private readonly MessagePermissionService $permissionService,
    ) {
    }

    /** GET /api/mobile/messages/threads — the patient's own conversations. */
    public function index(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        // The patient's own participant rows are the whole authorisation story
        // here: a thread is only listed when this patient is an active
        // participant in it. Keyed by thread_id so the per-thread read cursor
        // is a lookup, not a query.
        $participants = MessageThreadParticipant::where('user_id', $patientId)
            ->where('status', 'active')
            ->get()
            ->keyBy('thread_id');

        $threads = MessageThread::whereIn('id', $participants->keys())
            ->orderByDesc('updated_at')
            ->get();

        // Latest message per thread in two bounded queries instead of one
        // query per thread. MAX(id) is the ordering the detail view uses.
        $lastMessageIds = Message::whereIn('thread_id', $threads->modelKeys())
            ->groupBy('thread_id')
            ->selectRaw('thread_id, MAX(id) AS id')
            ->pluck('id');

        $lastMessages = Message::whereIn('id', $lastMessageIds)->get()->keyBy('thread_id');

        $data = $threads->map(function (MessageThread $thread) use ($patientId, $participants, $lastMessages) {
            $lastMessage = $lastMessages->get($thread->id);
            $participant = $participants->get($thread->id);

            // `last_read_at` is a plain timestamp column and the participant
            // model declares no casts, so it arrives as a string — comparing
            // it with `->lt()` directly is a fatal call-on-string. Parse it.
            $lastReadAt = $participant?->last_read_at
                ? Carbon::parse($participant->last_read_at)
                : null;

            $unread = $lastMessage
                && $lastMessage->sender_id !== $patientId
                && (! $lastReadAt || $lastReadAt->lt($lastMessage->created_at));

            return [
                'id'           => $thread->id,
                'title'        => $thread->title,
                'status'       => $thread->status,
                'priority'     => $thread->priority,
                'thread_type'  => $thread->thread_type,
                'updated_at'   => $thread->updated_at?->toISOString(),
                'unread'       => (bool) $unread,
                'last_message' => $lastMessage ? [
                    'body'       => $this->messagingService->decryptBody($lastMessage->body),
                    'is_mine'    => $lastMessage->sender_id === $patientId,
                    'created_at' => $lastMessage->created_at?->toISOString(),
                ] : null,
            ];
        });

        return response()->json(['data' => $data->values()]);
    }

    /** GET /api/mobile/messages/threads/{id} — thread detail + full message list. */
    public function show(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $thread = $this->findThread($id);

        if (! $this->permissionService->canViewThread($patientId, $thread->uuid)) {
            return response()->json([
                'error_code' => 'MESSAGE_ACCESS_DENIED',
                'message'    => 'You do not have access to this conversation.',
            ], 403);
        }

        $messages = $thread->messages()->orderBy('id')->get()->map(fn ($m) => [
            'id'         => $m->id,
            'is_mine'    => $m->sender_id === $patientId,
            'body'       => $this->messagingService->decryptBody($m->body),
            'status'     => $m->status,
            'created_at' => $m->created_at?->toISOString(),
        ]);

        MessageThreadParticipant::where('thread_id', $thread->id)
            ->where('user_id', $patientId)
            ->update(['last_read_at' => now()]);

        return response()->json(['data' => [
            'id'       => $thread->id,
            'title'    => $thread->title,
            'status'   => $thread->status,
            'messages' => $messages,
        ]]);
    }

    /** POST /api/mobile/messages/threads/{id}/messages — reply in an existing thread. */
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $validated = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        $thread = $this->findThread($id);
        if (! $this->permissionService->canViewThread($patientId, $thread->uuid)) {
            return response()->json([
                'error_code' => 'MESSAGE_ACCESS_DENIED',
                'message'    => 'You do not have access to this conversation.',
            ], 403);
        }

        try {
            $message = $this->messagingService->sendMessage($thread->uuid, $patientId, $validated['body']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        // `Message` declares no `$touches`, so without this the parent thread's
        // `updated_at` never moves and index()'s `orderByDesc('updated_at')`
        // degrades into thread-creation order — the inbox stops behaving like
        // an inbox after the very first reply.
        $thread->touch();

        return response()->json(['data' => [
            'id'         => $message->id,
            'is_mine'    => true,
            'body'       => $validated['body'],
            'status'     => $message->status,
            'created_at' => $message->created_at?->toISOString(),
        ]], 201);
    }

    /**
     * POST /api/mobile/messages/threads — start a new conversation with the
     * provider from one of the patient's own appointments (proof of an
     * active care relationship, enforced here rather than left implicit).
     */
    public function start(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $validated = $request->validate([
            'appointment_id' => ['required', 'uuid'],
            'body'           => ['required', 'string', 'max:4000'],
        ]);

        $appointment = Appointment::where('id', $validated['appointment_id'])
            ->where('patient_id', $patientId)
            ->with('provider:id,name')
            ->first();

        if (! $appointment) {
            return response()->json([
                'error_code' => 'NOT_FOUND',
                'message'    => 'Appointment not found.',
            ], 404);
        }

        if (! $appointment->provider_id) {
            return response()->json([
                'error_code' => 'MESSAGE_RECIPIENT_NOT_ALLOWED',
                'message'    => 'This appointment has no assigned provider to message yet.',
            ], 422);
        }

        try {
            $thread = $this->messagingService->createThread($patientId, 'patient', [
                'thread_type'    => 'patient_provider',
                'title'          => $appointment->provider?->name ?? 'Care team',
                'context_type'   => 'appointment',
                'context_id'     => (string) $appointment->id,
                'doctor_id'      => $appointment->provider_id,
                'recipient_id'   => $appointment->provider_id,
                'recipient_role' => 'provider',
            ]);
            $message = $this->messagingService->sendMessage($thread->uuid, $patientId, $validated['body']);
        } catch (\Exception $e) {
            return response()->json([
                'error_code' => 'MESSAGE_RECIPIENT_NOT_ALLOWED',
                'message'    => $e->getMessage(),
            ], 422);
        }

        return response()->json(['data' => [
            'id'       => $thread->id,
            'title'    => $thread->title,
            'status'   => $thread->status,
            'messages' => [[
                'id'         => $message->id,
                'is_mine'    => true,
                'body'       => $validated['body'],
                'status'     => $message->status,
                'created_at' => $message->created_at?->toISOString(),
            ]],
        ]], 201);
    }

    /**
     * `message_threads.id` is a bigint. Handing Postgres a non-numeric route
     * segment raises `invalid input syntax for type bigint` — a 500 where a
     * 404 belongs — so the shape is checked before the lookup.
     */
    private function findThread(string $id): MessageThread
    {
        if (! ctype_digit($id)) {
            abort(404);
        }

        return MessageThread::findOrFail($id);
    }

    private function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');
        if (! $patientId) {
            abort(401, 'Unauthenticated.');
        }

        return $patientId;
    }
}
