<?php

namespace App\Modules\Partners\Services;

use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerDocument;
use App\Modules\Partners\Enums\TrustLevel;

class PartnerVerificationService
{
    private PartnerAuditService $auditService;

    public function __construct(PartnerAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function verifyDocument(PartnerDocument $document, string $reviewerId, ?string $notes = null): PartnerDocument
    {
        $oldStatus = $document->status;
        $document->status = 'verified';
        $document->reviewed_by = $reviewerId;
        $document->reviewed_at = now();
        $document->review_notes = $notes;
        $document->save();

        $this->auditService->log(
            $document->partner_id,
            'partner_document_verified',
            $oldStatus,
            'verified',
            "Document ID {$document->id} verified. Notes: {$notes}",
            $reviewerId
        );

        $this->checkAndUpgradeTrustLevel($document->partner);

        return $document;
    }

    private function checkAndUpgradeTrustLevel(Partner $partner)
    {
        // Simple logic: if all uploaded documents are verified, upgrade to LEVEL_2
        $hasUnverified = $partner->documents()->where('status', '!=', 'verified')->exists();
        
        if (!$hasUnverified && $partner->trust_level === TrustLevel::LEVEL_1_REGISTERED->value) {
            $partner->trust_level = TrustLevel::LEVEL_2_DOCUMENT_VERIFIED->value;
            $partner->save();

            $this->auditService->log(
                $partner->id,
                'partner_trust_level_changed',
                TrustLevel::LEVEL_1_REGISTERED->value,
                TrustLevel::LEVEL_2_DOCUMENT_VERIFIED->value,
                'All documents verified'
            );
        }
    }
}
