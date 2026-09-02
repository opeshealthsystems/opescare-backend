<?php

namespace App\Modules\Partners\Services;

use App\Modules\Partners\Models\PartnerIntegration;
use App\Modules\Partners\Enums\TrustLevel;

class PartnerIntegrationGovernanceService
{
    private PartnerAuditService $auditService;

    public function __construct(PartnerAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function certifyIntegration(PartnerIntegration $integration, string $actorId): PartnerIntegration
    {
        $oldStatus = $integration->status;
        $integration->status = 'certified';
        $integration->certified_at = now();
        $integration->save();

        $this->auditService->log(
            $integration->partner_id,
            'partner_integration_certified',
            $oldStatus,
            'certified',
            "Integration ID {$integration->id} certified.",
            $actorId
        );

        return $integration;
    }

    public function enableProduction(PartnerIntegration $integration, string $actorId): PartnerIntegration
    {
        if ($integration->status !== 'certified') {
            throw new \Exception('Integration must be certified before enabling production access.');
        }

        // Must have minimum trust level (e.g. LEVEL_3_OPERATIONAL_VERIFIED)
        $trustLevels = [
            TrustLevel::LEVEL_3_OPERATIONAL_VERIFIED->value,
            TrustLevel::LEVEL_4_CLINICAL_TRUSTED->value,
            TrustLevel::LEVEL_5_GOVERNANCE_TRUSTED->value
        ];

        if (!in_array($integration->partner->trust_level, $trustLevels)) {
            throw new \Exception('Partner trust level is insufficient for production API access.');
        }

        $oldEnv = $integration->environment;
        $integration->environment = 'production';
        $integration->status = 'production_active';
        $integration->production_enabled_at = now();
        $integration->save();

        $this->auditService->log(
            $integration->partner_id,
            'partner_production_enabled',
            $oldEnv,
            'production',
            "Production API access enabled for Integration ID {$integration->id}.",
            $actorId
        );

        return $integration;
    }
}
