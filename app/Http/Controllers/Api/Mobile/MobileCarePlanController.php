<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\Clinical\CarePlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileCarePlanController extends Controller
{
    public function __construct(private readonly CarePlanService $service)
    {
    }

    /** GET /api/mobile/care-plans — patient's own active plans */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        if (! $patientId) {
            return response()->json(['message' => __('api.no_patient_record')], 404);
        }

        $plans = $this->service->getActivePlansForPatient($patientId);
        return response()->json(['data' => $plans]);
    }

    /** GET /api/mobile/care-plans/{id} */
    public function show(string $id): JsonResponse
    {
        $summary = $this->service->getSummary($id);
        return response()->json(['data' => $summary]);
    }
}
