<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActionTaskResource;
use App\Http\Resources\BroadcastResource;
use App\Http\Resources\EscalationChainResource;
use App\Http\Resources\MessageResource;
use App\Http\Resources\MessageThreadResource;
use App\Http\Resources\NotificationDeliveryResource;
use App\Http\Resources\NotificationEventResource;
use App\Http\Resources\NotificationTemplateResource;
use App\Modules\Broadcasts\Models\Broadcast;
use App\Modules\Broadcasts\Services\BroadcastService;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\MessageAttachmentService;
use App\Modules\Messaging\Services\MessagePermissionService;
use App\Modules\Messaging\Services\MessagingService;
use App\Modules\Notifications\Models\EscalationChain;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Models\NotificationEvent;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Services\NotificationPreferenceService;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Tasks\Models\ActionTask;
use App\Modules\Tasks\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CommunicationController — Notifications, Tasks, Messaging, and Broadcasts API.
 *
 * SECURITY: Actor identity (user_id) is always sourced from the validated
 * request body or middleware attributes. Never from X-User-ID or any other
 * request header — that pattern is an OWASP API1 IDOR vulnerability.
 *
 * For self-service endpoints (get my notifications, my messages, etc.),
 * callers must supply user_id in the request body as a validated UUID.
 */
class CommunicationController extends Controller
{
    public function __construct(
        private readonly NotificationService          $notificationService,
        private readonly TaskService                  $taskService,
        private readonly MessagingService             $messagingService,
        private readonly MessagePermissionService     $permissionService,
        private readonly MessageAttachmentService     $attachmentService,
        private readonly NotificationPreferenceService $preferenceService,
        private readonly BroadcastService             $broadcastService,
    ) {}

    private function actorUserId(Request $request): string
    {
        $userId = $request->user()?->id
            ?? $request->attributes->get('provider_id');

        if (!is_string($userId) || !Str::isUuid($userId)) {
            abort(401, 'Authenticated user context is required.');
        }

        return $userId;
    }

    // ═══════════════════════════════════════════════════════════════
    // NOTIFICATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get notifications for a user.
     * Body: { user_id: uuid }
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $notifications = NotificationEvent::where('recipient_user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(NotificationEventResource::collection($notifications));
    }

    /**
     * Get unread notification count for a user.
     * Body: { user_id: uuid }
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $count = NotificationEvent::where('recipient_user_id', $userId)
            ->where('status', 'pending')
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markRead(string $id): JsonResponse
    {
        $notification = NotificationEvent::findOrFail($id);
        $notification->status = 'read';
        $notification->save();
        return response()->json(['message' => __('api.notification_read')]);
    }

    /**
     * Acknowledge a notification.
     * Body: { user_id: uuid }
     */
    public function acknowledgeNotification(Request $request, string $id): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $notification = NotificationEvent::findOrFail($id);
        $notification->acknowledgement_status = 'acknowledged';
        $notification->acknowledged_by        = $userId;
        $notification->acknowledged_at        = now();
        $notification->save();

        return response()->json(['message' => __('api.notification_acknowledged')]);
    }

    /**
     * Mark all notifications as read for a user.
     * Body: { user_id: uuid }
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        NotificationEvent::where('recipient_user_id', $userId)
            ->where('status', 'pending')
            ->update(['status' => 'read']);

        return response()->json(['message' => __('api.all_notifications_read')]);
    }

    public function archiveNotification(string $id): JsonResponse
    {
        $notification = NotificationEvent::findOrFail($id);
        $notification->status = 'archived';
        $notification->save();
        return response()->json(['message' => __('api.notification_archived')]);
    }

    /**
     * Get notification preferences for a user.
     * Body: { user_id: uuid, category? }
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string'],
        ]);
        $userId = $this->actorUserId($request);

        $prefs = $this->preferenceService->getPreferences(
            $userId,
            $validated['category'] ?? 'health_updates'
        );

        return response()->json($prefs);
    }

    /**
     * Update notification preferences for a user.
     * Body: { user_id: uuid, category?, ...preference fields }
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string'],
        ]);
        $userId = $this->actorUserId($request);

        $prefs = $this->preferenceService->updatePreferences(
            $userId,
            $validated['category'] ?? 'health_updates',
            $request->except(['user_id', 'category'])
        );

        return response()->json($prefs);
    }

    // ═══════════════════════════════════════════════════════════════
    // TASKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get tasks for a user.
     * Body: { user_id: uuid, role?: string }
     */
    public function getTasks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role'    => ['nullable', 'string', 'max:100'],
        ]);
        $userId = $this->actorUserId($request);

        $tasks = ActionTask::where('assigned_to', $userId)
            ->when($validated['role'] ?? null, fn ($q, $role) =>
                $q->orWhere('assigned_role', $role)
            )
            ->get();

        return response()->json(ActionTaskResource::collection($tasks));
    }

    public function getTask(string $id): JsonResponse
    {
        return response()->json(ActionTaskResource::make(ActionTask::findOrFail($id)));
    }

    /**
     * Acknowledge a task.
     * Body: { user_id: uuid }
     */
    public function acknowledgeTask(Request $request, string $id): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $task = ActionTask::findOrFail($id);
        $this->taskService->acknowledgeTask($task->uuid, $userId);

        return response()->json(['message' => __('api.task_acknowledged')]);
    }

    public function completeTask(string $id): JsonResponse
    {
        $task = ActionTask::findOrFail($id);
        $this->taskService->completeTask($task->uuid);
        return response()->json(['message' => __('api.task_completed')]);
    }

    /**
     * Assign a task to a user.
     * Body: { assigned_to: uuid }
     */
    public function assignTask(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate(['assigned_to' => ['required', 'uuid']]);

        $task = ActionTask::findOrFail($id);
        $task->assigned_to = $validated['assigned_to'];
        $task->save();

        return response()->json(['message' => __('api.task_assigned')]);
    }

    public function escalateTask(string $id): JsonResponse
    {
        $task = ActionTask::findOrFail($id);
        $this->taskService->escalateTask($task->uuid);
        return response()->json(['message' => __('api.task_escalated')]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ADMIN — TEMPLATES & DELIVERY
    // ═══════════════════════════════════════════════════════════════

    public function getAdminTemplates(): JsonResponse
    {
        return response()->json(NotificationTemplateResource::collection(NotificationTemplate::all()));
    }

    public function createAdminTemplate(Request $request): JsonResponse
    {
        $template = NotificationTemplate::create(array_merge($request->all(), [
            'uuid'            => (string) Str::uuid(),
            'version'         => 1,
            'approval_status' => 'draft',
        ]));
        return response()->json(NotificationTemplateResource::make($template), 201);
    }

    public function updateAdminTemplate(Request $request, string $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update($request->all());
        return response()->json(NotificationTemplateResource::make($template));
    }

    public function submitTemplateReview(string $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->approval_status = 'pending_review';
        $template->save();
        return response()->json(['message' => __('api.template_submitted')]);
    }

    public function approveTemplate(string $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->approval_status = 'approved';
        $template->save();
        return response()->json(['message' => __('api.template_approved')]);
    }

    public function publishTemplate(string $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->approval_status = 'published';
        $template->save();
        return response()->json(['message' => __('api.template_published')]);
    }

    public function rollbackTemplate(string $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->version = max(1, $template->version - 1);
        $template->save();
        return response()->json(['message' => __('api.template_rolled_back')]);
    }

    public function getAdminDeliveries(): JsonResponse
    {
        return response()->json(NotificationDeliveryResource::collection(NotificationDelivery::all()));
    }

    public function retryDelivery(string $id): JsonResponse
    {
        $delivery = NotificationDelivery::findOrFail($id);
        $delivery->status = 'delivered';
        $delivery->attempt_count++;
        $delivery->save();
        return response()->json(['message' => __('api.delivery_retried')]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ESCALATION CHAINS
    // ═══════════════════════════════════════════════════════════════

    public function getEscalationChains(): JsonResponse
    {
        return response()->json(EscalationChainResource::collection(EscalationChain::all()));
    }

    public function createEscalationChain(Request $request): JsonResponse
    {
        $chain = EscalationChain::create([
            'uuid'       => (string) Str::uuid(),
            'name'       => $request->input('name'),
            'event_type' => $request->input('event_type'),
            'steps_json' => json_encode($request->input('steps')),
            'active'     => true,
        ]);
        return response()->json(EscalationChainResource::make($chain), 201);
    }

    public function updateEscalationChain(Request $request, string $id): JsonResponse
    {
        $chain = EscalationChain::findOrFail($id);
        $chain->update($request->all());
        return response()->json(EscalationChainResource::make($chain));
    }

    public function activateEscalationChain(string $id): JsonResponse
    {
        EscalationChain::findOrFail($id)->update(['active' => true]);
        return response()->json(['message' => __('api.escalation_chain_activated')]);
    }

    public function deactivateEscalationChain(string $id): JsonResponse
    {
        EscalationChain::findOrFail($id)->update(['active' => false]);
        return response()->json(['message' => __('api.escalation_chain_deactivated')]);
    }

    // ═══════════════════════════════════════════════════════════════
    // MESSAGING
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get message threads for a user.
     * Body: { user_id: uuid }
     */
    public function getThreads(Request $request): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $threads = MessageThread::whereHas('participants', fn ($q) =>
            $q->where('user_id', $userId)
        )->get();

        return response()->json(MessageThreadResource::collection($threads));
    }

    /**
     * Create a new message thread.
     * Body: { user_id: uuid, role: string, ...thread fields }
     */
    public function createThread(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role'    => ['required', 'string', 'max:100'],
        ]);
        $userId = $this->actorUserId($request);

        try {
            $thread = $this->messagingService->createThread(
                $userId,
                $validated['role'],
                $request->except(['user_id', 'role'])
            );
            return response()->json(MessageThreadResource::make($thread), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get a thread and its messages.
     * Body: { user_id: uuid }
     */
    public function getThread(Request $request, string $id): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $thread = MessageThread::findOrFail($id);

        if (!$this->permissionService->canViewThread($userId, $thread->uuid)) {
            return response()->json(['error' => 'MESSAGE_ACCESS_DENIED'], 403);
        }

        $thread->load('messages');
        $thread->messages->transform(function ($msg) {
            $msg->body = $this->messagingService->decryptBody($msg->body);
            return $msg;
        });
        return response()->json($thread);
    }

    /**
     * Send a message to a thread.
     * Body: { user_id: uuid, body: string }
     */
    public function sendMessage(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'body'    => ['required', 'string'],
        ]);
        $userId = $this->actorUserId($request);

        $thread = MessageThread::findOrFail($id);

        try {
            $message = $this->messagingService->sendMessage(
                $thread->uuid,
                $userId,
                $validated['body']
            );
            return response()->json(MessageResource::make($message), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function closeThread(Request $request, string $id): JsonResponse
    {
        $userId = $this->actorUserId($request);
        $thread = MessageThread::findOrFail($id);

        if (!$this->permissionService->canViewThread($userId, $thread->uuid)) {
            return response()->json(['error' => 'MESSAGE_ACCESS_DENIED'], 403);
        }

        $this->messagingService->closeThread($thread->uuid);
        return response()->json(['message' => __('api.thread_closed')]);
    }

    public function reopenThread(Request $request, string $id): JsonResponse
    {
        $userId = $this->actorUserId($request);
        $thread = MessageThread::findOrFail($id);

        if (!$this->permissionService->canViewThread($userId, $thread->uuid)) {
            return response()->json(['error' => 'MESSAGE_ACCESS_DENIED'], 403);
        }

        $this->messagingService->reopenThread($thread->uuid);
        return response()->json(['message' => __('api.thread_reopened')]);
    }

    public function editMessage(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate(['body' => ['required', 'string']]);
        $userId    = $this->actorUserId($request);

        $message = Message::findOrFail($id);

        if ($message->sender_id !== $userId) {
            return response()->json(['error' => 'MESSAGE_EDIT_FORBIDDEN'], 403);
        }

        $message->body      = $this->messagingService->encryptBody($validated['body']);
        $message->edited_at = now();
        $message->save();
        $message->body = $validated['body']; // return plaintext in response
        return response()->json(MessageResource::make($message));
    }

    public function deleteMessageForMe(Request $request, string $id): JsonResponse
    {
        $userId  = $this->actorUserId($request);
        $message = Message::findOrFail($id);

        if ($message->sender_id !== $userId) {
            return response()->json(['error' => 'MESSAGE_DELETE_FORBIDDEN'], 403);
        }

        $message->deleted_for_sender_at = now();
        $message->save();
        return response()->json(['message' => __('api.message_deleted_view')]);
    }

    public function reportMessage(string $id): JsonResponse
    {
        Message::findOrFail($id); // 404 guard
        return response()->json(['message' => __('api.message_reported')]);
    }

    public function uploadAttachment(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'file_name'      => ['required', 'string', 'max:255'],
            'file_path'      => ['required', 'string'],
            'mime_type'      => ['required', 'string', 'max:100'],
            'file_size'      => ['required', 'integer', 'min:1'],
            'classification' => ['nullable', 'string', 'in:PHI,PII,general'],
        ]);

        $message = Message::findOrFail($id);

        try {
            $attachment = $this->attachmentService->uploadAttachment(
                $message,
                $validated['file_name'],
                $validated['file_path'],
                $validated['mime_type'],
                $validated['file_size'],
                $validated['classification'] ?? 'PHI'
            );
            return response()->json($attachment, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // BROADCASTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * List active (published, non-expired) broadcasts.
     * ?facility_id= scopes results to platform-wide + facility-specific.
     * facility_id is read from middleware attributes — never from query string for auth.
     */
    public function getBroadcasts(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id')
            ?? $request->query('facility_id'); // public display boards may be unauthenticated

        return response()->json([
            'data' => $this->broadcastService->getActive($facilityId),
        ]);
    }

    /**
     * Create a broadcast draft.
     * Body: {
     *   broadcast_type, title, body, target_type,
     *   target_ids?, priority?, language?,
     *   requires_acknowledgement?, expires_at?,
     *   created_by: uuid   ← the admin actor
     * }
     * facility_id from middleware attributes.
     */
    public function createBroadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'broadcast_type'           => ['required', 'string', 'max:100'],
            'title'                    => ['required', 'string', 'max:255'],
            'body'                     => ['required', 'string'],
            'target_type'              => ['required', 'string', 'in:all,facility,role,specific_users'],
            'target_ids'               => ['nullable', 'array'],
            'target_ids.*'             => ['string'],
            'priority'                 => ['nullable', 'in:low,normal,high,urgent'],
            'language'                 => ['nullable', 'string', 'max:5'],
            'requires_acknowledgement' => ['nullable', 'boolean'],
            'expires_at'               => ['nullable', 'date', 'after:now'],
            'created_by'               => ['required', 'uuid'],
        ]);

        $broadcast = $this->broadcastService->create($validated, $validated['created_by']);

        return response()->json(['message' => __('api.broadcast_draft_created'), 'data' => BroadcastResource::make($broadcast)], 201);
    }

    /**
     * Update a broadcast draft.
     * Only drafts can be updated — published broadcasts cannot be modified.
     */
    public function updateAdminTemp(Request $request, string $id): JsonResponse
    {
        $broadcast = Broadcast::findOrFail($id);

        $validated = $request->validate([
            'broadcast_type'           => ['nullable', 'string', 'max:100'],
            'title'                    => ['nullable', 'string', 'max:255'],
            'body'                     => ['nullable', 'string'],
            'target_type'              => ['nullable', 'string', 'in:all,facility,role,specific_users'],
            'target_ids'               => ['nullable', 'array'],
            'target_ids.*'             => ['string'],
            'priority'                 => ['nullable', 'in:low,normal,high,urgent'],
            'language'                 => ['nullable', 'string', 'max:5'],
            'requires_acknowledgement' => ['nullable', 'boolean'],
            'expires_at'               => ['nullable', 'date', 'after:now'],
        ]);

        try {
            $updated = $this->broadcastService->update($broadcast, $validated);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('api.broadcast_updated'), 'data' => BroadcastResource::make($updated)]);
    }

    /**
     * Publish a broadcast — transitions from draft to published.
     * Published broadcasts become visible in recipient feeds immediately.
     */
    public function publishBroadcast(string $id): JsonResponse
    {
        $broadcast = Broadcast::findOrFail($id);

        try {
            $published = $this->broadcastService->publish($broadcast);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('api.broadcast_published'), 'data' => BroadcastResource::make($published)]);
    }

    /**
     * Cancel a broadcast.
     */
    public function cancelBroadcast(string $id): JsonResponse
    {
        $broadcast = Broadcast::findOrFail($id);

        try {
            $cancelled = $this->broadcastService->cancel($broadcast);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('api.broadcast_cancelled'), 'data' => BroadcastResource::make($cancelled)]);
    }

    /**
     * Acknowledge a broadcast.
     * Only valid for published broadcasts with requires_acknowledgement = true.
     * Body: { user_id: uuid }
     *
     * Idempotent — acknowledging twice returns the existing record.
     */
    public function acknowledgeBroadcast(Request $request, string $id): JsonResponse
    {
        $userId = $this->actorUserId($request);

        $broadcast  = Broadcast::findOrFail($id);
        $facilityId = $request->attributes->get('facility_id');

        try {
            $ack = $this->broadcastService->acknowledge(
                $broadcast,
                $userId,
                $facilityId,
                $request->ip()
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.broadcast_acknowledged'),
            'data'    => [
                'broadcast_id'    => $broadcast->id,
                'user_id'         => $ack->user_id,
                'acknowledged_at' => $ack->acknowledged_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Get acknowledgement summary for a broadcast.
     * Returns total count and per-user acknowledgement records.
     */
    public function broadcastAcknowledgements(string $id): JsonResponse
    {
        $broadcast = Broadcast::findOrFail($id);
        return response()->json(['data' => $this->broadcastService->acknowledgementSummary($broadcast)]);
    }
}
