<?php

namespace App\Modules\Partners\Services;

use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Enums\PartnerStatus;
use App\Modules\Partners\Models\PartnerContributionPermission;
use App\Modules\Partners\Models\PartnerAgreement;
use App\Modules\Partners\Models\PartnerAccessPermission;

class PartnerPermissionService
{
    /**
     * Strict check if a partner can contribute a specific type of data.
     */
    public function canContribute(Partner $partner, string $contributionType): bool
    {
        // 1. Partner must not be suspended/rejected/draft
        if (!in_array($partner->status, [PartnerStatus::ACTIVE->value, PartnerStatus::APPROVED->value])) {
            return false;
        }

        // 2. Must have an active agreement
        $hasActiveAgreement = PartnerAgreement::where('partner_id', $partner->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        if (!$hasActiveAgreement) {
            return false;
        }

        // 3. Must have explicit active contribution permission
        $hasPermission = PartnerContributionPermission::where('partner_id', $partner->id)
            ->where('contribution_type', $contributionType)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        return $hasPermission;
    }

    /**
     * Strict check if a partner can access a specific type of data.
     */
    public function canAccess(Partner $partner, string $accessType): bool
    {
        if (!in_array($partner->status, [PartnerStatus::ACTIVE->value, PartnerStatus::APPROVED->value])) {
            return false;
        }

        $hasActiveAgreement = PartnerAgreement::where('partner_id', $partner->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        if (!$hasActiveAgreement) {
            return false;
        }

        $hasPermission = PartnerAccessPermission::where('partner_id', $partner->id)
            ->where('access_type', $accessType)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->exists();

        return $hasPermission;
    }
}
