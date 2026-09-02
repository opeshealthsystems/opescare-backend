<?php

namespace App\Modules\PublicHealth\Services;

use App\Models\PublicHealthReport;
use App\Models\ReportItem;
use App\Models\ReportType;
use App\Models\ReportingRule;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Diagnosis;
use App\Models\PharmacyInventory;
use App\Models\BloodInventory;
use Illuminate\Support\Facades\DB;

class DraftGenerationService
{
    /**
     * Generate draft reports for a facility for a given time window.
     */
    public function generateDrafts(string $facilityId, string $periodStart, string $periodEnd): array
    {
        $generatedReports = [];
        $rules = ReportingRule::where('status', 'active')->with('reportType')->get();

        foreach ($rules as $rule) {
            $reportType = $rule->reportType;
            if (!$reportType || !$reportType->is_active) {
                continue;
            }

            // Check if draft already exists to avoid duplication
            $existing = PublicHealthReport::where('report_type_id', $reportType->id)
                ->where('facility_id', $facilityId)
                ->where('reporting_period_start', $periodStart)
                ->where('reporting_period_end', $periodEnd)
                ->first();

            if ($existing) {
                continue;
            }

            // Perform draft generation based on report type code
            switch ($reportType->code) {
                case 'notifiable_disease':
                    $report = $this->generateNotifiableDiseaseDraft($facilityId, $periodStart, $periodEnd, $rule);
                    if ($report) $generatedReports[] = $report;
                    break;

                case 'facility_aggregate':
                    $report = $this->generateFacilityAggregateDraft($facilityId, $periodStart, $periodEnd, $rule);
                    if ($report) $generatedReports[] = $report;
                    break;

                case 'lab_surveillance':
                    $report = $this->generateLabSurveillanceDraft($facilityId, $periodStart, $periodEnd, $rule);
                    if ($report) $generatedReports[] = $report;
                    break;

                case 'pharmacy_stockout':
                    $report = $this->generatePharmacyStockoutDraft($facilityId, $periodStart, $periodEnd, $rule);
                    if ($report) $generatedReports[] = $report;
                    break;

                case 'blood_shortage':
                    $report = $this->generateBloodShortageDraft($facilityId, $periodStart, $periodEnd, $rule);
                    if ($report) $generatedReports[] = $report;
                    break;
            }
        }

        return $generatedReports;
    }

    private function generateNotifiableDiseaseDraft(string $facilityId, string $periodStart, string $periodEnd, ReportingRule $rule): ?PublicHealthReport
    {
        // Query diagnoses representing reportable conditions
        // Filter by matching conditions defined in rule triggers, or common notifiable diseases
        $diseases = ['Malaria', 'Measles', 'Cholera', 'Yellow Fever', 'Tuberculosis'];

        $cases = Diagnosis::whereIn('display_name', $diseases)
            ->whereHas('visit', function ($query) use ($facilityId, $periodStart, $periodEnd) {
                $query->where('facility_id', $facilityId)
                      ->whereBetween('started_at', [$periodStart, $periodEnd]);
            })
            ->select('display_name', 'code', DB::raw('count(*) as total'))
            ->groupBy('display_name', 'code')
            ->get();

        if ($cases->isEmpty()) {
            return null;
        }

        $report = PublicHealthReport::create([
            'report_type_id' => $rule->report_type_id,
            'facility_id' => $facilityId,
            'reporting_period_start' => $periodStart,
            'reporting_period_end' => $periodEnd,
            'status' => 'draft',
            'sensitivity_level' => $rule->requires_patient_identity ? 'identifiable' : 'aggregate',
            'data_classification' => 'sensitive',
            'generated_by_system' => true,
            'data_quality_score' => 100,
            'requires_review' => $rule->requires_review
        ]);

        foreach ($cases as $case) {
            ReportItem::create([
                'report_id' => $report->id,
                'indicator_code' => 'DISEASE_' . strtoupper(str_replace(' ', '_', $case->display_name)),
                'indicator_name' => $case->display_name,
                'value' => $case->total,
                'disease_code' => $case->code
            ]);
        }

        // Run data quality scoring
        $qualityService = new DataQualityCheckService();
        $qualityService->runQualityChecks($report);

        return $report;
    }

    private function generateFacilityAggregateDraft(string $facilityId, string $periodStart, string $periodEnd, ReportingRule $rule): ?PublicHealthReport
    {
        // Query visits statistics
        $visitsCount = Visit::where('facility_id', $facilityId)
            ->whereBetween('started_at', [$periodStart, $periodEnd])
            ->count();

        $admissionsCount = Visit::where('facility_id', $facilityId)
            ->where('visit_type', 'inpatient')
            ->whereBetween('started_at', [$periodStart, $periodEnd])
            ->count();

        $report = PublicHealthReport::create([
            'report_type_id' => $rule->report_type_id,
            'facility_id' => $facilityId,
            'reporting_period_start' => $periodStart,
            'reporting_period_end' => $periodEnd,
            'status' => 'draft',
            'sensitivity_level' => 'aggregate',
            'data_classification' => 'public',
            'generated_by_system' => true,
            'data_quality_score' => 100,
            'requires_review' => $rule->requires_review
        ]);

        ReportItem::create([
            'report_id' => $report->id,
            'indicator_code' => 'OUTPATIENT_VISITS',
            'indicator_name' => 'Outpatient Visits',
            'value' => $visitsCount
        ]);

        ReportItem::create([
            'report_id' => $report->id,
            'indicator_code' => 'INPATIENT_ADMISSIONS',
            'indicator_name' => 'Inpatient Admissions',
            'value' => $admissionsCount
        ]);

        $qualityService = new DataQualityCheckService();
        $qualityService->runQualityChecks($report);

        return $report;
    }

    private function generateLabSurveillanceDraft(string $facilityId, string $periodStart, string $periodEnd, ReportingRule $rule): ?PublicHealthReport
    {
        // Simple aggregate of lab positivity based on diagnoses with positive outcomes
        // In OpesCare, lab-confirmed cases are verified clinical diagnoses
        $labCases = Diagnosis::whereIn('display_name', ['Malaria', 'Measles'])
            ->where('status', 'active')
            ->whereHas('visit', function ($query) use ($facilityId, $periodStart, $periodEnd) {
                $query->where('facility_id', $facilityId)
                      ->whereBetween('started_at', [$periodStart, $periodEnd]);
            })
            ->select('display_name', DB::raw('count(*) as total'))
            ->groupBy('display_name')
            ->get();

        $report = PublicHealthReport::create([
            'report_type_id' => $rule->report_type_id,
            'facility_id' => $facilityId,
            'reporting_period_start' => $periodStart,
            'reporting_period_end' => $periodEnd,
            'status' => 'draft',
            'sensitivity_level' => 'aggregate',
            'data_classification' => 'internal',
            'generated_by_system' => true,
            'data_quality_score' => 100,
            'requires_review' => $rule->requires_review
        ]);

        foreach ($labCases as $case) {
            ReportItem::create([
                'report_id' => $report->id,
                'indicator_code' => 'LAB_POSITIVE_' . strtoupper($case->display_name),
                'indicator_name' => $case->display_name . ' Lab Positive Count',
                'value' => $case->total
            ]);
        }

        $qualityService = new DataQualityCheckService();
        $qualityService->runQualityChecks($report);

        return $report;
    }

    private function generatePharmacyStockoutDraft(string $facilityId, string $periodStart, string $periodEnd, ReportingRule $rule): ?PublicHealthReport
    {
        // Exclude expired, recalled, or quarantined stocks as required
        $stockOuts = PharmacyInventory::where('facility_id', $facilityId)
            ->where('stock_status', 'out_of_stock')
            ->where('is_expired', false)
            ->where('is_recalled', false)
            ->where('is_quarantined', false)
            ->get();

        if ($stockOuts->isEmpty()) {
            return null;
        }

        $report = PublicHealthReport::create([
            'report_type_id' => $rule->report_type_id,
            'facility_id' => $facilityId,
            'reporting_period_start' => $periodStart,
            'reporting_period_end' => $periodEnd,
            'status' => 'draft',
            'sensitivity_level' => 'aggregate',
            'data_classification' => 'internal',
            'generated_by_system' => true,
            'data_quality_score' => 100,
            'requires_review' => $rule->requires_review
        ]);

        foreach ($stockOuts as $item) {
            ReportItem::create([
                'report_id' => $report->id,
                'indicator_code' => 'STOCKOUT_' . strtoupper(str_replace(' ', '_', $item->generic_name)),
                'indicator_name' => $item->medicine_name . ' Stockout',
                'value' => 1,
                'metadata_json' => [
                    'strength' => $item->strength,
                    'form' => $item->form
                ]
            ]);
        }

        $qualityService = new DataQualityCheckService();
        $qualityService->runQualityChecks($report);

        return $report;
    }

    private function generateBloodShortageDraft(string $facilityId, string $periodStart, string $periodEnd, ReportingRule $rule): ?PublicHealthReport
    {
        // Exclude expired, quarantined, or unsafe units
        $bloodShortages = BloodInventory::where('facility_id', $facilityId)
            ->where('available_units', '<', 10)
            ->where('is_expired', false)
            ->where('is_quarantined', false)
            ->where('is_unsafe', false)
            ->get();

        if ($bloodShortages->isEmpty()) {
            return null;
        }

        $report = PublicHealthReport::create([
            'report_type_id' => $rule->report_type_id,
            'facility_id' => $facilityId,
            'reporting_period_start' => $periodStart,
            'reporting_period_end' => $periodEnd,
            'status' => 'draft',
            'sensitivity_level' => 'aggregate',
            'data_classification' => 'sensitive',
            'generated_by_system' => true,
            'data_quality_score' => 100,
            'requires_review' => $rule->requires_review
        ]);

        foreach ($bloodShortages as $item) {
            ReportItem::create([
                'report_id' => $report->id,
                'indicator_code' => 'BLOOD_LOW_' . strtoupper($item->blood_group) . '_' . strtoupper($item->component),
                'indicator_name' => 'Low blood group ' . $item->blood_group . ' (' . $item->component . ')',
                'value' => $item->available_units
            ]);
        }

        $qualityService = new DataQualityCheckService();
        $qualityService->runQualityChecks($report);

        return $report;
    }
}
