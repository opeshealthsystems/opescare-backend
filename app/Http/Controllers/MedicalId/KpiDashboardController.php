<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\KpiExport;
use App\Models\MetricDefinition;
use App\Models\MetricSnapshot;
use App\Modules\Analytics\Services\ProductAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * KPI Dashboard Portal Controller
 *
 * Provides the admin/director portal UI for KPI snapshots, trends,
 * and metric export requests.
 *
 * Roles: facility_admin, hospital_director, super_admin
 */
class KpiDashboardController extends Controller
{
    public function __construct(
        private readonly ProductAnalyticsService $analytics,
    ) {}

    // ── KPI Summary Dashboard ─────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();
        $category   = $request->input('category'); // volume|quality|efficiency|financial|safety

        // Ensure core metric definitions exist
        $this->analytics->seedCoreMetrics();

        // Compute today's snapshots on-the-fly for portal demo
        $this->analytics->computeDailySnapshots($facilityId, Carbon::today());

        $snapshots      = $this->analytics->latestDailySnapshots($facilityId, $category);
        $platformSummary= $this->analytics->platformSummary(Carbon::today());
        $categories     = MetricDefinition::active()->distinct('category')->pluck('category')->sort()->values();
        $recentExports  = KpiExport::query()
            ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
            ->orderByDesc('requested_at')
            ->limit(5)
            ->get();

        return view('portals.admin.kpi.index', compact(
            'snapshots', 'platformSummary', 'categories', 'category', 'recentExports'
        ));
    }

    // ── Metric Trend ──────────────────────────────────────────────────────────

    public function trend(Request $request): View
    {
        $facilityId = $this->facilityId();
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

    // ── Export Request ────────────────────────────────────────────────────────

    public function requestExport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'metric_slugs'  => 'required|array|min:1',
            'metric_slugs.*'=> 'string',
            'period_from'   => 'required|date',
            'period_to'     => 'required|date|after_or_equal:period_from',
            'export_type'   => 'required|in:csv,json',
        ]);

        $facilityId = $this->facilityId();

        $export = $this->analytics->requestExport(
            requestedBy: $this->demoActorId(),
            metricSlugs: $validated['metric_slugs'],
            from: Carbon::parse($validated['period_from']),
            to: Carbon::parse($validated['period_to']),
            exportType: $validated['export_type'],
            facilityId: $facilityId,
        );

        return redirect()
            ->route('portals.admin.kpi.index')
            ->with('success', __('flash.kpi_export_submitted', ['id' => $export->id]));
    }

    // ── Recompute Snapshots ───────────────────────────────────────────────────

    public function recompute(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();
        $date       = Carbon::parse($request->input('date', Carbon::today()->toDateString()));

        $this->analytics->seedCoreMetrics();
        $this->analytics->computeDailySnapshots($facilityId, $date);

        return back()->with('success', __('flash.kpi_snapshots_recomputed', ['date' => $date->toDateString()]));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function facilityId(): ?string
    {
        return session('active_facility_id') ?? auth()->user()?->primary_facility_id ?? null;
    }

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-admin';
    }
}
