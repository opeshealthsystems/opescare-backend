<?php

namespace App\Http\Controllers\Api\Mobile\Lab;

use App\Http\Resources\LabDesk\LabDeskRequestResource;
use App\Models\LabTestRequest;
use App\Services\Lab\LabDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Mobile Provider API — the LAB DESK's answer to patient test requests.
 *
 *   GET  /api/mobile/lab/requests               requests submitted to this facility (?status=)
 *   POST /api/mobile/lab/requests/{id}/approve  raises the LabOrder, tells the patient
 *   POST /api/mobile/lab/requests/{id}/decline  {reason} — tells the patient why
 *
 * Gate: `capability:lab_results`. A request is a clinical event: a decision
 * is final (409 already_decided on a second one) and a decline needs a reason
 * the patient will read.
 */
class LabRequestController extends LabDeskBaseController
{
    public function requests(Request $request): JsonResponse
    {
        $this->applyLocale($request);

        $facilityId = $this->facilityId($request);
        if ($facilityId === null) {
            return $this->noFacility();
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(LabDeskService::REQUEST_STATUSES)],
        ]);

        $status   = $validated['status'] ?? 'submitted';
        $requests = $this->desk->requests($facilityId, $status);

        // One list-level row; the per-patient rows belong to approve()/decline().
        $this->auditList($request, 'LabTestRequest', $requests->count());

        return response()->json([
            'data' => LabDeskRequestResource::collection($requests),
            'meta' => [
                'status' => $status,
                'count'  => $requests->count(),
            ],
        ]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $facilityId = $this->facilityId($request);
        if ($facilityId === null) {
            return $this->noFacility();
        }

        $testRequest = $this->desk->findRequest($facilityId, $id);
        if ($testRequest === null) {
            return $this->notFound('lab_api.request_not_found', 'request_not_found');
        }

        $decided = $this->desk->approveRequest($testRequest, $facilityId, $this->userId($request));

        $this->audit($request, [$decided->patient_id], 'lab_desk_request_approved', 'LabTestRequest', $decided->id, 'update');

        return response()->json([
            'data' => LabDeskRequestResource::make($decided),
        ]);
    }

    public function decline(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $facilityId = $this->facilityId($request);
        if ($facilityId === null) {
            return $this->noFacility();
        }

        $testRequest = $this->desk->findRequest($facilityId, $id);
        if ($testRequest === null) {
            return $this->notFound('lab_api.request_not_found', 'request_not_found');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reason.required' => __('lab_api.validation.reason_required'),
            'reason.min'      => __('lab_api.validation.reason_required'),
        ]);

        $decided = $this->desk->declineRequest($testRequest, $this->userId($request), $validated['reason']);

        $this->audit($request, [$decided->patient_id], 'lab_desk_request_declined', 'LabTestRequest', $decided->id, 'update');

        return response()->json([
            'data' => LabDeskRequestResource::make($decided),
        ]);
    }
}
