<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BloodRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Modules\CareMap\Services\BloodRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Blood bank queue — the receiving end of a patient's blood request.
 *
 * Before this controller a request was a message into a void: a patient could
 * raise one, and nothing on the platform could answer. `confirmed`, `ready`,
 * `fulfilled` and `rejected` were declared in App\Enums\BloodRequestStatus and
 * unreachable in practice — the only exits from `pending` were the patient
 * cancelling and the hourly expiry sweep.
 *
 * Deliberately small. A blood bank lists what has been asked of it and answers
 * each one; the answer is a status transition with an actor and a timestamp.
 * There is no assignment, no queueing, no escalation and no stock movement:
 * issuing units stays a separate, explicit act through
 * /v1/inventory/blood/{item}/adjust, which re-publishes availability on its own.
 *
 * Scoping — the rule that matters here:
 *   `facility_id` comes only from $request->attributes (set by
 *   VerifyIntegrationClient), never from a header, body or query. It is a
 *   `facilities.id` (the tenant record); requests are addressed to a
 *   `care_facilities.id` (the public listing). The bridge is
 *   `care_facilities.facility_id`, so a client can only ever see and answer
 *   requests raised against its own listings. A request belonging to another
 *   blood bank is simply not found — never merely forbidden.
 *
 * Endpoints:
 *   GET  /api/v1/blood-bank/requests                 — the queue (default: open)
 *   GET  /api/v1/blood-bank/requests/{id}            — one request
 *   POST /api/v1/blood-bank/requests/{id}/decision   — confirm / ready / fulfil / reject
 */
class BloodRequestQueueController extends Controller
{
    public function __construct(private readonly BloodRequestService $requests)
    {
    }

    /**
     * GET /api/v1/blood-bank/requests
     * Query: ?status=pending|confirmed|... &scope=open|all &limit=50
     */
    public function index(Request $request): JsonResponse
    {
        $listingIds = $this->listingIdsFor($request);

        if ($listingIds === null) {
            return $this->facilityUnresolved();
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(BloodRequestStatus::values())],
            'scope'  => ['nullable', 'string', Rule::in(['open', 'all'])],
            'limit'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = BloodRequest::query()
            ->whereIn('care_facility_id', $listingIds)
            ->with(['careFacility', 'patient']);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        } elseif (($validated['scope'] ?? 'open') === 'open') {
            $query->open();
        }

        // Most urgent first, then oldest — a blood bank works the top of this
        // list, and an emergency must not sit under a routine request that
        // happens to be older.
        $rows = $query
            ->orderByRaw("CASE urgency WHEN 'emergency' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END")
            ->orderBy('created_at')
            ->limit((int) ($validated['limit'] ?? 50))
            ->get();

        return response()->json([
            'facility_id' => $request->attributes->get('facility_id'),
            'data'        => $rows->map(fn (BloodRequest $r) => $this->payload($r))->all(),
            'meta'        => [
                'total'        => $rows->count(),
                'open_total'   => BloodRequest::query()->whereIn('care_facility_id', $listingIds)->open()->count(),
                'listing_ids'  => $listingIds,
            ],
        ]);
    }

    /**
     * GET /api/v1/blood-bank/requests/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $listingIds = $this->listingIdsFor($request);

        if ($listingIds === null) {
            return $this->facilityUnresolved();
        }

        $bloodRequest = $this->findScoped($id, $listingIds);

        if ($bloodRequest === null) {
            return $this->notFound();
        }

        return response()->json(['data' => $this->payload($bloodRequest)]);
    }

    /**
     * POST /api/v1/blood-bank/requests/{id}/decision
     * Body: { decision: confirmed|ready|fulfilled|rejected, note?: string }
     *
     * Forward-only. An illegal move (reopening a cancelled request, jumping
     * back from `ready` to `confirmed`) is refused with 409 and changes
     * nothing; no path here deletes a request.
     */
    public function decide(Request $request, string $id): JsonResponse
    {
        $listingIds = $this->listingIdsFor($request);

        if ($listingIds === null) {
            return $this->facilityUnresolved();
        }

        $validated = $request->validate([
            'decision' => ['required', 'string', Rule::in(BloodRequestStatus::facilityDecisions())],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        $bloodRequest = $this->findScoped($id, $listingIds);

        if ($bloodRequest === null) {
            return $this->notFound();
        }

        $target = BloodRequestStatus::from($validated['decision']);

        try {
            $bloodRequest = $this->requests->decide(
                request:      $bloodRequest,
                target:       $target,
                actor:        (string) $request->attributes->get('integration_client_id', 'unknown_client'),
                facilityNote: $validated['note'] ?? null,
            );
        } catch (RuntimeException $e) {
            if ($e->getMessage() === BloodRequestService::ERR_BAD_TRANSITION) {
                return response()->json([
                    'message'    => __('api.blood_request_transition_not_allowed'),
                    'error_code' => BloodRequestService::ERR_BAD_TRANSITION,
                    'from'       => $bloodRequest->status->value,
                    'to'         => $target->value,
                    'allowed'    => array_map(
                        static fn (BloodRequestStatus $s) => $s->value,
                        $bloodRequest->status->facilityTransitions(),
                    ),
                ], 409);
            }

            throw $e;
        }

        $bloodRequest->load(['careFacility', 'patient']);

        return response()->json([
            'message' => __('api.blood_request_decision_recorded'),
            'data'    => $this->payload($bloodRequest),
        ]);
    }

    // ── Scoping helpers ─────────────────────────────────────────────────────

    /**
     * The public listings this integration client speaks for.
     *
     * @return list<string>|null  null when the middleware resolved no facility.
     */
    private function listingIdsFor(Request $request): ?array
    {
        $facilityId = $request->attributes->get('facility_id');

        if (! $facilityId) {
            return null;
        }

        return CareFacility::query()
            ->where('facility_id', $facilityId)
            ->pluck('id')
            ->map(static fn ($id) => (string) $id)
            ->all();
    }

    /** @param  list<string>  $listingIds */
    private function findScoped(string $id, array $listingIds): ?BloodRequest
    {
        if ($listingIds === []) {
            return null;
        }

        return BloodRequest::query()
            ->whereIn('care_facility_id', $listingIds)
            ->with(['careFacility', 'patient'])
            ->find($id);
    }

    private function facilityUnresolved(): JsonResponse
    {
        return response()->json([
            'message'    => __('api.facility_unresolved_id'),
            'error_code' => 'FACILITY_UNRESOLVABLE',
        ], 422);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message'    => __('api.blood_request_not_found'),
            'error_code' => 'REQUEST_NOT_FOUND',
        ], 404);
    }

    // ── Payload ─────────────────────────────────────────────────────────────

    /**
     * What the blood bank needs to act: who asked, for what, how urgently, how
     * to reach them, and how long the hold has left.
     *
     * @return array<string,mixed>
     */
    private function payload(BloodRequest $request): array
    {
        return [
            'id'              => $request->id,
            'reference'       => $request->reference,
            'status'          => $request->status->value,
            'status_label'    => $request->status->label(),
            'is_open'         => $request->status->isOpen(),
            'next_decisions'  => array_map(
                static fn (BloodRequestStatus $s) => $s->value,
                $request->status->facilityTransitions(),
            ),
            'blood_group'     => $request->blood_group->value,
            'component_type'  => $request->component_type->value,
            'component_label' => $request->component_type->label(),
            'quantity'        => $request->quantity,
            'urgency'         => $request->urgency->value,
            'urgency_label'   => $request->urgency->label(),
            'contact_phone'   => $request->contact_phone,
            'patient_note'    => $request->patient_note,
            'facility_note'   => $request->facility_note,
            'decided_by'      => $request->decided_by,
            'decided_at'      => $request->decided_at?->toIso8601String(),
            'needed_by'       => $request->needed_by?->toIso8601String(),
            'expires_at'      => $request->expires_at?->toIso8601String(),
            'confirmed_at'    => $request->confirmed_at?->toIso8601String(),
            'fulfilled_at'    => $request->fulfilled_at?->toIso8601String(),
            'created_at'      => $request->created_at?->toIso8601String(),
            // Minimum needed to hand units over at the counter. The Health ID
            // is deliberately NOT here: the patient asked for blood, not to
            // hand this blood bank their national record, and `reference` is
            // already the identifier both sides quote.
            'patient'         => $request->patient === null ? null : [
                'id'   => $request->patient->id,
                'name' => trim($request->patient->first_name . ' ' . $request->patient->last_name),
            ],
            'facility'        => $request->careFacility === null ? null : [
                'id'   => $request->careFacility->id,
                'name' => $request->careFacility->facility_name,
                'city' => $request->careFacility->city,
            ],
        ];
    }
}
