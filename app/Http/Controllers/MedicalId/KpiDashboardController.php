<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\MetricDefinition;
use App\Models\MetricSnapshot;
use App\Modules\Analytics\Services\ProductAnalyticsService;
use App\Modules\Analytics\Services\ReportExportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * KPI Dashboard Portal Controller
 *
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group C).
 * Finder adaptations:
 *  - platform administrators only (/admin/kpi, RequirePlatformAdmin), so the
 *    dashboard is platform-wide: no facility is read from the session;
 *  - today's snapshots are computed once per day on first view, not on every
 *    request (the platform recomputed every facility on every page load);
 *  - the export is a synchronous CSV of aggregates via ReportExportService,
 *    audited, with no patient identifiers.
 */
class KpiDashboardController extends Controller
{
    public function __construct(
        private readonly ProductAnalyticsService $analytics,
    ) {}

    // ── KPI Summary Dashboard ─────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $facilityId = null; // platform-wide
        $category   = $request->input('category'); // volume|quality|efficiency|financial

        $this->analytics->seedCoreMetrics();

        if (! MetricSnapshot::where('period_granularity', 'daily')->whereDate('period_date', Carbon::today())->exists()) {
            $this->analytics->computeDailySnapshots($facilityId, Carbon::today());
        }

        $snapshots       = $this->analytics->latestDailySnapshots($facilityId, $category);
        $platformSummary = $this->analytics->platformSummary(Carbon::today());
        $categories      = MetricDefinition::active()->distinct('category')->pluck('category')->sort()->values();

        return view('portals.admin.kpi.index', compact(
            'snapshots', 'platformSummary', 'categories', 'category'
        ));
    }

    // ── Metric Trend ──────────────────────────────────────────────────────────

    public function trend(Request $request): View
    {
        $facilityId = null; // platform-wide
        $slug       = $request->input('metric', ProductAnalyticsService::METRIC_DAILY_VISITS);
        $period     = in_array($request->input('period'), ['7d', '30d', '90d']) ? $request->input('period') : '30d';

        $to   = Carbon::today();
        $from = match ($period) {
            '7d'  => $to->copy()->subDays(6),
            '90d' => $to->copy()->subDays(89),
            default => $to->copy()->subDays(29),
        };

        $trendData  = $this->analytics->metricTrend($facilityId, $slug, $from, $to);
        $definition = MetricDefinition::where('slug', $slug)->first();
        $allMetrics = MetricDefinition::active()->orderBy('category')->orderBy('name')->get();

        return view('portals.admin.kpi.trend', compact(
            'trendData', 'definition', 'allMetrics', 'slug', 'period', 'from', 'to'
        ));
    }

    // ── CSV Export (aggregates only) ──────────────────────────────────────────

    public function export(Request $request, ReportExportService $exports): Response
    {
        $validated = $request->validate([
            'metric_slugs'   => 'nullable|array',
            'metric_slugs.*' => 'string|max:100',
            'period_from'    => 'nullable|date',
            'period_to'      => 'nullable|date|after_or_equal:period_from',
        ]);

        $to   = Carbon::parse($validated['period_to'] ?? Carbon::today()->toDateString());
        $from = Carbon::parse($validated['period_from'] ?? $to->copy()->subDays(29)->toDateString());
        $slugs = array_values($validated['metric_slugs'] ?? []);

        $record = $exports->requestExport(
            reportType: 'kpi_daily_aggregate',
            format: 'csv',
            requestedBy: (string) auth()->id(),
            filters: ['period_from' => $from->toDateString(), 'period_to' => $to->toDateString(), 'metric_slugs' => $slugs],
        );

        $result = $exports->kpiCsv($from, $to, $slugs);
        $exports->markReady($record->id, 'inline:download', $result['rows']);

        $filename = sprintf('opescare-kpi-%s-%s.csv', $from->format('Ymd'), $to->format('Ymd'));

        return response($result['csv'], 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    // ── Recompute Snapshots ───────────────────────────────────────────────────

    public function recompute(Request $request): RedirectResponse
    {
        $date = Carbon::parse($request->input('date', Carbon::today()->toDateString()));

        $this->analytics->seedCoreMetrics();
        $this->analytics->computeDailySnapshots(null, $date);

        return back()->with('success', __('kpi.recomputed', ['date' => $date->toDateString()]));
    }
}
