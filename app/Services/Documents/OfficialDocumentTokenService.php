<?php

namespace App\Services\Documents;

use App\Models\DocumentVerificationToken;
use App\Models\OfficialDocument;
use Illuminate\Support\Str;

/**
 * The `vdt_…` verification token minted for every OfficialDocument.
 *
 * Issued by DocumentIssuanceService on every issue (prescriptions, appointment
 * confirmations). Rows live in `document_verification_tokens`, keyed by the
 * SHA-256 of the raw token. This class was formerly a second
 * `DocumentVerificationService`; it is NOT the OpesCare Secure verifier — that
 * is App\Services\Patient\DocumentVerificationService (`doc_…` tokens in
 * `document_verifications`), which delegates to resolve() below so a `vdt_`
 * token scanned at /verify/doc/{token} gets a real answer instead of
 * "not recognised".
 */
class OfficialDocumentTokenService
{
    public const PREFIX = 'vdt_';

    public const STATE_VALID   = 'valid';
    public const STATE_REVOKED = 'revoked';
    public const STATE_EXPIRED = 'expired';
    public const STATE_INVALID = 'invalid';

    /**
     * Issue a hashed token linked to an official document. Returns the raw
     * token; only its hash is stored.
     */
    public function issueToken(string $documentId, ?string $token = null, ?int $expiryMinutes = null): string
    {
        $rawToken = $token ?? self::PREFIX . Str::random(10);

        DocumentVerificationToken::create([
            'official_document_id' => $documentId,
            'token_hash'           => hash('sha256', $rawToken),
            'status'               => 'active',
            'expires_at'           => $expiryMinutes ? now()->addMinutes($expiryMinutes) : null,
        ]);

        return $rawToken;
    }

    public static function looksLikeToken(string $rawToken): bool
    {
        return str_starts_with(trim($rawToken), self::PREFIX);
    }

    /**
     * Resolve a raw `vdt_` token to one of the verifier states.
     *
     * @return array{state: string, document: OfficialDocument|null, token: DocumentVerificationToken|null}
     */
    public function resolve(string $rawToken): array
    {
        $rawToken = trim($rawToken);

        if (! self::looksLikeToken($rawToken)) {
            return ['state' => self::STATE_INVALID, 'document' => null, 'token' => null];
        }

        $tokenRecord = DocumentVerificationToken::withoutGlobalScopes()
            ->where('token_hash', hash('sha256', $rawToken))
            ->first();

        if (! $tokenRecord) {
            return ['state' => self::STATE_INVALID, 'document' => null, 'token' => null];
        }

        $document = OfficialDocument::withoutGlobalScopes()->find($tokenRecord->official_document_id);

        if (! $document) {
            return ['state' => self::STATE_INVALID, 'document' => null, 'token' => $tokenRecord];
        }

        if ($tokenRecord->status === 'revoked' || $tokenRecord->revoked_at) {
            return ['state' => self::STATE_REVOKED, 'document' => $document, 'token' => $tokenRecord];
        }

        if ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast()) {
            return ['state' => self::STATE_EXPIRED, 'document' => $document, 'token' => $tokenRecord];
        }

        $state = $this->documentState($document);

        if ($state === self::STATE_VALID) {
            $tokenRecord->forceFill(['last_used_at' => now()])->save();
        }

        return ['state' => $state, 'document' => $document, 'token' => $tokenRecord];
    }

    /**
     * Map the document's own lifecycle status onto the verifier's states. A
     * withdrawn, cancelled, erroneous or superseded document reads as
     * "withdrawn" to whoever holds the paper; it is never a forgery.
     */
    private function documentState(OfficialDocument $document): string
    {
        $status = (string) $document->status;

        if (in_array($status, ['revoked', 'cancelled', 'entered_in_error', 'superseded', 'amended'], true) || $document->revoked_at) {
            return self::STATE_REVOKED;
        }

        if ($document->expires_at && $document->expires_at->isPast()) {
            return self::STATE_EXPIRED;
        }

        return self::STATE_VALID;
    }
}
