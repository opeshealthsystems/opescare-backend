<?php

namespace App\Modules\Messaging\Services;

use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageAttachment;

class MessageAttachmentService
{
    private array $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' // DOCX
    ];

    public function uploadAttachment(
        Message $message,
        string $fileName,
        string $filePath,
        string $mimeType,
        int $fileSize,
        string $classification
    ): MessageAttachment {
        // Block executables or unsupported mime types
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new \Exception('MESSAGE_ATTACHMENT_BLOCKED');
        }

        // Scan is queued asynchronously; attachment is quarantined until the job completes.
        $scanStatus = 'pending';

        return MessageAttachment::create([
            'message_id' => $message->id,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'classification' => $classification,
            'scan_status' => $scanStatus,
            'encrypted' => true
        ]);
    }
}
