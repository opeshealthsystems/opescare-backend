<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RadiologyReportResource;
use App\Models\RadiologyReport;
use App\Services\Documents\DocumentIssuanceService;
use App\Services\Lab\RadiologyReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class RadiologyReportController extends Controller {
    public function __construct(
        private readonly RadiologyReportService $service,
        private readonly DocumentIssuanceService $issuance
    ) {}

    public function store(Request $request): JsonResponse {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'          => ['required','uuid','exists:patients,id'],
            'imaging_order_id'    => ['sometimes','nullable','uuid'],
            'ordered_by'          => ['required','uuid','exists:users,id'],
            'reported_by'         => ['required','uuid','exists:users,id'],
            'modality'            => ['required','in:xray,ct,mri,ultrasound,echo,nuclear,pet,other'],
            'body_part'           => ['required','string','max:150'],
            'study_date'          => ['required','date'],
            'clinical_indication' => ['required','string'],
            'findings'            => ['required','string'],
            'impression'          => ['required','string'],
            'recommendation'      => ['sometimes','nullable','string'],
        ]);
        $validated['facility_id'] = $facilityId;
        $report = $this->service->createDraft($validated);
        return response()->json(['data' => RadiologyReportResource::make($report)], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse {
        $report = \App\Models\RadiologyReport::with(['patient','orderedBy','reportedBy','facility'])->findOrFail($id);
        return response()->json(['data' => RadiologyReportResource::make($report)]);
    }

    public function finalize(Request $request, string $id): JsonResponse {
        $facilityId = $request->attributes->get('facility_id');

        $validated = $request->validate([
            'radiologist_id' => ['required','uuid','exists:users,id'],
        ]);
        $report = $this->service->finalize($id, $validated['radiologist_id']);

        if ($facilityId) {
            try {
                $raw = is_array($report) ? $report : $report->toArray();
                $this->issuance->issueFromModel(
                    'RAD',
                    'Radiology Report — ' . ($raw['modality'] ?? '') . ' ' . ($raw['body_part'] ?? ''),
                    ['report_id' => $id, 'patient_id' => $raw['patient_id'] ?? null, 'modality' => $raw['modality'] ?? null, 'body_part' => $raw['body_part'] ?? null, 'findings' => $raw['findings'] ?? null, 'impression' => $raw['impression'] ?? null, 'study_date' => $raw['study_date'] ?? null],
                    $facilityId,
                    $raw['patient_id'] ?? null,
                    null,
                    $validated['radiologist_id']
                );
            } catch (\Throwable) {}
        }

        return response()->json(['data' => RadiologyReportResource::make($report)]);
    }

    public function amend(Request $request, string $id): JsonResponse {
        $validated = $request->validate([
            'reason'              => ['required','string','max:1000'],
            'findings'            => ['sometimes','string'],
            'impression'          => ['sometimes','string'],
            'recommendation'      => ['sometimes','nullable','string'],
            'clinical_indication' => ['sometimes','string'],
        ]);
        $reason  = $validated['reason'];
        $changes = Arr::except($validated, ['reason']);
        $report  = $this->service->amend($id, $reason, $changes);
        return response()->json(['data' => RadiologyReportResource::make($report)]);
    }

    public function distribute(Request $request, string $id): JsonResponse {
        $validated = $request->validate([
            'user_ids'   => ['required','array','min:1'],
            'user_ids.*' => ['required','uuid','exists:users,id'],
        ]);
        $report = $this->service->distribute($id, $validated['user_ids']);
        return response()->json(['data' => RadiologyReportResource::make($report)]);
    }

    public function pending(string $facilityId): JsonResponse {
        $reports = $this->service->getPendingForFacility($facilityId);
        return response()->json(['data' => RadiologyReportResource::collection($reports)]);
    }
}
