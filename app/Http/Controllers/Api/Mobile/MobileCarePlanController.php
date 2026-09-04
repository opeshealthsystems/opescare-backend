<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CarePlan;
use App\Services\Clinical\CarePlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile Patient API — Care plans (read-only).
 *
 * Care plans are PHI. Both endpoints are scoped to the patient resolved by the
 * auth.mobile middleware (`$request->attributes->get('patient_id')`); the
 * caller-supplied plan id is never trusted on its own.
 */
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

    /** GET /api/mobile/care-plans/{id} — the patient's own plan only */
    public function show(Request $request, string $id): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        if (! $patientId) {
            return response()->json(['message' => __('api.no_patient_record')], 404);
        }

        // Ownership gate. CarePlanService::getSummary() resolves the plan by id
        // alone, so without this check any authenticated patient could read
        // another patient's plan. 404 (not 403) so the response does not
        // confirm that an unrelated plan id exists.
        CarePlan::where('id', $id)
            ->where('patient_id', $patientId)
            ->firstOrFail(['id']);

        $summary = $this->service->getSummary($id);
        return response()->json(['data' => $summary]);
    }
}
