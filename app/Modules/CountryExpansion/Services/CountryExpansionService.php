<?php

namespace App\Modules\CountryExpansion\Services;

use App\Models\Country;
use App\Models\CountryLaunchApproval;
use App\Models\CountryLegalReview;
use App\Models\CountryHealthRegulation;
use App\Models\CountryPaymentSetting;

/**
 * CountryExpansionService — Module 18 (Country Expansion Framework)
 *
 * Orchestrates country onboarding and launch approval workflow.
 *
 * Rule (NON-NEGOTIABLE):
 * "Do not expand to a new country without legal, language, facility registry,
 * public health, and data residency review."
 *
 * A CountryLaunchApproval must be approved before any facilities in the
 * country can go live.
 */
class CountryExpansionService
{
    /**
     * Initiate the launch approval process for a country.
     * Creates a draft launch approval record if none exists.
     */
    public function initiateLaunch(Country $country): CountryLaunchApproval
    {
        return CountryLaunchApproval::firstOrCreate(
            ['country_id' => $country->id],
            ['status' => 'pending']
        );
    }

    /**
     * Update the checklist items for a country's launch approval.
     */
    public function updateChecklist(CountryLaunchApproval $approval, array $checks): void
    {
        $approval->update(array_intersect_key($checks, [
            'legal_review_complete'             => null,
            'health_regulation_review_complete' => null,
            'language_pack_ready'               => null,
            'payment_configured'                => null,
            'pilot_facility_selected'           => null,
            'data_residency_reviewed'           => null,
        ]));

        // Auto-advance to in_progress if at least one check is complete
        if ($approval->status === 'pending' && $approval->readinessPercent() > 0) {
            $approval->update(['status' => 'in_progress']);
        }
    }

    /**
     * Approve country launch — requires all checklist items passed.
     */
    public function approveLaunch(
        CountryLaunchApproval $approval,
        string $approvedBy,
        ?string $notes = null
    ): void {
        if (! $approval->allChecklistItemsPassed()) {
            $missing = $this->getMissingChecklist($approval);
            throw new \RuntimeException(
                'Cannot approve launch — missing checklist items: ' . implode(', ', $missing)
            );
        }

        $approval->approve($approvedBy, $notes);
    }

    /**
     * Check if a country is approved for live operations.
     */
    public function isApprovedForLaunch(Country $country): bool
    {
        $approval = CountryLaunchApproval::where('country_id', $country->id)
            ->where('status', 'approved')
            ->first();

        return $approval !== null;
    }

    /**
     * Get missing checklist items for an approval.
     */
    public function getMissingChecklist(CountryLaunchApproval $approval): array
    {
        $missing = [];
        if (! $approval->legal_review_complete) {
            $missing[] = 'legal_review';
        }
        if (! $approval->health_regulation_review_complete) {
            $missing[] = 'health_regulation_review';
        }
        if (! $approval->language_pack_ready) {
            $missing[] = 'language_pack';
        }
        if (! $approval->payment_configured) {
            $missing[] = 'payment_configuration';
        }
        if (! $approval->pilot_facility_selected) {
            $missing[] = 'pilot_facility';
        }
        if (! $approval->data_residency_reviewed) {
            $missing[] = 'data_residency_review';
        }
        return $missing;
    }
}
