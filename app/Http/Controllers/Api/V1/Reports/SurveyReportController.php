<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Patient\SurveyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyReportController extends Controller
{
    public function __construct(private readonly SurveyService $service)
    {
    }

    /** GET /api/v1/reports/surveys/satisfaction?from=&to= */
    public function satisfaction(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $scores = $this->service->getSatisfactionScore(
            $facilityId,
            Carbon::parse($validated['from']),
            Carbon::parse($validated['to']),
        );

        return response()->json(['data' => $scores]);
    }
}
