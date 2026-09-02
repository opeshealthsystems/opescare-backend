<?php

namespace App\Modules\Partners\Services;

use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerAgreement;
use App\Modules\Partners\Enums\TrustLevel;

class PartnerAgreementService
{
    private PartnerAuditService $auditService;

    public function __construct(PartnerAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function markAgreementSigned(PartnerAgreement $agreement, ?string $actorId = null): PartnerAgreement
    {
        $oldStatus = $agreement->status;
        $agreement->status = 'active';
        $agreement->signed_by_partner_at = now();
        $agreement->signed_by_opescare_at = now();
        $agreement->save();

        $this->auditService->log(
            $agreement->partner_id,
            'partner_agreement_signed',
            $oldStatus,
            'active',
            "Agreement ID {$agreement->id} ({$agreement->agreement_type}) signed.",
            $actorId
        );

        $this->checkAndUpgradeTrustLevel($agreement->partner);

        return $agreement;
    }

    private function checkAndUpgradeTrustLevel(Partner $partner)
    {
        // If they have an active clinical contribution agreement, upgrade to LEVEL_4
        $hasClinical = $partner->agreements()->where('agreement_type', 'clinical_contribution_agreement')
                                            ->where('status', 'active')
                                            ->exists();
        
        if ($hasClinical && in_array($partner->trust_level, [
            TrustLevel::LEVEL_1_REGISTERED->value,
            TrustLevel::LEVEL_2_DOCUMENT_VERIFIED->value,
            TrustLevel::LEVEL_3_OPERATIONAL_VERIFIED->value
        ])) {
            $oldLevel = $partner->trust_level;
            $partner->trust_level = TrustLevel::LEVEL_4_CLINICAL_TRUSTED->value;
            $partner->save();

            $this->auditService->log(
                $partner->id,
                'partner_trust_level_changed',
                $oldLevel,
                TrustLevel::LEVEL_4_CLINICAL_TRUSTED->value,
                'Clinical agreement signed'
            );
        }
    }
}
