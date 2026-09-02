<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\RevenueCycleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueCycleController extends Controller
{
    public function __construct(private readonly RevenueCycleService $service) {}

    public function summary(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to'   => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : Carbon::now()->startOfMonth();
        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : Carbon::now()->endOfDay();

        return response()->json(['data' => $this->service->getSummary($facilityId, $from, $to)]);
    }

    public function aging(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        return response()->json(['data' => $this->service->getAgingReport($facilityId)]);
    }

    public function denials(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to'   => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : Carbon::now()->subMonths(3)->startOfDay();
        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : Carbon::now()->endOfDay();

        return response()->json(['data' => $this->service->getDenialReasons($facilityId, $from, $to)]);
    }

    public function trend(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'months' => ['sometimes', 'integer', 'min:1', 'max:24'],
        ]);

        return response()->json([
            'data' => $this->service->getMonthlyTrend(
                $facilityId,
                (int) ($validated['months'] ?? 6)
            ),
        ]);
    }
}
