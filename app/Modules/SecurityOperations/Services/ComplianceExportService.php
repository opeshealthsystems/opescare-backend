<?php

namespace App\Modules\SecurityOperations\Services;

use App\Models\AuditExport;
use App\Models\AuditEvent;

/**
 * ComplianceExportService — Generates and packages audit/compliance export files.
 *
 * Exports are generated asynchronously (dispatched as jobs) and stored
 * as AuditExport records. Exports expire after 24 hours for security.
 *
 * Supported export types:
 *  - audit_log: Full audit event log for a time range
 *  - access_review: Summary of access review outcomes
 *  - breach_reports: Breach report summary (for regulators)
 *  - suspicious_flags: Suspicious access flag report
 *  - permission_changes: Permission grant/revoke history
 */
class ComplianceExportService
{
    public function requestExport(
        string $exportType,
        string $requestedBy,
        array $filters = [],
        string $format = 'csv'
    ): AuditExport {
        $export = AuditExport::create([
            'export_type'  => $exportType,
            'requested_by' => $requestedBy,
            'filters'      => $filters,
            'format'       => $format,
            'status'       => 'pending',
        ]);

        AuditEvent::create([
            'actor_id'      => $requestedBy,
            'action_type'   => 'create',
            'resource_type' => 'compliance_export',
            'resource_id'   => $export->id,
            'reason'        => 'Compliance export requested. Type: ' . $exportType . ', Format: ' . $format,
        ]);

        // In production: dispatch(new GenerateComplianceExportJob($export->id));

        return $export;
    }

    public function markReady(string $exportId, string $filePath): AuditExport
    {
        $export = AuditExport::findOrFail($exportId);
        $export->markReady($filePath);
        return $export->fresh();
    }

    public function markFailed(string $exportId, string $errorMessage): AuditExport
    {
        $export = AuditExport::findOrFail($exportId);
        $export->update(['status' => 'failed']);
        return $export->fresh();
    }

    public function getReadyExportsFor(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return AuditExport::ready()
            ->where('requested_by', $userId)
            ->orderByDesc('created_at')
            ->get();
    }
}
