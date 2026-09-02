<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\MpiCandidate;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * MasterPatientIndexController
 *
 * Admin-only API for the Master Patient Index (MPI).
 * All routes are protected by VerifyIntegrationClient middleware.
 *
 * Endpoints:
 *  GET    /v1/admin/mpi/candidates            — list duplicate candidates
 *  POST   /v1/admin/mpi/detect                — trigger duplicate detection for a facility
 *  POST   /v1/admin/mpi/search                — search patients by identifiers or demographics
 *  POST   /v1/admin/mpi/candidates/{id}/confirm — confirm two records are the same patient
 *  POST   /v1/admin/mpi/candidates/{id}/reject  — reject a candidate pair
 *  POST   /v1/admin/mpi/patients/{patient}/identifiers — link an external identifier
 */
class MasterPatientIndexController extends Controller
{
    public function __construct(private readonly MasterPatientIndexService $service) {}

    /**
     * List MPI duplicate candidates.
     * Supports filtering by ?status=pending_review|merged|rejected and ?min_score=70
     */
    public function listCandidates(Request $request): JsonResponse
    {
        $request->validate([
            'status'    => ['nullable', 'string', 'in:pending_review,merged,rejected'],
            'min_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginated = $this->service->listCandidates(
            $request->only(['status', 'min_score']),
            (int) $request->input('per_page', 20)
        );

        return response()->json([
            'data'  => $paginated->items(),
            'meta'  => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * Trigger duplicate detection scan for a facility.
     * POST body: { facility_id: uuid, limit?: int }
     */
    public function detect(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:10', 'max:2000'],
        ]);

        $newCandidates = $this->service->detectDuplicates(
            $facilityId,
            (int) ($validated['limit'] ?? 500)
        );

        return response()->json([
            'message'        => __('api.duplicate_detection_complete'),
            'new_candidates' => $newCandidates,
        ]);
    }

    /**
     * Search patients by identifiers or demographic triple.
     * POST body: { identifiers?: [{type, value}], first_name?, last_name?,
     *              phone_number?, date_of_birth?, sex? }
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'identifiers'    => ['nullable', 'array'],
            'identifiers.*.type'  => ['required_with:identifiers', 'string'],
            'identifiers.*.value' => ['required_with:identifiers', 'string'],
            'first_name'     => ['nullable', 'string'],
            'last_name'      => ['nullable', 'string'],
            'phone_number'   => ['nullable', 'string'],
            'date_of_birth'  => ['nullable', 'date'],
            'sex'            => ['nullable', 'string', 'in:male,female'],
        ]);

        $results = $this->service->searchCandidates($request->all());

        return response()->json([
            'data'  => $results->map(fn ($p) => [
                'id'         => $p->id,
                'health_id'  => $p->health_id,
                'first_name' => $p->first_name,
                'last_name'  => $p->last_name,
                'sex'        => $p->sex,
                'facility_id'=> $p->facility_id,
            ])->values(),
            'count' => $results->count(),
        ]);
    }

    /**
     * Confirm that source and target are the same patient.
     * POST body: { actor_id: uuid }
     */
    public function confirmMatch(MpiCandidate $candidate, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id' => ['required', 'uuid'],
        ]);

        try {
            $candidate = $this->service->confirmMatch($candidate, $validated['actor_id']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.candidate_confirmed_duplicate'),
            'data'    => $this->serializeCandidate($candidate),
        ]);
    }

    /**
     * Reject a candidate pair — records are not the same patient.
     * POST body: { actor_id: uuid }
     */
    public function rejectMatch(MpiCandidate $candidate, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actor_id' => ['required', 'uuid'],
        ]);

        try {
            $candidate = $this->service->rejectMatch($candidate, $validated['actor_id']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.candidate_rejected_distinct'),
            'data'    => $this->serializeCandidate($candidate),
        ]);
    }

    /**
     * Link an external identifier to a patient.
     * POST body: { type: string, value: string, issuer?: string, facility_id?: uuid }
     */
    public function linkIdentifier(string $patient, Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.forbidden')], 403);
        }

        $validated = $request->validate([
            'type'   => ['required', 'string', 'max:100'],
            'value'  => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $identifier = $this->service->linkIdentifier($patient, [
                'type'        => $validated['type'],
                'value'       => $validated['value'],
                'issuer'      => $validated['issuer'] ?? null,
                'facility_id' => $facilityId,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('api.identifier_linked'),
            'data'    => [
                'id'              => $identifier->id,
                'patient_id'      => $identifier->patient_id,
                'identifier_type' => $identifier->identifier_type,
                'identifier_value'=> $identifier->identifier_value,
                'issuer'          => $identifier->issuer,
                'facility_id'     => $identifier->facility_id,
            ],
        ], 201);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function serializeCandidate(MpiCandidate $c): array
    {
        return [
            'id'                => $c->id,
            'source_patient_id' => $c->source_patient_id,
            'target_patient_id' => $c->target_patient_id,
            'match_score'       => $c->match_score,
            'match_reasons'     => $c->match_reasons,
            'status'            => $c->status,
            'reviewed_by'       => $c->reviewed_by,
            'reviewed_at'       => $c->reviewed_at?->toISOString(),
        ];
    }
}
