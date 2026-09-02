<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientSurveyResource;
use App\Models\PatientSurvey;
use App\Services\Patient\SurveyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileSurveyController extends Controller
{
    public function __construct(private readonly SurveyService $service)
    {
    }

    /** GET /api/mobile/surveys — pending surveys for the authenticated patient */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');
        $surveys   = PatientSurvey::where('patient_id', $patientId)
            ->where('status', 'sent')
            ->with('responses')
            ->get();

        return response()->json(['data' => PatientSurveyResource::collection($surveys)]);
    }

    /** GET /api/mobile/surveys/{id} — survey with questions */
    public function show(string $id): JsonResponse
    {
        $survey   = PatientSurvey::findOrFail($id);
        $template = $this->service->getTemplate($survey->template_key);

        return response()->json([
            'data'     => PatientSurveyResource::make($survey),
            'template' => $template,
        ]);
    }

    /** POST /api/mobile/surveys/{id}/submit */
    public function submit(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'responses' => 'required|array',
        ]);

        $survey = $this->service->submitResponse($id, $validated['responses']);
        return response()->json(['data' => $survey]);
    }
}
