<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdrReportResource;
use App\Models\AdrReport;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdrReportController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'            => ['required', 'uuid', 'exists:patients,id'],
            'reporter_id'           => ['required', 'uuid', 'exists:users,id'],
            'suspect_drug'          => ['required', 'string', 'max:255'],
            'suspect_drug_batch'    => ['nullable', 'string', 'max:100'],
            'suspect_drug_dose'     => ['nullable', 'string', 'max:100'],
            'suspect_drug_route'    => ['nullable', 'string', 'max:50'],
            'indication_for_use'    => ['nullable', 'string', 'max:500'],
            'reaction_onset_date'   => ['nullable', 'date'],
            'reaction_description'  => ['required', 'string'],
            'severity'              => ['required', 'in:mild,moderate,severe,life_threatening,fatal'],
            'causality_assessment'  => ['nullable', 'string', 'max:30'],
            'drug_stopped'          => ['nullable', 'boolean'],
            'rechallenged'          => ['nullable', 'boolean'],
            'reaction_resolved'     => ['nullable', 'boolean'],
            'outcome'               => ['nullable', 'in:recovered,recovering,not_recovered,recovered_with_sequelae,fatal,unknown'],
            'concomitant_drugs'     => ['nullable', 'string'],
            'reporter_profession'   => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string'],
        ]);

        $report = AdrReport::create(array_merge($validated, ['facility_id' => $facilityId]));

        try {
            $this->issuance->issueFromModel(
                'ADR',
                'Adverse Drug Reaction Report',
                [
                    'adr_report_id'        => $report->id,
                    'patient_id'           => $validated['patient_id'],
                    'suspect_drug'         => $validated['suspect_drug'],
                    'reaction_description' => $validated['reaction_description'],
                    'severity'             => $validated['severity'],
                    'reaction_onset_date'  => $validated['reaction_onset_date'] ?? null,
                    'outcome'              => $validated['outcome'] ?? null,
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['reporter_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => AdrReportResource::make($report)], 201);
    }

    public function show(Request $request, AdrReport $report): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $report->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => AdrReportResource::make($report)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = AdrReport::where('facility_id', $facilityId)
            ->orderByDesc('created_at');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => AdrReportResource::collection($query->get())]);
    }
}
