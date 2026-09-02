<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Services\MessagePermissionService;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $threadIds = MessageThreadParticipant::where('user_id', $patientId)
            ->where('status', 'active')
            ->pluck('thread_id');

        $threads = MessageThread::whereIn('id', $threadIds)
            ->orderByDesc('updated_at')
            ->get();

        $data = $threads->map(function (MessageThread $thread) use ($patientId) {
            $lastMessage = $thread->messages()->latest('id')->first();
            $participant = MessageThreadParticipant::where('thread_id', $thread->id)
                ->where('user_id', $patientId)
                ->first();

            $unread = $lastMessage
                && $lastMessage->sender_id !== $patientId
                && (! $participant?->last_read_at || $participant->last_read_at->lt($lastMessage->created_at));

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
        $thread = MessageThread::findOrFail($id);

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

        $thread = MessageThread::findOrFail($id);
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

    private function resolvePatientId(Request $request): string
    {
        $patientId = $request->attributes->get('patient_id');
        if (! $patientId) {
            abort(401, 'Unauthenticated.');
        }

        return $patientId;
    }
}
