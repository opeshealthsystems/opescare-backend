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

    /**
     * Finder: the rows of an export, rendered on demand and never stored
     * (same rule as patient documents). Nothing here carries patient or
     * clinical content: no patient_id, no before/after state, no free-text
     * reason, no evidence payloads — only who/what/when metadata or counts.
     *
     * @return list<list<string|int|null>> header row first
     */
    public const EXPORT_TYPES = ['audit_aggregate', 'audit_log', 'breach_reports', 'suspicious_flags', 'access_reviews'];

    public function rows(string $exportType, ?string $from = null, ?string $to = null): array
    {
        $range = function ($q, string $col = 'created_at') use ($from, $to) {
            if ($from) {
                $q->where($col, '>=', $from);
            }
            if ($to) {
                $q->where($col, '<=', $to . ' 23:59:59');
            }

            return $q;
        };

        $db = \Illuminate\Support\Facades\DB::class;

        return match ($exportType) {
            'audit_aggregate' => array_merge([['day', 'action_type', 'resource_type', 'events']],
                $range($db::table('audit_events'))
                    ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') AS day, action_type, resource_type, COUNT(*) AS events")
                    ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM-DD'), action_type, resource_type")
                    ->orderBy('day')->orderBy('action_type')
                    ->get()->map(fn ($r) => [$r->day, $r->action_type, $r->resource_type, (int) $r->events])->all()),
            'audit_log' => array_merge([['created_at', 'actor_id', 'actor_role', 'facility_id', 'action_type', 'resource_type', 'emergency_override']],
                $range($db::table('audit_events'))
                    ->orderBy('created_at')->limit(50000)
                    ->get(['created_at', 'actor_id', 'actor_role', 'facility_id', 'action_type', 'resource_type', 'emergency_override'])
                    ->map(fn ($r) => [$r->created_at, $r->actor_id, $r->actor_role, $r->facility_id, $r->action_type, $r->resource_type, $r->emergency_override ? 1 : 0])->all()),
            'breach_reports' => array_merge([['discovered_at', 'breach_type', 'severity', 'status', 'contained_at', 'reported_to_authority_at', 'estimated_affected_records']],
                $range($db::table('breach_reports'), 'discovered_at')->orderBy('discovered_at')
                    ->get()->map(fn ($r) => [$r->discovered_at, $r->breach_type, $r->severity, $r->status, $r->contained_at, $r->reported_to_authority_at, $r->estimated_affected_records])->all()),
            'suspicious_flags' => array_merge([['created_at', 'user_id', 'flag_type', 'severity', 'status', 'reviewed_by', 'reviewed_at']],
                $range($db::table('suspicious_access_flags'))->orderBy('created_at')
                    ->get()->map(fn ($r) => [$r->created_at, $r->user_id, $r->flag_type, $r->severity, $r->status, $r->reviewed_by, $r->reviewed_at])->all()),
            'access_reviews' => array_merge([['created_at', 'reviewed_subject_id', 'reviewed_by', 'status', 'outcome', 'completed_at']],
                $range($db::table('access_reviews'))->orderBy('created_at')
                    ->get()->map(fn ($r) => [$r->created_at, $r->reviewed_subject_id, $r->reviewed_by, $r->status, $r->outcome, $r->completed_at])->all()),
            default => throw new \InvalidArgumentException('COMPLIANCE_EXPORT_TYPE_UNKNOWN'),
        };
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
