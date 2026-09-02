<?php

namespace App\Modules\Analytics\Services;

use App\Models\ReportExport;
use App\Models\AuditEvent;

/**
 * ReportExportService — Manages async report export generation and delivery.
 *
 * Exports are generated in the background and stored as ReportExport records.
 * Each export has a TTL (default 24 hours) after which the file is purged.
 *
 * Supported formats: csv, xlsx, pdf, json
 * Supported report types: patient_summary, billing_summary, lab_stats,
 *   appointment_stats, queue_performance, staff_activity, public_health_aggregate
 *
 * PHI constraint: exports must never contain unaggregated patient data
 * unless the requester has explicit download permission and the export is audited.
 */
class ReportExportService
{
    public function requestExport(
        string $reportType,
        string $format,
        string $requestedBy,
        array $filters = [],
        string $facilityId = null
    ): ReportExport {
        $export = ReportExport::create([
            'report_type'  => $reportType,
            'format'       => $format,
            'requested_by' => $requestedBy,
            'facility_id'  => $facilityId,
            'filters'      => $filters,
            'status'       => 'pending',
        ]);

        AuditEvent::create([
            'actor_id'    => $requestedBy,
            'action'      => 'report_export.requested',
            'module'      => 'analytics',
            'facility_id' => $facilityId,
            'metadata'    => [
                'export_id'   => $export->id,
                'report_type' => $reportType,
                'format'      => $format,
            ],
        ]);

        // In production: dispatch(new GenerateReportExportJob($export->id));

        return $export;
    }

    public function markReady(string $exportId, string $filePath): ReportExport
    {
        $export = ReportExport::findOrFail($exportId);
        $export->markReady($filePath);
        return $export->fresh();
    }

    public function markFailed(string $exportId, string $error): ReportExport
    {
        $export = ReportExport::findOrFail($exportId);
        $export->markFailed($error);
        return $export->fresh();
    }

    public function getReadyExportsFor(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return ReportExport::where('requested_by', $userId)
            ->where('status', 'ready')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();
    }
}
