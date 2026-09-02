<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OccupationalHealthAssessmentResource;
use App\Models\OccupationalHealthAssessment;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OccupationalHealthController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $validated = $request->validate([
            'patient_id'        => 'required|uuid',
            'examiner_id'       => 'required|uuid',
            'assessment_date'   => 'required|date',
            'assessment_type'   => 'required|string|in:pre_employment,periodic,post_incident,return_to_work,exit|max:30',
            'job_title'         => 'nullable|string|max:150',
            'employer'          => 'nullable|string|max:200',
            'exposure_history'  => 'nullable|array',
            'clinical_findings' => 'nullable|string',
            'fitness_conclusion' => 'required|string|in:fit,fit_with_restrictions,temporarily_unfit,permanently_unfit|max:30',
            'restrictions'      => 'nullable|string',
            'next_review_date'  => 'nullable|date',
        ]);

        $record = OccupationalHealthAssessment::create(array_merge($validated, ['facility_id' => $facilityId]));

        try {
            $this->issuance->issueFromModel(
                'OHA',
                'Occupational Health Assessment — ' . $validated['assessment_type'],
                ['assessment_id' => $record->id, 'patient_id' => $validated['patient_id']],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['examiner_id'],
            );
        } catch (\Throwable $e) {
            // Non-fatal
        }

        return response()->json(['data' => OccupationalHealthAssessmentResource::make($record)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $records = OccupationalHealthAssessment::where('facility_id', $facilityId)
            ->with(['patient', 'examiner'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => OccupationalHealthAssessmentResource::collection($records)]);
    }
}
