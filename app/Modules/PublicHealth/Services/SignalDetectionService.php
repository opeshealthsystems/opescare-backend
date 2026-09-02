<?php

namespace App\Modules\PublicHealth\Services;

use App\Models\PublicHealthReport;
use App\Models\PublicHealthSignal;
use App\Models\PublicHealthBaseline;
use Illuminate\Support\Facades\DB;

class SignalDetectionService
{
    /**
     * Compute baseline and run outbreak cluster detection algorithms on aggregate counts.
     */
    public function detectSignals(string $facilityId, string $indicatorCode): ?PublicHealthSignal
    {
        // 1. Fetch historic weekly average baseline or seed a default baseline
        $baseline = PublicHealthBaseline::where('scope_type', 'facility')
            ->where('scope_id', $facilityId)
            ->where('indicator_code', $indicatorCode)
            ->first();

        if (!$baseline) {
            $baseline = PublicHealthBaseline::create([
                'scope_type' => 'facility',
                'scope_id' => $facilityId,
                'indicator_code' => $indicatorCode,
                'period_type' => 'weekly',
                'baseline_value' => 4.00, // Seed default weekly baseline count
                'calculated_at' => now()
            ]);
        }

        // 2. Query recent report aggregates for this facility and indicator in the last week
        $currentSum = DB::table('public_health_report_items')
            ->join('public_health_reports', 'public_health_reports.id', '=', 'public_health_report_items.report_id')
            ->where('public_health_reports.facility_id', $facilityId)
            ->where('public_health_report_items.indicator_code', $indicatorCode)
            ->where('public_health_reports.created_at', '>=', now()->subDays(7))
            ->sum('public_health_report_items.value');

        // 3. Compute percentage increase compared with baseline
        $baselineVal = (float) $baseline->baseline_value;
        $increasePct = 0.00;
        if ($baselineVal > 0.00) {
            $increasePct = (($currentSum - $baselineVal) / $baselineVal) * 100.00;
        }

        // 4. Trigger Outbreak Disease Signal if spike exceeds the 50% threshold
        if ($increasePct >= 50.00) {
            // Signal confidence level and severity scoring
            $confidence = 'medium';
            if ($increasePct >= 150.00) {
                $confidence = 'high';
            }
            $severity = 'low';
            if ($currentSum > 10) {
                $severity = 'medium';
            }
            if ($currentSum > 25) {
                $severity = 'critical';
            }

            return PublicHealthSignal::create([
                'signal_type' => $this->resolveSignalTypeFromIndicator($indicatorCode),
                'status' => 'new_signal',
                'scope_type' => 'facility',
                'scope_id' => $facilityId,
                'facility_id' => $facilityId,
                'indicator_code' => $indicatorCode,
                'baseline_value' => $baselineVal,
                'current_value' => $currentSum,
                'increase_percentage' => $increasePct,
                'confidence_level' => $confidence,
                'severity' => $severity,
                'detected_at' => now()
            ]);
        }

        return null;
    }

    private function resolveSignalTypeFromIndicator(string $indicatorCode): string
    {
        if (str_contains($indicatorCode, 'STOCKOUT')) {
            return 'medicine_stock_out_cluster';
        }
        if (str_contains($indicatorCode, 'BLOOD')) {
            return 'blood_shortage_cluster';
        }
        if (str_contains($indicatorCode, 'LAB_POSITIVE')) {
            return 'lab_positivity_spike';
        }
        return 'disease_cluster';
    }
}
