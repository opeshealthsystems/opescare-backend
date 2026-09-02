<?php

namespace App\Modules\PublicHealth\Services;

use App\Models\PublicHealthReport;
use App\Models\DataQualityCheck;
use App\Models\Facility;

class DataQualityCheckService
{
    /**
     * Compute a data quality score (0-100) and log warning/failed metrics in DB.
     */
    public function runQualityChecks(PublicHealthReport $report): int
    {
        // First delete existing checks to avoid accumulation
        DataQualityCheck::where('report_id', $report->id)->delete();

        $score = 100;
        $checks = [];

        // Check 1: Facility existence and active status
        $facility = Facility::find($report->facility_id);
        if (!$facility) {
            $score -= 30;
            $checks[] = [
                'check_code' => 'FACILITY_MISSING',
                'check_name' => 'Facility Validation',
                'status' => 'failed',
                'severity' => 'critical',
                'message' => 'The report facility does not exist.'
            ];
        } elseif ($facility->status !== 'active') {
            $score -= 15;
            $checks[] = [
                'check_code' => 'FACILITY_INACTIVE',
                'check_name' => 'Facility Status Check',
                'status' => 'warning',
                'severity' => 'medium',
                'message' => 'The facility is registered but currently marked as inactive.'
            ];
        }

        // Check 2: Verify non-negative values in items
        $negativeCounts = 0;
        foreach ($report->items as $item) {
            if ($item->value < 0) {
                $negativeCounts++;
            }
        }
        if ($negativeCounts > 0) {
            $score -= 25;
            $checks[] = [
                'check_code' => 'NEGATIVE_VALUES',
                'check_name' => 'Negative Count Verification',
                'status' => 'failed',
                'severity' => 'critical',
                'message' => "Found {$negativeCounts} items with invalid negative counts."
            ];
        }

        // Check 3: Check for duplicate draft reports in same period
        $duplicates = PublicHealthReport::where('report_type_id', $report->report_type_id)
            ->where('facility_id', $report->facility_id)
            ->where('reporting_period_start', $report->reporting_period_start)
            ->where('reporting_period_end', $report->reporting_period_end)
            ->where('id', '!=', $report->id)
            ->count();

        if ($duplicates > 0) {
            $score -= 20;
            $checks[] = [
                'check_code' => 'DUPLICATE_REPORT',
                'check_name' => 'Duplicate Draft Detection',
                'status' => 'warning',
                'severity' => 'medium',
                'message' => 'Another draft report exists for the same period.'
            ];
        }

        // Log checks in database
        foreach ($checks as $chk) {
            DataQualityCheck::create(array_merge($chk, [
                'report_id' => $report->id
            ]));
        }

        // Save quality score on the report
        $report->data_quality_score = max(0, $score);
        $report->save();

        return $report->data_quality_score;
    }
}
