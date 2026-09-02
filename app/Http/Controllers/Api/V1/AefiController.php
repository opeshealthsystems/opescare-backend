<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AefiReportResource;
use App\Models\AefiReport;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AefiController extends Controller
{
    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $validated = $request->validate([
            'patient_id'              => 'required|uuid',
            'immunization_record_id'  => 'nullable|uuid',
            'reporter_id'             => 'required|uuid',
            'report_date'             => 'required|date',
            'onset_date'              => 'required|date',
            'severity'                => 'required|string|in:mild,moderate,severe,life_threatening,fatal|max:20',
            'event_description'       => 'required|string',
            'vaccine_name'            => 'required|string|max:200',
            'vaccine_lot'             => 'nullable|string|max:100',
            'batch_number'            => 'nullable|string|max:100',
            'causality_assessment'    => 'nullable|string|in:consistent,inconsistent,indeterminate,unclassifiable|max:30',
            'outcome'                 => 'nullable|string|in:recovered,recovering,not_recovered,sequelae,fatal,unknown|max:30',
            'action_taken'            => 'nullable|string',
            'reported_to_authorities' => 'nullable|boolean',
        ]);

        $record = AefiReport::create(array_merge($validated, ['facility_id' => $facilityId]));

        try {
            $this->issuance->issueFromModel(
                'AEF',
                'AEFI Report — ' . $validated['vaccine_name'],
                ['aefi_report_id' => $record->id, 'patient_id' => $validated['patient_id']],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['reporter_id'],
            );
        } catch (\Throwable $e) {
            // Non-fatal
        }

        return response()->json(['data' => AefiReportResource::make($record)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (! $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $records = AefiReport::where('facility_id', $facilityId)
            ->with(['patient', 'reporter'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $records]);
    }
}
