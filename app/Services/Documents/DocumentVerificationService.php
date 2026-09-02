<?php

namespace App\Services\Documents;

use App\Models\OfficialDocument;
use App\Models\DocumentVerificationToken;
use App\Models\DocumentVerificationEvent;
use Illuminate\Http\Request;

class DocumentVerificationService
{
    /**
     * Issue a secure, hashed token linked to an official document.
     */
    public function issueToken(string $documentId, ?string $token = null, ?int $expiryMinutes = null): string
    {
        $rawToken = $token ?? 'vdt_' . \Illuminate\Support\Str::random(10);
        $hash = hash('sha256', $rawToken);

        DocumentVerificationToken::create([
            'official_document_id' => $documentId,
            'token_hash' => $hash,
            'status' => 'active',
            'expires_at' => $expiryMinutes ? now()->addMinutes($expiryMinutes) : null,
        ]);

        return $rawToken;
    }

    /**
     * Verify a document's authenticity using a secure QR token.
     */
    public function verifyByToken(Request $request, string $rawToken): array
    {
        $hash = hash('sha256', $rawToken);
        $tokenRecord = DocumentVerificationToken::where('token_hash', $hash)->first();

        if (!$tokenRecord) {
            $this->logEvent(null, null, $hash, 'not_found', $request);
            return ['status' => 'not_found', 'document' => null];
        }

        $document = $tokenRecord->document;
        if (!$document) {
            $this->logEvent(null, null, $hash, 'not_found', $request);
            return ['status' => 'not_found', 'document' => null];
        }

        // Evaluate token status
        if ($tokenRecord->status === 'revoked' || $tokenRecord->revoked_at) {
            $this->logEvent($document->id, $document->verification_code, $hash, 'token_revoked', $request);
            return ['status' => 'revoked', 'document' => $document];
        }

        if ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast()) {
            $tokenRecord->update(['status' => 'expired']);
            $this->logEvent($document->id, $document->verification_code, $hash, 'token_expired', $request);
            return ['status' => 'token_expired', 'document' => $document];
        }

        // Update last used at
        $tokenRecord->update(['last_used_at' => now()]);

        // Evaluate document status
        $status = $this->evaluateDocumentStatus($document);
        $this->logEvent($document->id, $document->verification_code, $hash, $status, $request);

        return [
            'status' => $status,
            'document' => $document
        ];
    }

    /**
     * Verify a document's authenticity manually via alphanumeric code entry.
     */
    public function verifyByCode(Request $request, string $code, ?string $documentNumber = null): array
    {
        $query = OfficialDocument::where('verification_code', $code);
        if ($documentNumber) {
            $query->where('document_number', $documentNumber);
        }
        $document = $query->first();

        if (!$document) {
            $this->logEvent(null, $code, null, 'not_found', $request);
            return ['status' => 'not_found', 'document' => null];
        }

        $status = $this->evaluateDocumentStatus($document);
        $this->logEvent($document->id, $code, null, $status, $request);

        return [
            'status' => $status,
            'document' => $document
        ];
    }

    /**
     * Map clinical and system status into standard verification response.
     */
    private function evaluateDocumentStatus(OfficialDocument $document): string
    {
        if ($document->status === 'revoked') {
            return 'revoked';
        }
        if ($document->status === 'cancelled') {
            return 'cancelled';
        }
        if ($document->status === 'entered_in_error') {
            return 'entered_in_error';
        }
        if ($document->status === 'superseded' || $document->status === 'amended') {
            return 'superseded';
        }
        if ($document->expires_at && $document->expires_at->isPast()) {
            return 'expired';
        }

        return 'valid';
    }

    /**
     * Persist verification logs for rate limits and audit requirements.
     */
    private function logEvent(?string $documentId, ?string $code, ?string $tokenHash, string $result, Request $request): void
    {
        DocumentVerificationEvent::create([
            'official_document_id' => $documentId,
            'verification_code' => $code,
            'token_hash' => $tokenHash,
            'result' => $result,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'verified_by_user_id' => auth()->id(),
            'public_verification' => !auth()->check(),
        ]);
    }
}
