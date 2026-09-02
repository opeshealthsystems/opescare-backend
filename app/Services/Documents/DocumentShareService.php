<?php

namespace App\Services\Documents;

use App\Models\OfficialDocument;
use App\Models\DocumentShareLink;
use Illuminate\Support\Str;

class DocumentShareService
{
    /**
     * Generate a temporary secure share link for a document.
     */
    public function generateShareLink(string $documentId, string $createdById, ?int $expiryMinutes = 60, ?int $maxAccess = null, ?string $recipient = null): array
    {
        $rawToken = 'vsl_' . Str::random(12);
        $hash = hash('sha256', $rawToken);

        $shareLink = DocumentShareLink::create([
            'official_document_id' => $documentId,
            'share_token_hash' => $hash,
            'created_by' => $createdById,
            'recipient_contact' => $recipient,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'max_access_count' => $maxAccess,
            'access_count' => 0
        ]);

        return [
            'token' => $rawToken,
            'expires_at' => $shareLink->expires_at,
            'url' => route('document.share.view', ['token' => $rawToken], true)
        ];
    }

    /**
     * Resolve and audit a temporary share link.
     */
    public function resolveShareLink(string $rawToken): OfficialDocument
    {
        $hash = hash('sha256', $rawToken);
        $shareRecord = DocumentShareLink::where('share_token_hash', $hash)->first();

        if (!$shareRecord) {
            throw new \RuntimeException('DOCUMENT_SHARE_LINK_NOT_FOUND');
        }

        if ($shareRecord->revoked_at) {
            throw new \RuntimeException('DOCUMENT_SHARE_LINK_REVOKED');
        }

        if ($shareRecord->expires_at->isPast()) {
            throw new \RuntimeException('DOCUMENT_SHARE_LINK_EXPIRED');
        }

        if ($shareRecord->max_access_count && $shareRecord->access_count >= $shareRecord->max_access_count) {
            throw new \RuntimeException('DOCUMENT_SHARE_LINK_EXPIRED');
        }

        // Increment access counter
        $shareRecord->increment('access_count');

        return $shareRecord->document;
    }

    /**
     * Revoke a temporary share link.
     */
    public function revokeShareLink(string $id): void
    {
        $shareRecord = DocumentShareLink::findOrFail($id);
        $shareRecord->update(['revoked_at' => now()]);
    }
}
