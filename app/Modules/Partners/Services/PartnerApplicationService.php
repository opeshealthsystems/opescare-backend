<?php

namespace App\Modules\Partners\Services;

use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Enums\TrustLevel;

class PartnerApplicationService
{
    public function approveApplication(Partner $partner): Partner
    {
        $partner->status = PartnerStatus::APPROVED->value;
        $partner->trust_level = TrustLevel::LEVEL_1_REGISTERED->value;
        $partner->save();

        // In a real system, we would log this in partner_governance_cases or audit tables.
        return $partner;
    }

    public function suspendPartner(Partner $partner, string $reason): Partner
    {
        $partner->status = PartnerStatus::SUSPENDED->value;
        // Optionally downgrade trust level, but suspension explicitly blocks anyway.
        $partner->save();

        // Suspend active agreements and permissions if necessary, or just rely on the partner's suspended status.
        return $partner;
    }
}
