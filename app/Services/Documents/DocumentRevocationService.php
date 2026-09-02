<?php

namespace App\Services\Documents;

use App\Models\OfficialDocument;

class DocumentRevocationService
{
    /**
     * Revoke an issued document with a documented reason.
     */
    public function revoke(string $id, string $reason): OfficialDocument
    {
        if (empty($reason)) {
            throw new \InvalidArgumentException('DOCUMENT_REVOCATION_REASON_REQUIRED');
        }

        $document = OfficialDocument::findOrFail($id);

        if ($document->status === 'revoked') {
            throw new \LogicException('DOCUMENT_ALREADY_REVOKED');
        }

        // Revoke linked verification tokens
        $document->verificationTokens()->update([
            'status' => 'revoked',
            'revoked_at' => now()
        ]);

        $document->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $reason
        ]);

        return $document;
    }

    /**
     * Mark an issued document as entered in error with a documented reason.
     */
    public function markAsEnteredInError(string $id, string $reason): OfficialDocument
    {
        if (empty($reason)) {
            throw new \InvalidArgumentException('DOCUMENT_ENTERED_IN_ERROR_REASON_REQUIRED');
        }

        $document = OfficialDocument::findOrFail($id);

        if ($document->status === 'entered_in_error') {
            throw new \LogicException('DOCUMENT_ALREADY_ENTERED_IN_ERROR');
        }

        $document->verificationTokens()->update([
            'status' => 'revoked',
            'revoked_at' => now()
        ]);

        $document->update([
            'status' => 'entered_in_error',
            'revoked_at' => now(),
            'revocation_reason' => $reason
        ]);

        return $document;
    }
}
