<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataAccessCommitteeReviewResource;
use App\Http\Resources\ResearchAccessLogResource;
use App\Http\Resources\ResearchAccessRequestResource;
use App\Http\Resources\ResearchDataAgreementResource;
use App\Models\DataAccessCommitteeReview;
use App\Models\ResearchAccessRequest;
use App\Models\ResearchDataAgreement;
use App\Models\ResearcherProfile;
use App\Modules\ResearchAccess\Services\ResearchAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * ResearchAccessController
 *
 * Manages the research data access lifecycle:
 *
 *   1. Researcher submits request (requires active ethics approval)
 *   2. DAC members record review decisions
 *   3. Admin approves or rejects based on DAC vote
 *   4. Researcher signs data agreement
 *   5. Admin logs data access (append-only audit trail)
 *
 * Security constraints enforced at every step:
 * - Ethics approval required before submission
 * - At least one approved DAC review required before approval
 * - Signed data agreement required before access logging
 * - Rejected requests cannot access data
 * - All access is append-only logged
 *
 * Routes protected by VerifyIntegrationClient middleware.
 *
 * Endpoints:
 *  GET    /v1/research/requests                          — list requests
 *  POST   /v1/research/requests                          — submit a request
 *  GET    /v1/research/requests/{id}                     — show request detail
 *  POST   /v1/research/requests/{id}/dac-review          — record DAC review
 *  POST   /v1/research/requests/{id}/approve             — approve request
 *  POST   /v1/research/requests/{id}/reject              — reject request
 *  POST   /v1/research/agreements/{id}/sign              — sign data agreement
 *  POST   /v1/research/requests/{id}/log-access          — log data access event
 */
class ResearchAccessController extends Controller
{
    public function __construct(private readonly ResearchAccessService $service) {}

    // ── 1. List & Show ────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => ['nullable', 'string', 'in:submitted,under_review,active,rejected,expired'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $requests = ResearchAccessRequest::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'total'        => $requests->total(),
            ],
        ]);
    }

    public function show(ResearchAccessRequest $researchRequest): JsonResponse
    {
        $researchRequest->load(['dacReviews', 'dataAgreements']);

        return response()->json(['data' => ResearchAccessRequestResource::make($researchRequest)]);
    }

    // ── 2. Submit Request ─────────────────────────────────────────────────

    /**
     * Submit a research access request.
     * Body: { researcher_profile_id, purpose, ethics_document_id,
     *         requested_dataset_scope_json }
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'researcher_profile_id'        => ['required', 'uuid', 'exists:researcher_profiles,id'],
            'purpose'                      => ['required', 'string', 'min:50', 'max:5000'],
            'ethics_document_id'           => ['required', 'uuid'],
            'requested_dataset_scope_json' => ['required', 'array'],
        ]);

        $researcher = ResearcherProfile::findOrFail($validated['researcher_profile_id']);

        try {
            $researchRequest = $this->service->submitRequest(
                [
                    'purpose'                      => $validated['purpose'],
                    'ethics_document_id'           => $validated['ethics_document_id'],
                    'requested_dataset_scope_json' => $validated['requested_dataset_scope_json'],
                ],
                $researcher
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.research_request_submitted'),
            'data'    => ResearchAccessRequestResource::make($researchRequest),
        ], 201);
    }

    // ── 3. DAC Review ─────────────────────────────────────────────────────

    /**
     * Record a Data Access Committee review decision.
     * Body: { reviewer_id, decision, comments?, conditions? }
     * decision: approved|rejected|deferred|more_info_needed
     */
    public function dacReview(ResearchAccessRequest $researchRequest, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reviewer_id' => ['required', 'uuid'],
            'decision'    => ['required', 'string', 'in:approved,rejected,deferred,more_info_needed'],
            'comments'    => ['nullable', 'string', 'max:2000'],
            'conditions'  => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $review = $this->service->recordDacReview(
                $researchRequest,
                $validated['reviewer_id'],
                $validated['decision'],
                $validated['comments'] ?? null,
                $validated['conditions'] ?? null
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.dac_review_recorded'),
            'data'    => DataAccessCommitteeReviewResource::make($review),
        ], 201);
    }

    // ── 4. Approve / Reject ───────────────────────────────────────────────

    /**
     * Approve the research request.
     * Body: { approved_by, expires_at }
     */
    public function approve(ResearchAccessRequest $researchRequest, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'approved_by' => ['required', 'uuid'],
            'expires_at'  => ['required', 'date', 'after:today'],
        ]);

        try {
            $this->service->approveRequest(
                $researchRequest,
                $validated['approved_by'],
                new \DateTime($validated['expires_at'])
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.research_request_approved'),
            'data'    => ResearchAccessRequestResource::make($researchRequest->fresh()),
        ]);
    }

    /**
     * Reject the research request.
     * Body: { rejected_by }
     */
    public function reject(ResearchAccessRequest $researchRequest, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rejected_by' => ['required', 'uuid'],
        ]);

        try {
            $this->service->rejectRequest($researchRequest, $validated['rejected_by']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.research_request_rejected'),
            'data'    => ResearchAccessRequestResource::make($researchRequest->fresh()),
        ]);
    }

    // ── 5. Sign Agreement ─────────────────────────────────────────────────

    /**
     * Sign a data agreement — required before access logging is permitted.
     * Body: (none — IP address is taken from request)
     */
    public function signAgreement(ResearchDataAgreement $agreement, Request $request): JsonResponse
    {
        try {
            $this->service->signAgreement($agreement, $request->ip());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.data_agreement_signed'),
            'data'    => ResearchDataAgreementResource::make($agreement->fresh()),
        ]);
    }

    // ── 6. Log Access ─────────────────────────────────────────────────────

    /**
     * Log a data access event (append-only).
     * Requires: active request + signed agreement.
     * Body: { researcher_profile_id, action, context? }
     */
    public function logAccess(ResearchAccessRequest $researchRequest, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'researcher_profile_id' => ['required', 'uuid', 'exists:researcher_profiles,id'],
            'action'                => ['required', 'string', 'max:255'],
            'context'               => ['nullable', 'array'],
        ]);

        $researcher = ResearcherProfile::findOrFail($validated['researcher_profile_id']);

        try {
            $log = $this->service->logAccess(
                $researchRequest,
                $researcher,
                $validated['action'],
                $validated['context'] ?? null,
                $request->ip()
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.access_logged'),
            'data'    => ResearchAccessLogResource::make($log),
        ], 201);
    }
}
