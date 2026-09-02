<?php

namespace App\Services\Documents;

use App\Models\OfficialDocument;
use App\Models\DocumentVersion;

class DocumentAmendmentService
{
    /**
     * Amend an issued document, creating an immutable backup version.
     */
    public function amend(string $id, array $newPayload, string $reason, string $userId): OfficialDocument
    {
        if (empty($reason)) {
            throw new \InvalidArgumentException('DOCUMENT_AMENDMENT_REASON_REQUIRED');
        }

        $document = OfficialDocument::findOrFail($id);

        if ($document->status === 'revoked') {
            throw new \LogicException('DOCUMENT_REVOKED');
        }
        if ($document->status === 'cancelled') {
            throw new \LogicException('DOCUMENT_CANCELLED');
        }
        if ($document->status === 'entered_in_error') {
            throw new \LogicException('DOCUMENT_ENTERED_IN_ERROR');
        }

        // 1. Create immutable backup version
        DocumentVersion::create([
            'official_document_id' => $document->id,
            'version' => $document->version,
            'payload_json' => $document->payload_json,
            'standard_mapping_json' => $document->standard_mapping_json,
            'pdf_path' => $document->pdf_path,
            'document_hash' => $document->document_hash,
            'payload_hash' => $document->payload_hash,
            'change_reason' => $reason,
            'created_by' => $userId
        ]);

        // 2. Compute new version number
        $currentVersion = (float) $document->version;
        $newVersion = number_format($currentVersion + 0.1, 1);

        // 3. Calculate new payload hash
        $newPayloadHash = hash('sha256', json_encode($newPayload));

        // 4. Update the document status and payload
        $document->update([
            'payload_json' => $newPayload,
            'version' => $newVersion,
            'payload_hash' => $newPayloadHash,
            'status' => 'amended',
            'updated_at' => now()
        ]);

        return $document;
    }
}
