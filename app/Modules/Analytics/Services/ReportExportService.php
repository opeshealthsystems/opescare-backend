<?php

namespace App\Modules\Analytics\Services;

use App\Models\AuditEvent;
use App\Models\MetricDefinition;
use App\Models\MetricSnapshot;
use App\Models\ReportExport;
use Carbon\Carbon;

/**
 * ReportExportService — Manages report export generation and delivery.
 *
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group C). Finder
 * adaptations:
 *  - the platform wrote report_type / facility_id / filters, columns the
 *    report_exports table does not have; they are stored in `parameters`;
 *  - the audit row uses the Finder's audit_events shape (action_type, …);
 *  - kpiCsv() generates the KPI export synchronously (the platform only
 *    queued a record that no job ever filled).
 *
 * PHI constraint: exports must never contain unaggregated patient data.
 * kpiCsv() reads metric_snapshots only — counts, rates and averages — and
 * never a patient row, name or Health ID.
 */
class ReportExportService
{
    public function requestExport(
        string $reportType,
        string $format,
        string $requestedBy,
        array $filters = [],
        ?string $facilityId = null
    ): ReportExport {
        $export = ReportExport::create([
            'requested_by' => $requestedBy,
            'format'       => $format,
            'status'       => 'pending',
            'parameters'   => [
                'report_type' => $reportType,
                'facility_id' => $facilityId,
                'filters'     => $filters,
            ],
        ]);

        AuditEvent::create([
            'actor_id'      => $requestedBy,
            'actor_role'    => auth()->user()?->role?->name,
            'facility_id'   => $facilityId,
            'action_type'   => 'report_export.requested',
            'resource_type' => 'ReportExport',
            'resource_id'   => $export->id,
            'source_system' => 'portal',
            'ip_address'    => request()->ip(),
            'emergency_override' => false,
            'after_state'   => [
                'report_type' => $reportType,
                'format'      => $format,
            ],
            'created_at'    => now(),
        ]);

        return $export;
    }

    public function markReady(string $exportId, string $filePath, int $rowCount = 0): ReportExport
    {
        $export = ReportExport::findOrFail($exportId);
        $export->markReady($filePath, $rowCount);
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

    /**
     * Platform-wide KPI aggregates as CSV: one row per day per metric.
     * Counts/sums are summed across facilities; rates and averages are the
     * mean of the facilities that reported. No patient-level column exists.
     *
     * @return array{csv: string, rows: int}
     */
    public function kpiCsv(Carbon $from, Carbon $to, array $metricSlugs = []): array
    {
        $definitions = MetricDefinition::query()
            ->when($metricSlugs !== [], fn ($q) => $q->whereIn('slug', $metricSlugs))
            ->get()
            ->keyBy('id');

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['period_date', 'metric', 'category', 'unit', 'value', 'facilities_reporting']);
        $rows = 0;

        MetricSnapshot::query()
            ->whereIn('metric_definition_id', $definitions->keys())
            ->where('period_granularity', 'daily')
            ->whereBetween('period_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('period_date, metric_definition_id, SUM(value) as total, AVG(value) as mean, COUNT(value) as n')
            ->groupBy('period_date', 'metric_definition_id')
            ->orderBy('period_date')
            ->get()
            ->each(function ($r) use ($definitions, $out, &$rows) {
                $def = $definitions->get($r->metric_definition_id);
                $additive = in_array($def->aggregation, ['count', 'sum'], true);
                fputcsv($out, [
                    Carbon::parse($r->period_date)->toDateString(),
                    $def->slug,
                    $def->category,
                    $def->unit,
                    $r->n > 0 ? round((float) ($additive ? $r->total : $r->mean), 2) : '',
                    (int) $r->n,
                ]);
                $rows++;
            });

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return ['csv' => $csv, 'rows' => $rows];
    }
}
