<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpecialtyDiagnosticReportResource;
use App\Models\SpecialtyDiagnosticReport;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpecialtyDiagnosticsController extends Controller
{
    private const DOC_MAP = [
        'echo'       => ['ECHO', 'Echocardiogram Report'],
        'ecg'        => ['ECG',  'ECG / 12-Lead Report'],
        'endoscopy'  => ['ENDO', 'Endoscopy Report'],
    ];

    public function __construct(private readonly DocumentIssuanceService $issuance) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'              => ['required', 'uuid', 'exists:patients,id'],
            'reporting_clinician_id'  => ['required', 'uuid', 'exists:users,id'],
            'report_type'             => ['required', 'in:echo,ecg,endoscopy'],
            'study_date'              => ['required', 'date', 'before_or_equal:today'],
            'indication'              => ['nullable', 'string', 'max:500'],
            'findings'                => ['required', 'string'],
            'impression'              => ['nullable', 'string', 'max:2000'],
            'recommendation'          => ['nullable', 'string', 'max:1000'],
            'measurements'            => ['nullable', 'array'],
            'image_refs'              => ['nullable', 'string', 'max:1000'],
        ]);

        $report = SpecialtyDiagnosticReport::create(array_merge($validated, [
            'facility_id' => $facilityId,
            'status'      => 'draft',
        ]));

        [$docCode, $docTitle] = self::DOC_MAP[$validated['report_type']];

        try {
            $this->issuance->issueFromModel(
                $docCode,
                $docTitle,
                [
                    'report_id'    => $report->id,
                    'patient_id'   => $validated['patient_id'],
                    'report_type'  => $validated['report_type'],
                    'study_date'   => $validated['study_date'],
                    'findings'     => $validated['findings'],
                    'impression'   => $validated['impression'] ?? null,
                ],
                $facilityId,
                $validated['patient_id'],
                null,
                $validated['reporting_clinician_id'],
            );
        } catch (\Throwable) {}

        return response()->json(['data' => SpecialtyDiagnosticReportResource::make($report)], 201);
    }

    public function show(Request $request, SpecialtyDiagnosticReport $report): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId || $report->facility_id !== $facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }
        return response()->json(['data' => SpecialtyDiagnosticReportResource::make($report)]);
    }

    public function index(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $query = SpecialtyDiagnosticReport::where('facility_id', $facilityId)
            ->orderByDesc('study_date');

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->query('report_type'));
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        return response()->json(['data' => SpecialtyDiagnosticReportResource::collection($query->get())]);
    }
}
