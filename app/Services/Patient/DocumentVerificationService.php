<?php

namespace App\Services\Patient;

use App\Models\DocumentVerification;
use App\Models\Patient;
use App\Services\Documents\OfficialDocumentTokenService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Issues and checks the QR code printed on every OpesCare Secure document.
 *
 * Token shape mirrors the Health ID QR: `doc_{lookupId}_{secret}` — an O(1)
 * lookup on the first half, Hash::check on the second, so a leaked database
 * row cannot be turned back into a scannable code. The human reference
 * (OC-DOC-XXXX-XXXX) is what a facility reads out over the phone; it uses an
 * alphabet without 0/O/1/I so it survives being read aloud.
 *
 * The verifier page (check()) also accepts the older `vdt_…` tokens that
 * DocumentIssuanceService mints for every OfficialDocument, by delegating to
 * OfficialDocumentTokenService. A genuine token must never read as a forgery
 * just because it came from the other token store.
 */
class DocumentVerificationService
{
    public const TYPE_MEDICAL_RECORD = 'medical_record';

    public const STATE_VALID   = 'valid';
    public const STATE_REVOKED = 'revoked';
    public const STATE_EXPIRED = 'expired';
    public const STATE_INVALID = 'invalid';

    /** Reference alphabet: no 0/O, no 1/I. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(private readonly OfficialDocumentTokenService $officialTokens)
    {
    }

    /**
     * The one answer the /verify/doc page needs, for either token family:
     * a state (valid · revoked · expired · invalid) and, when valid, the card
     * describing the document. Counts the check on the row it names.
     *
     * @return array{state: string, card: VerifiedDocumentCard|null}
     */
    public function check(string $rawToken): array
    {
        if (OfficialDocumentTokenService::looksLikeToken($rawToken)) {
            $resolved = $this->officialTokens->resolve($rawToken);

            return [
                'state' => $resolved['state'],
                'card'  => $resolved['state'] === self::STATE_VALID && $resolved['document']
                    ? VerifiedDocumentCard::fromOfficialDocument($resolved['document'])
                    : null,
            ];
        }

        $record = $this->verify($rawToken);

        if ($record) {
            return ['state' => self::STATE_VALID, 'card' => VerifiedDocumentCard::fromVerification($record)];
        }

        return ['state' => $this->failureState($rawToken), 'card' => null];
    }

    /**
     * Create the verification row BEFORE the document is rendered — the QR
     * has to be on the page, so the token has to exist first. Seal the hash
     * with seal() once the bytes are final.
     *
     * @return array{record: DocumentVerification, raw_token: string, verify_url: string}
     */
    public function issue(Patient $patient, string $documentType, ?string $issuedByUserId = null): array
    {
        $lookupId = Str::random(12);
        $secret   = Str::random(32);
        $rawToken = "doc_{$lookupId}_{$secret}";

        $record = DocumentVerification::create([
            'patient_id'        => $patient->id,
            'document_type'     => $documentType,
            'reference'         => $this->uniqueReference(),
            'lookup_id'         => $lookupId,
            'token_hash'        => Hash::make($secret),
            'issued_by_user_id' => $issuedByUserId,
            'issued_at'         => Carbon::now(),
            'status'            => DocumentVerification::STATUS_VALID,
        ]);

        return [
            'record'     => $record,
            'raw_token'  => $rawToken,
            'verify_url' => route('verify.document', ['token' => $rawToken]),
        ];
    }

    /**
     * The same verification row for a FACILITY document — a pharmacy report,
     * the controlled-substance register — which has no patient. The verifier
     * page then shows the document type, reference, issue time and
     * fingerprint, and no holder.
     *
     * @return array{record: DocumentVerification, raw_token: string, verify_url: string}
     */
    public function issueForFacility(string $facilityId, string $documentType, ?string $issuedByUserId = null): array
    {
        $lookupId = Str::random(12);
        $secret   = Str::random(32);
        $rawToken = "doc_{$lookupId}_{$secret}";

        $record = DocumentVerification::create([
            'patient_id'        => null,
            'facility_id'       => $facilityId,
            'document_type'     => $documentType,
            'reference'         => $this->uniqueReference(),
            'lookup_id'         => $lookupId,
            'token_hash'        => Hash::make($secret),
            'issued_by_user_id' => $issuedByUserId,
            'issued_at'         => Carbon::now(),
            'status'            => DocumentVerification::STATUS_VALID,
        ]);

        return [
            'record'     => $record,
            'raw_token'  => $rawToken,
            'verify_url' => route('verify.document', ['token' => $rawToken]),
        ];
    }

    /** Record the fingerprint of the bytes that were actually issued. */
    public function seal(DocumentVerification $record, string $pdfBytes): void
    {
        $record->forceFill(['sha256' => hash('sha256', $pdfBytes)])->save();
    }

    /**
     * The row a scanned token names, if the token is genuine and the document
     * still stands. Counts the scan. Null for revoked, unknown or malformed.
     */
    public function verify(string $rawToken): ?DocumentVerification
    {
        $parts = $this->split($rawToken);

        if ($parts === null) {
            return null;
        }

        [$lookupId, $secret] = $parts;

        $record = DocumentVerification::where('lookup_id', $lookupId)->first();

        if (! $record || ! $record->isValid() || ! Hash::check($secret, $record->token_hash)) {
            return null;
        }

        $record->increment('scan_count', 1, ['last_verified_at' => Carbon::now()]);

        return $record->refresh();
    }

    /**
     * Why a token was refused, read from the row it names without trusting
     * the secret: `revoked` when the document was withdrawn, `invalid` for
     * everything else (unknown row, wrong secret, malformed token).
     */
    public function failureState(string $rawToken): string
    {
        $parts = $this->split($rawToken);

        if ($parts === null) {
            return 'invalid';
        }

        $record = DocumentVerification::where('lookup_id', $parts[0])->first();

        if ($record && $record->status === DocumentVerification::STATUS_REVOKED && Hash::check($parts[1], $record->token_hash)) {
            return 'revoked';
        }

        return 'invalid';
    }

    public function revoke(DocumentVerification $record, ?string $reason = null): void
    {
        $record->forceFill([
            'status'        => DocumentVerification::STATUS_REVOKED,
            'revoked_at'    => Carbon::now(),
            'revoke_reason' => $reason ? Str::limit($reason, 200, '') : null,
        ])->save();
    }

    /** @return array{0: string, 1: string}|null [lookupId, secret] */
    private function split(string $rawToken): ?array
    {
        $parts = explode('_', trim($rawToken), 3);

        if (count($parts) !== 3 || $parts[0] !== 'doc' || $parts[1] === '' || $parts[2] === '') {
            return null;
        }

        return [$parts[1], $parts[2]];
    }

    private function uniqueReference(): string
    {
        do {
            $body = '';
            for ($i = 0; $i < 8; $i++) {
                $body .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $reference = 'OC-DOC-' . substr($body, 0, 4) . '-' . substr($body, 4, 4);
        } while (DocumentVerification::where('reference', $reference)->exists());

        return $reference;
    }
}
