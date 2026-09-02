<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabPathReportResource;
use App\Models\LabPathReport;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabPathController extends Controller
{
    private const DOC_MAP = [
        'lab'               => ['LAB', 'Laboratory Result Report'],
        'pathology'         => ['PATH', 'Pathology Report'],
        'autopsy_pathology' => ['PMR',  'Post-Mortem Pathology Report'],
    ];

    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'      => ['required', 'uuid', 'exists:patients,id'],
            'reported_by'     => ['required', 'uuid', 'exists:users,id'],
            'report_type'     => ['required', 'in:lab,pathology,autopsy_pathology'],
            'collected_date'  => ['nullable', 'date'],
            'reported_date'   => ['required', 'date', 'before_or_equal:today'],
            'specimen_type'   => ['nullable', 'string', 'max:100'],
            'test_name'       => ['required', 'string', 'max:255'],
            'results'         => ['required', 'string'],
            'reference_range' => ['nullable', 'string', 'max:500'],
            'interpretation'  => ['nullable', 'string', 'max:1000'],
            'critical_value'  => ['nullable', 'boolean'],
        ]);

        $report = LabPathReport::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'preliminary',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['report_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                [
                    'report_id'      => $report->id,
                    'patient_id'     => $validated['patient_id'],
                    'report_type'    => $validated['report_type'],
                    'test_name'      => $validated['test_name'],
                    'reported_date'  => $validated['reported_date'],
                    'critical_value' => $validated['critical_value'] ?? false,
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['reported_by'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => LabPathReportResource::make($report)], 201);
    }

    public function show(Request $request, LabPathReport $report): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $report->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => LabPathReportResource::make($report)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = LabPathReport::where('facility_id', $facilityId)
            ->orderByDesc('reported_date');

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->query('report_type'));
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => LabPathReportResource::collection($query->get())]);
    }

    public function finalize(Request $request, LabPathReport $report): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $report->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $report->update(['status' => 'final']);
        return response()->json(['data' => LabPathReportResource::make($report)]);
    }
}
