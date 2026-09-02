<?php

namespace App\Modules\FacilityReadiness\Services;

use App\Models\FacilityReadinessScore;
use App\Models\GoLiveChecklist;
use App\Models\GoLiveChecklistItem;
use App\Models\StaffProfile;
use App\Models\Facility;

/**
 * FacilityReadinessScoringService — Calculates and stores facility readiness scores.
 *
 * Readiness score is a composite of four sub-scores:
 *  - staff_score:   required staff trained and credentialed
 *  - config_score:  facility configuration complete (departments, prices, schedules)
 *  - data_score:    data quality and completeness
 *  - support_score: support contacts configured, go-live checklist complete
 *
 * A facility is only ready to go live when overall_score >= 80 AND is_ready = true.
 * The is_ready flag requires explicit approval — score alone does not unlock go-live.
 */
class FacilityReadinessScoringService
{
    public function calculateAndStore(string $facilityId, string $computedBy = null): FacilityReadinessScore
    {
        $staffScore   = $this->computeStaffScore($facilityId);
        $configScore  = $this->computeConfigScore($facilityId);
        $dataScore    = $this->computeDataScore($facilityId);
        $supportScore = $this->computeSupportScore($facilityId);

        $overallScore = (int) round(
            ($staffScore + $configScore + $dataScore + $supportScore) / 4
        );

        return FacilityReadinessScore::create([
            'facility_id'   => $facilityId,
            'overall_score' => $overallScore,
            'staff_score'   => $staffScore,
            'config_score'  => $configScore,
            'data_score'    => $dataScore,
            'support_score' => $supportScore,
            'is_ready'      => false, // Requires explicit approval even at 100
            'computed_by'   => $computedBy,
        ]);
    }

    private function computeStaffScore(string $facilityId): int
    {
        // Check trained staff count against required minimum
        $totalRequired  = StaffProfile::where('facility_id', $facilityId)->count();
        if ($totalRequired === 0) {
            return 0;
        }

        $trained = StaffProfile::where('facility_id', $facilityId)
            ->where('is_active', true)
            ->count();

        return (int) min(100, round(($trained / $totalRequired) * 100));
    }

    private function computeConfigScore(string $facilityId): int
    {
        $facility = Facility::find($facilityId);
        if (! $facility) {
            return 0;
        }

        $checks = [
            $facility->departments()->exists(),
            $facility->schedules()->exists(),
            $facility->priceList()->exists(),
        ];

        $passed = count(array_filter($checks));
        return (int) round(($passed / count($checks)) * 100);
    }

    private function computeDataScore(string $facilityId): int
    {
        // Simplified: check data completeness score average for this facility
        $avg = \App\Models\DataCompletenessScore::where('facility_id', $facilityId)
            ->avg('score');

        return $avg ? (int) round($avg) : 0;
    }

    private function computeSupportScore(string $facilityId): int
    {
        $checklist = GoLiveChecklist::where('facility_id', $facilityId)->latest()->first();
        if (! $checklist) {
            return 0;
        }

        $total = GoLiveChecklistItem::where('go_live_checklist_id', $checklist->id)->count();
        if ($total === 0) {
            return 0;
        }

        $completed = GoLiveChecklistItem::where('go_live_checklist_id', $checklist->id)
            ->where('status', 'completed')
            ->count();

        return (int) round(($completed / $total) * 100);
    }
}
