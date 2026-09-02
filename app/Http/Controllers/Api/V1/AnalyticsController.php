<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MetricSnapshotResource;
use App\Modules\Analytics\Services\OperationalAnalyticsService;
use App\Modules\Analytics\Services\ProductAnalyticsService;
use App\Modules\Analytics\Services\ReportExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AnalyticsController — Analytics & Reporting API.
 *
 * PRIVACY: All analytics endpoints return aggregated data only.
 * No patient-level data may be included in analytics responses
 * unless the requester has explicit permission with audit log.
 *
 * Exports are generated asynchronously — use the export status endpoint
 * to poll for readiness before downloading.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private OperationalAnalyticsService $analytics,
        private ReportExportService         $exports,
        private ProductAnalyticsService     $product
    ) {}

    public function facilityDashboard(Request $request, string $facilityId): JsonResponse
    {
        $clientFacilityId = $request->attributes->get('facility_id');
        if ($clientFacilityId && $clientFacilityId !== $facilityId) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => __('api.analytics_access_denied'),
            ], 403);
        }

        return response()->json(
            $this->analytics->dashboardSnapshot($facilityId, $request->input('period', '30d'))
        );
    }

    public function appointmentStats(Request $request): JsonResponse
    {
        [$facilityId, $from, $to, $error] = $this->resolveStatsParams($request);
        if ($error) {
            return $error;
        }

        return response()->json([
            'facility_id' => $facilityId,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'data'        => $this->analytics->appointmentSummary($facilityId, $from, $to),
        ]);
    }

    public function queueStats(Request $request): JsonResponse
    {
        [$facilityId, $from, $to, $error] = $this->resolveStatsParams($request);
        if ($error) {
            return $error;
        }

        return response()->json([
            'facility_id' => $facilityId,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'data'        => $this->analytics->queueSummary($facilityId, $from, $to),
        ]);
    }

    public function billingStats(Request $request): JsonResponse
    {
        [$facilityId, $from, $to, $error] = $this->resolveStatsParams($request);
        if ($error) {
            return $error;
        }

        return response()->json([
            'facility_id' => $facilityId,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'data'        => $this->analytics->revenueSummary($facilityId, $from, $to),
        ]);
    }

    /**
     * Resolve facility + date range for the flat stats endpoints.
     * Returns [facilityId, from, to, errorResponse|null].
     */
    private function resolveStatsParams(Request $request): array
    {
        $request->validate([
            'facility_id' => ['nullable', 'uuid'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
        ]);

        $authorizedFacilityId = $request->attributes->get('facility_id');
        $requestedFacilityId  = $request->input('facility_id');

        if ($authorizedFacilityId && $requestedFacilityId && $requestedFacilityId !== $authorizedFacilityId) {
            return [null, null, null, response()->json([
                'error'   => 'forbidden',
                'message' => __('api.analytics_access_denied'),
            ], 403)];
        }

        $facilityId = $requestedFacilityId ?? $authorizedFacilityId;
        if (!$facilityId) {
            return [null, null, null, response()->json([
                'error'   => 'validation_error',
                'message' => __('api.facility_id_required'),
            ], 422)];
        }

        $from = Carbon::parse($request->input('from', Carbon::today()->subDays(29)->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->input('to', Carbon::today()->toDateString()))->endOfDay();

        return [$facilityId, $from, $to, null];
    }

    // ── Report Exports ─────────────────────────────────────────────────────

    public function requestExport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type'  => ['required', 'string'],
            'format'       => ['required', 'in:csv,xlsx,pdf,json'],
            'facility_id'  => ['nullable', 'uuid'],
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
        ]);

        $export = $this->exports->requestExport(
            $validated['report_type'],
            $validated['format'],
            $request->user()->id,
            $validated,
            $validated['facility_id'] ?? null
        );

        return response()->json(['export_id' => $export->id, 'status' => $export->status], 202);
    }

    public function exportStatus(string $exportId): JsonResponse
    {
        $export = \App\Models\ReportExport::findOrFail($exportId);
        return response()->json([
            'export_id'    => $export->id,
            'status'       => $export->status,
            'download_url' => $export->isExpired() ? null : $export->download_url,
        ]);
    }

    // ── KPI / Metric Snapshots ─────────────────────────────────────────────

    /**
     * Latest daily KPI snapshots for a facility.
     * Used by the KPI dashboard widget — one value per metric for the most recent date.
     *
     * ?category=clinical|financial|operational|quality  (optional filter)
     */
    public function kpiSnapshots(Request $request, string $facilityId): JsonResponse
    {
        $clientFacilityId = $request->attributes->get('facility_id');
        if ($clientFacilityId && $clientFacilityId !== $facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.access_denied_facility')], 403);
        }

        $snapshots = $this->product->latestDailySnapshots(
            $facilityId,
            $request->input('category')
        );

        return response()->json([
            'facility_id' => $facilityId,
            'data'        => MetricSnapshotResource::collection($snapshots),
        ]);
    }

    /**
     * Metric trend data for a specific metric over a date range.
     * Returns a map of date → value for charting.
     *
     * ?metric_slug=patient_registrations&from=2026-05-01&to=2026-06-07
     */
    public function metricTrend(Request $request, string $facilityId): JsonResponse
    {
        $validated = $request->validate([
            'metric_slug' => ['required', 'string'],
            'from'        => ['required', 'date'],
            'to'          => ['required', 'date', 'after_or_equal:from'],
        ]);

        $clientFacilityId = $request->attributes->get('facility_id');
        if ($clientFacilityId && $clientFacilityId !== $facilityId) {
            return response()->json(['error' => 'forbidden', 'message' => __('api.access_denied_facility')], 403);
        }

        $trend = $this->product->metricTrend(
            $facilityId,
            $validated['metric_slug'],
            Carbon::parse($validated['from']),
            Carbon::parse($validated['to'])
        );

        return response()->json([
            'facility_id' => $facilityId,
            'metric_slug' => $validated['metric_slug'],
            'from'        => $validated['from'],
            'to'          => $validated['to'],
            'data'        => $trend,
        ]);
    }

    /**
     * Platform-wide summary for super_admin / hospital_director dashboards.
     * Returns aggregate counts across all facilities for a given date.
     *
     * ?date=2026-06-07  (defaults to today)
     */
    public function platformSummary(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $date = Carbon::parse($request->input('date', now()->toDateString()));

        return response()->json(
            $this->product->platformSummary($date)
        );
    }
}
