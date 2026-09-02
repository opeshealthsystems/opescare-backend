<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ProviderPerformanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderPerformanceController extends Controller
{
    public function __construct(private readonly ProviderPerformanceService $service) {}

    /**
     * GET /api/v1/reports/providers/{providerId}/performance
     *
     * Returns a performance summary for the given provider.
     * Optional query params: from (date), to (date). Defaults to last 30 days.
     */
    public function summary(Request $request, string $providerId): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : Carbon::now()->endOfDay();

        $data = $this->service->getSummary($providerId, $from, $to);

        return response()->json([
            'data'        => $data,
            'provider_id' => $providerId,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
        ]);
    }

    /**
     * GET /api/v1/reports/providers/{providerId}/top-diagnoses
     *
     * Returns the top N diagnoses recorded by this provider.
     * Optional query param: limit (1–50, default 10).
     */
    public function topDiagnoses(Request $request, string $providerId): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $data = $this->service->getTopDiagnoses(
            $providerId,
            (int) ($validated['limit'] ?? 10)
        );

        return response()->json([
            'data'        => $data,
            'provider_id' => $providerId,
        ]);
    }

    /**
     * GET /api/v1/reports/providers/facility/{facilityId}/performance
     *
     * Returns per-provider performance summaries for all providers
     * who had activity at the given facility in the date range.
     * The route {facilityId} param is cross-checked against the middleware-resolved
     * facility_id to prevent cross-facility IDOR.
     */
    public function facilitySummary(Request $request, string $facilityId): JsonResponse
    {
        $middlewareFacilityId = $request->attributes->get('facility_id');
        if (!$middlewareFacilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        // Cross-check route param against middleware-resolved facility
        if ($facilityId !== $middlewareFacilityId) {
            return response()->json(['message' => __('api.access_denied_facility_to'), 'code' => 'FACILITY_ACCESS_DENIED'], 403);
        }

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : Carbon::now()->endOfDay();

        $data = $this->service->getFacilitySummary($middlewareFacilityId, $from, $to);

        return response()->json([
            'data'        => $data,
            'facility_id' => $middlewareFacilityId,
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
        ]);
    }
}
