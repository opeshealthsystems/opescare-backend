<?php

namespace App\Modules\Messaging\Services;

use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;
use App\Modules\Messaging\Models\Message;
use App\Services\Security\KmsEncryptionService;
use Illuminate\Support\Str;

class MessagingService
{
    private MessagePermissionService $permissionService;
    private KmsEncryptionService $kms;

    public function __construct(MessagePermissionService $permissionService, KmsEncryptionService $kms)
    {
        $this->permissionService = $permissionService;
        $this->kms = $kms;
    }

    public function decryptBody(string $encryptedBody): string
    {
        try {
            return $this->kms->decrypt($encryptedBody);
        } catch (\Throwable) {
            return $encryptedBody; // graceful fallback for legacy plaintext messages
        }
    }

    public function encryptBody(string $plaintext): string
    {
        return $this->kms->encrypt($plaintext);
    }

    public function createThread(string $creatorId, string $creatorRole, array $details): MessageThread
    {
        if (!$this->permissionService->canCreateThread($creatorId, $creatorRole, $details)) {
            throw new \Exception('MESSAGE_RECIPIENT_NOT_ALLOWED');
        }

        $thread = MessageThread::create([
            'uuid' => Str::uuid(),
            'thread_type' => $details['thread_type'],
            'context_type' => $details['context_type'] ?? null,
            'context_id' => $details['context_id'] ?? null,
            'organization_id' => $details['organization_id'] ?? null,
            'facility_id' => $details['facility_id'] ?? null,
            'patient_id' => $details['patient_id'] ?? null,
            'title' => $details['title'],
            'priority' => $details['priority'] ?? 'normal',
            'status' => 'open',
            'created_by' => $creatorId
        ]);

        // Add creator as participant
        MessageThreadParticipant::create([
            'thread_id' => $thread->id,
            'user_id' => $creatorId,
            'role_in_thread' => $creatorRole,
            'status' => 'active'
        ]);

        // Add recipient if present
        if (isset($details['recipient_id'])) {
            MessageThreadParticipant::create([
                'thread_id' => $thread->id,
                'user_id' => $details['recipient_id'],
                'role_in_thread' => $details['recipient_role'] ?? 'participant',
                'status' => 'active'
            ]);
        }

        return $thread;
    }

    public function sendMessage(string $threadUuid, string $senderId, string $body, string $type = 'text'): Message
    {
        $thread = MessageThread::where('uuid', $threadUuid)->firstOrFail();

        if ($thread->status === 'closed') {
            throw new \Exception('MESSAGE_THREAD_CLOSED');
        }

        return Message::create([
            'uuid'         => Str::uuid(),
            'thread_id'    => $thread->id,
            'sender_id'    => $senderId,
            'message_type' => $type,
            'body'         => $this->kms->encrypt($body),
            'status'       => 'sent',
        ]);
    }

    public function closeThread(string $threadUuid): MessageThread
    {
        $thread = MessageThread::where('uuid', $threadUuid)->firstOrFail();
        $thread->status = 'closed';
        $thread->closed_at = now();
        $thread->save();
        return $thread;
    }

    public function reopenThread(string $threadUuid): MessageThread
    {
        $thread = MessageThread::where('uuid', $threadUuid)->firstOrFail();
        $thread->status = 'open';
        $thread->closed_at = null;
        $thread->save();
        return $thread;
    }

    public function applyLegalHold(string $threadUuid, bool $hold = true): MessageThread
    {
        $thread = MessageThread::where('uuid', $threadUuid)->firstOrFail();
        $thread->legal_hold = $hold;
        $thread->save();
        return $thread;
    }
}
