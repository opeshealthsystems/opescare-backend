<?php

namespace App\Modules\PublicHealth\Services;

use App\Models\DataQualityCheck;
use App\Models\Facility;
use App\Models\PublicHealthReport;
use App\Services\Interoperability\Dhis2PushService;

/**
 * Imported from the platform. Finder adaptations: the duplicate check only looks at
 * reports of the same type that are not cancelled, and three DHIS2 checks were added
 * (facility org unit not mapped, data elements not mapped, no source data). Warnings
 * never block approval; a failed check does.
 */
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

        // Check 3: Check for duplicate reports in same period
        $duplicates = PublicHealthReport::where('report_type_id', $report->report_type_id)
            ->where('facility_id', $report->facility_id)
            ->where('reporting_period_start', $report->reporting_period_start)
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $report->id)
            ->count();

        if ($duplicates > 0) {
            $score -= 20;
            $checks[] = [
                'check_code' => 'DUPLICATE_REPORT',
                'check_name' => 'Duplicate Draft Detection',
                'status' => 'warning',
                'severity' => 'medium',
                'message' => 'Another report exists for the same period.'
            ];
        }

        // Finder: DHIS2 mapping checks.
        $mapping = app(Dhis2PushService::class);
        if ($mapping->orgUnitFor($report->facility_id) === null) {
            $score -= 20;
            $checks[] = [
                'check_code' => 'ORG_UNIT_NOT_MAPPED',
                'check_name' => 'DHIS2 org unit',
                'status' => 'warning',
                'severity' => 'high',
                'message' => 'The facility has no DHIS2 org unit; it will be skipped until one is set.'
            ];
        }

        $unmapped = $report->items->filter(fn ($item) => $mapping->dataElementFor($item->indicator_code) === null)->count();
        if ($unmapped > 0) {
            $score -= 10;
            $checks[] = [
                'check_code' => 'DATA_ELEMENTS_NOT_MAPPED',
                'check_name' => 'DHIS2 data elements',
                'status' => 'warning',
                'severity' => 'medium',
                'message' => "{$unmapped} indicator(s) have no DHIS2 data element and will not be sent.",
                'field_reference' => (string) $unmapped,
            ];
        }

        if ($report->items->isEmpty()) {
            $checks[] = [
                'check_code' => 'NO_SOURCE_DATA',
                'check_name' => 'Source data',
                'status' => 'warning',
                'severity' => 'low',
                'message' => 'No source records for this facility in this period; nothing would be sent.'
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
