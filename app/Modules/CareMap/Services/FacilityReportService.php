<?php

namespace App\Modules\CareMap\Services;

use App\Models\FacilityReport;
use Illuminate\Support\Facades\DB;

class FacilityReportService
{
    /**
     * Submit a correction or wrong info report
     */
    public function submitReport(array $data)
    {
        return FacilityReport::create([
            'facility_id' => $data['facility_id'],
            'reported_by_user_id' => $data['reported_by_user_id'] ?? null,
            'report_type' => $data['report_type'],
            'description' => $data['description'] ?? null,
            'evidence_path' => $data['evidence_path'] ?? null,
            'status' => 'new',
        ]);
    }

    /**
     * Resolve a correction or wrong info report
     */
    public function resolveReport($reportId, $adminId, $resolutionNotes, $status = 'resolved')
    {
        $report = FacilityReport::findOrFail($reportId);
        
        $report->update([
            'status' => $status,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'resolution_notes' => $resolutionNotes,
        ]);

        return $report;
    }
}
