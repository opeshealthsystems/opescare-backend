<?php

namespace App\Services\Patient;

use App\Models\DocumentVerification;
use App\Models\OfficialDocument;
use App\Models\Patient;
use Carbon\CarbonInterface;

/**
 * What the /verify/doc page shows about a genuine document, whichever token
 * family it was reached through. Nothing here is invented: every field is read
 * from the row the token names, and a field the row does not carry is null
 * (the page then leaves it out).
 */
final class VerifiedDocumentCard
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly ?string $patientId,
        public readonly string $documentType,
        public readonly string $reference,
        public readonly ?CarbonInterface $issuedAt,
        public readonly ?string $sha256,
        public readonly ?int $scanCount,
        public readonly ?Patient $patient,
    ) {
    }

    /** A `doc_…` token: an OpesCare Secure export in `document_verifications`. */
    public static function fromVerification(DocumentVerification $record): self
    {
        return new self(
            id: (string) $record->id,
            source: 'DocumentVerification',
            patientId: $record->patient_id,
            documentType: (string) $record->document_type,
            reference: (string) $record->reference,
            issuedAt: $record->issued_at,
            sha256: $record->sha256,
            scanCount: $record->scan_count,
            patient: $record->patient,
        );
    }

    /** A `vdt_…` token: an OfficialDocument in `document_verification_tokens`. */
    public static function fromOfficialDocument(OfficialDocument $document): self
    {
        $patient = $document->patient_id
            ? Patient::withoutGlobalScopes()->find($document->patient_id)
            : null;

        return new self(
            id: (string) $document->id,
            source: 'OfficialDocument',
            patientId: $document->patient_id,
            documentType: (string) $document->document_type,
            reference: (string) ($document->document_number ?: $document->verification_code),
            issuedAt: $document->issued_at ?? $document->created_at,
            sha256: $document->document_hash ?: $document->payload_hash,
            scanCount: null,
            patient: $patient,
        );
    }

    /** The first twelve hex digits of the fingerprint, grouped for reading. */
    public function shortFingerprint(): ?string
    {
        if (! $this->sha256 || strlen($this->sha256) < 12) {
            return null;
        }

        return strtoupper(implode(' ', str_split(substr($this->sha256, 0, 12), 4)));
    }
}
