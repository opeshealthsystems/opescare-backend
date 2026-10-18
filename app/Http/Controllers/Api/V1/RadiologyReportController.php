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

/**
 * Imported from the platform (docs/PLATFORM_IMPORTS.md, group A). Routes live in
 * routes/imports_files.php (routes/api.php is sealed).
 *
 * Finder adaptation: every {id} resolves through the calling client's facility
 * (request attributes, set by VerifyIntegrationClient) — another facility's
 * report is a 404 — and `pending` answers only for its own facility.
 */
class RadiologyReportController extends Controller {
    /** A report this integration client's facility owns, or 404. */
    private function scoped(Request $request, string $id): RadiologyReport {
        $facilityId = $request->attributes->get('facility_id');
        abort_unless($facilityId && \Illuminate\Support\Str::isUuid($id), 404);
        return RadiologyReport::where('facility_id', $facilityId)->where('id', $id)->firstOrFail();
    }

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
            'patient_id'          => ['required','uuid'], // existence is answered by the consent gate below, as FHIR does
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
        // The ordering clinician must actually be posted at the calling
        // client's facility — a client cannot attribute an order to anyone.
        $orderedBy = \App\Models\User::find($validated['ordered_by']);
        $postings  = $orderedBy ? app(\App\Services\Portal\FacilityMembershipService::class)->facilityIdsFor($orderedBy) : [];
        if (! is_array($postings) || ! in_array($facilityId, $postings, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ordered_by' => __('api.radiology_ordered_by_not_facility_staff'),
            ]);
        }

        // Same gate as FHIR ingest writes: no active consent grant from this
        // patient to the client's facility, no write — and the same
        // OperationOutcome refusal FHIR returns (a 404 for both "no such
        // patient" and "no consent", so existence stays private).
        $patient = \App\Models\Patient::withoutGlobalScopes()->find($validated['patient_id']);
        try {
            if (! $patient) {
                throw \App\Exceptions\FhirIngestRefusal::make(
                    \App\Support\Fhir\FhirOutcome::unknownPatient(),
                    'patient_unresolvable',
                    'patient uuid did not resolve'
                );
            }
            app(\App\Services\Fhir\FhirIngestGuard::class)->requireConsent($patient, $facilityId);
        } catch (\App\Exceptions\FhirIngestRefusal $refusal) {
            \Illuminate\Support\Facades\Log::warning('radiology_report_refused', [
                'client_id'    => $request->attributes->get('integration_client_id'),
                'facility_id'  => $facilityId,
                'refusal_code' => $refusal->refusalCode,
            ]);
            return $refusal->response;
        }

        $validated['facility_id'] = $facilityId;
        $report = $this->service->createDraft($validated);
        return response()->json(['data' => RadiologyReportResource::make($report)], Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id): JsonResponse {
        $report = $this->scoped($request, $id)->load(['patient','orderedBy','reportedBy','facility']);
        return response()->json(['data' => RadiologyReportResource::make($report)]);
    }

    public function finalize(Request $request, string $id): JsonResponse {
        $this->scoped($request, $id);
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
        $this->scoped($request, $id);
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
        $this->scoped($request, $id);
        $validated = $request->validate([
            'user_ids'   => ['required','array','min:1'],
            'user_ids.*' => ['required','uuid','exists:users,id'],
        ]);
        $report = $this->service->distribute($id, $validated['user_ids']);
        return response()->json(['data' => RadiologyReportResource::make($report)]);
    }

    public function pending(Request $request, string $facilityId): JsonResponse {
        // The URL names a facility; only the caller's own is answered.
        abort_unless($request->attributes->get('facility_id') === $facilityId, 404);
        $reports = $this->service->getPendingForFacility($facilityId);
        return response()->json(['data' => RadiologyReportResource::collection($reports)]);
    }
}
