<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\BloodRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Modules\CareMap\Services\BloodRequestService;
use App\Modules\Governance\Services\AccessLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Mobile Provider API — the BLOOD-BANK STAFF DESK.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS EXISTS
 * ─────────────────────────────────────────────────────────────────────────
 *
 * A patient could raise a blood request and no human could answer it. The only
 * receiver on the platform was App\Http\Controllers\Api\V1\BloodRequestQueueController,
 * mounted on routes/blood_bank.php behind VerifyIntegrationClient — and
 * `integration_clients` holds zero rows in production, so nobody anywhere held
 * a credential that could reach it. There was no staff screen of any kind.
 * Every request filed sat `pending` for HOLD_HOURS and was then retired by the
 * hourly sweep, silently, having been seen by no one.
 *
 * This controller is the same behaviour behind the credential staff actually
 * have: the mobile provider bearer token. It is deliberately NOT a second
 * implementation — the lifecycle stays in BloodRequestService::decide() and the
 * legal moves stay in BloodRequestStatus::facilityTransitions(). What is added
 * here is authentication, facility scoping, and an access-log trail.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * SCOPING — the rule that matters
 * ─────────────────────────────────────────────────────────────────────────
 *
 *     facility ← $request->attributes 'provider_facility_id'
 *     actor    ← $request->attributes 'provider_user_id'
 *
 * Both are stamped by AuthenticateMobileProvider from the bearer token, and
 * neither is ever read from a header, body, query string, session, or a
 * fallback default. There is no parameter anywhere in this file that lets a
 * caller name a facility.
 *
 * `provider_facility_id` is a `facilities.id` (the tenant record); a blood
 * request is addressed to a `care_facilities.id` (the public listing). The
 * bridge is `care_facilities.facility_id`, so a desk only ever sees requests
 * raised against its OWN listings. A request belonging to another blood bank is
 * 404 — never 403: telling a caller "forbidden" would already confirm that some
 * patient asked somewhere else for blood.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT IT WILL NOT DO
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  - No patient search, no patient listing, no enumeration. A patient appears
 *    only inside a request already addressed to this facility, and only the
 *    fields needed to hand units over at the counter are returned.
 *  - No stock movement. Confirming a request is the blood bank saying "yes,
 *    come" — it does not reserve, hold or decrement a unit. BloodRequestService
 *    never writes to `blood_availability`, and the patient-facing copy was
 *    corrected specifically to stop claiming units are held. Issuing blood
 *    remains a separate, explicit act through the inventory surface.
 *  - Nothing is deleted and nothing is rewritten. Status moves forward only,
 *    through the enum's own canTransitionTo(); an illegal move is 409 and
 *    changes nothing. A decision records who made it and when.
 *
 * Every read and every write of a patient-linked request is access-logged, and
 * — as on the chart and the referral desk — a disclosure that cannot be
 * recorded does not happen (503).
 */
class MobileBloodDeskController extends Controller
{
    /** Hard ceilings on any list this controller returns. */
    private const LIST_LIMIT     = 50;
    private const LIST_MAX_LIMIT = 100;

    public function __construct(private readonly BloodRequestService $requests)
    {
    }

    // ══════════════════════════════════════════════════════════════════
    // Reading
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/blood-desk/summary
     *
     * Counts for the desk home. Deliberately counts only — a tile that says
     * "4 waiting" discloses nothing about who asked, so this is the one read
     * on the surface that touches no patient-linked field and therefore raises
     * no access-log entry.
     */
    public function summary(Request $request): JsonResponse
    {
        $this->applyRequestLocale($request);

        $facilityId = $this->facilityId($request);

        if (! $facilityId) {
            return $this->facilityRequired();
        }

        $listingIds = $this->listingIdsFor($facilityId);

        $counts = [];

        foreach (BloodRequestStatus::cases() as $status) {
            $counts[$status->value] = $listingIds === []
                ? 0
                : BloodRequest::query()
                    ->whereIn('care_facility_id', $listingIds)
                    ->where('status', $status->value)
                    ->count();
        }

        $open = $listingIds === []
            ? 0
            : BloodRequest::query()->whereIn('care_facility_id', $listingIds)->open()->count();

        return response()->json([
            'data' => [
                'facility_id' => $facilityId,
                'open_total'  => $open,
                'by_status'   => $counts,
                // A desk with no public listing is not an error — it simply has
                // nothing addressed to it yet. Saying so is kinder than an
                // empty queue the staff member cannot explain.
                'has_listing' => $listingIds !== [],
            ],
        ], 200);
    }

    /**
     * GET /api/mobile/blood-desk/requests?status=&scope=&limit=
     *
     * This facility's queue and nothing else. Ordering mirrors the partner-API
     * queue: most urgent first, then oldest, so an emergency never sits beneath
     * a routine request that happens to have been filed earlier.
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyRequestLocale($request);

        $facilityId = $this->facilityId($request);

        if (! $facilityId) {
            return $this->facilityRequired();
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(BloodRequestStatus::values())],
            'scope'  => ['nullable', 'string', Rule::in(['open', 'all'])],
            'limit'  => ['nullable', 'integer', 'min:1', 'max:' . self::LIST_MAX_LIMIT],
        ]);

        $listingIds = $this->listingIdsFor($facilityId);

        if ($listingIds === []) {
            return response()->json([
                'data' => [],
                'meta' => ['facility_id' => $facilityId, 'count' => 0, 'open_total' => 0],
            ], 200);
        }

        $query = BloodRequest::query()
            ->whereIn('care_facility_id', $listingIds)
            ->with(['careFacility', 'patient']);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        } elseif (($validated['scope'] ?? 'open') === 'open') {
            $query->open();
        }

        $rows = $query
            ->orderByRaw("CASE urgency WHEN 'emergency' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END")
            ->orderBy('created_at')
            ->limit((int) ($validated['limit'] ?? self::LIST_LIMIT))
            ->get();

        // The queue is a disclosure of patient-linked records, so it is logged
        // like one — one entry per row read, before anything is returned. A
        // queue that cannot be recorded is not shown.
        foreach ($rows as $row) {
            if ($row->patient_id && ! $this->audit((string) $row->patient_id, $request, 'read', (string) $row->id)) {
                return $this->auditUnavailable();
            }
        }

        return response()->json([
            'data' => $rows->map(fn (BloodRequest $r) => $this->payload($r))->values(),
            'meta' => [
                'facility_id' => $facilityId,
                'count'       => $rows->count(),
                'open_total'  => BloodRequest::query()->whereIn('care_facility_id', $listingIds)->open()->count(),
            ],
        ], 200);
    }

    /**
     * GET /api/mobile/blood-desk/requests/{id}
     *
     * One request, only if it was addressed to this facility. Another blood
     * bank's request answers 404, not 403.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyRequestLocale($request);

        $facilityId = $this->facilityId($request);

        if (! $facilityId) {
            return $this->facilityRequired();
        }

        $bloodRequest = $this->findScoped($id, $this->listingIdsFor($facilityId));

        if ($bloodRequest === null) {
            return $this->notFound();
        }

        if ($bloodRequest->patient_id && ! $this->audit((string) $bloodRequest->patient_id, $request, 'read', (string) $bloodRequest->id)) {
            return $this->auditUnavailable();
        }

        return response()->json(['data' => $this->payload($bloodRequest)], 200);
    }

    // ══════════════════════════════════════════════════════════════════
    // Answering — every path lands on the same transition()
    // ══════════════════════════════════════════════════════════════════

    /**
     * POST /api/mobile/blood-desk/requests/{id}/decide
     * Body: { decision: confirmed|rejected, note?: string }
     *
     * The two answers a desk gives to a request it has just read. `ready` and
     * `fulfilled` have their own verbs below because they are counter events,
     * not decisions — but all four are the same forward-only transition and
     * the same 409 on an illegal move.
     */
    public function decide(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', Rule::in([
                BloodRequestStatus::Confirmed->value,
                BloodRequestStatus::Rejected->value,
            ])],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        $target = BloodRequestStatus::from($validated['decision']);

        return $this->transition(
            $request,
            $id,
            $target,
            $target === BloodRequestStatus::Confirmed
                ? __('blood_desk.confirmed_recorded')
                : __('blood_desk.rejected_recorded'),
        );
    }

    /**
     * POST /api/mobile/blood-desk/requests/{id}/ready
     *
     * The units for this request are at the counter. Still not a stock
     * movement — the desk is telling the patient to come, and blood inventory
     * changes only when a unit is actually issued through the inventory
     * surface.
     */
    public function ready(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, BloodRequestStatus::Ready, __('blood_desk.ready_recorded'));
    }

    /**
     * POST /api/mobile/blood-desk/requests/{id}/fulfil
     *
     * Collected. Terminal — the request transitions to nothing after this.
     */
    public function fulfil(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, BloodRequestStatus::Fulfilled, __('blood_desk.fulfilled_recorded'));
    }

    /**
     * The one write path. Scope, find, log, then hand the move to
     * BloodRequestService::decide() — which owns the rule about what is legal
     * and is the only thing that touches the row.
     */
    private function transition(Request $request, string $id, BloodRequestStatus $target, string $message): JsonResponse
    {
        $this->applyRequestLocale($request);

        $facilityId = $this->facilityId($request);

        if (! $facilityId) {
            return $this->facilityRequired();
        }

        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        $bloodRequest = $this->findScoped($id, $this->listingIdsFor($facilityId));

        if ($bloodRequest === null) {
            return $this->notFound();
        }

        // Logged BEFORE the write: an answer recorded on a patient's request
        // with no trail is exactly the event the access log exists for.
        if ($bloodRequest->patient_id && ! $this->audit((string) $bloodRequest->patient_id, $request, 'write', (string) $bloodRequest->id)) {
            return $this->auditUnavailable();
        }

        $from = $bloodRequest->status;

        try {
            $bloodRequest = $this->requests->decide(
                request:      $bloodRequest,
                target:       $target,
                actor:        (string) $this->staffId($request),
                facilityNote: $note,
            );
        } catch (RuntimeException $e) {
            if ($e->getMessage() === BloodRequestService::ERR_BAD_TRANSITION) {
                return response()->json([
                    'message'    => __('blood_desk.transition_not_allowed'),
                    'error_code' => BloodRequestService::ERR_BAD_TRANSITION,
                    'from'       => $from->value,
                    'to'         => $target->value,
                    'allowed'    => array_map(
                        static fn (BloodRequestStatus $s) => $s->value,
                        $from->facilityTransitions(),
                    ),
                ], 409);
            }

            throw $e;
        }

        $bloodRequest->load(['careFacility', 'patient']);

        return response()->json([
            'message' => $message,
            'data'    => $this->payload($bloodRequest),
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════
    // Scoping helpers
    // ══════════════════════════════════════════════════════════════════

    /**
     * The public listings this facility speaks for.
     *
     * @return list<string>
     */
    private function listingIdsFor(string $facilityId): array
    {
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

    /**
     * The facility this session speaks for. Only ever from the request
     * attributes the auth middleware set — never a header, body, query string
     * or session, and never a fallback default.
     */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /** The staff member's user id — stamped by AuthenticateMobileProvider. */
    private function staffId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_user_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /** Honour Accept-Language for this response only. */
    private function applyRequestLocale(Request $request): void
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header !== '' && str_starts_with(strtolower(ltrim($header)), 'fr')) {
            App::setLocale('fr');
        }
    }

    /**
     * Record the act against the patient. Returns false when it could not be
     * written, which every caller turns into a 503 rather than proceeding.
     */
    private function audit(string $patientId, Request $request, string $accessType, ?string $resourceId): bool
    {
        $staffId = $this->staffId($request);

        if (! $staffId) {
            return false;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $staffId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $this->facilityId($request),
                purpose: 'treatment:blood_request',
                dataCategory: 'blood_request',
                resourceType: 'BloodRequest',
                resourceId: $resourceId,
                accessType: $accessType,
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Blood desk action refused: access log could not be written.', [
                'patient_id'  => $patientId,
                'staff_id'    => $staffId,
                'access_type' => $accessType,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // Responses
    // ══════════════════════════════════════════════════════════════════

    private function facilityRequired(): JsonResponse
    {
        return response()->json([
            'message'    => __('blood_desk.facility_required'),
            'error_code' => 'IDENTITY_UNRESOLVABLE',
        ], 401);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message'    => __('blood_desk.request_not_found'),
            'error_code' => 'REQUEST_NOT_FOUND',
        ], 404);
    }

    private function auditUnavailable(): JsonResponse
    {
        return response()->json([
            'message'    => __('blood_desk.audit_unavailable'),
            'error_code' => 'AUDIT_UNAVAILABLE',
        ], 503);
    }

    /**
     * What the desk needs to act: what was asked for, how urgently, how to
     * reach the person, and how long the hold has left.
     *
     * The Health ID IS here, unlike the partner-API payload — a member of staff
     * standing at the counter has to match the person in front of them to the
     * request, and `reference` alone cannot do that. Nothing beyond identity
     * and the clinical need is returned: no address, no chart, no history. The
     * request is what the patient sent to this facility, and the request is all
     * that is shown.
     *
     * @return array<string,mixed>
     */
    private function payload(BloodRequest $r): array
    {
        return [
            'id'              => $r->id,
            'reference'       => $r->reference,
            'status'          => $r->status->value,
            'status_label'    => $r->status->label(),
            'is_open'         => $r->status->isOpen(),
            'next_decisions'  => array_map(
                static fn (BloodRequestStatus $s) => $s->value,
                $r->status->facilityTransitions(),
            ),
            'blood_group'     => $r->blood_group->value,
            'component_type'  => $r->component_type->value,
            'component_label' => $r->component_type->label(),
            'quantity'        => $r->quantity,
            'urgency'         => $r->urgency->value,
            'urgency_label'   => $r->urgency->label(),
            'contact_phone'   => $r->contact_phone,
            'patient_note'    => $r->patient_note,
            'facility_note'   => $r->facility_note,
            'decided_by'      => $r->decided_by,
            'decided_at'      => $r->decided_at?->toIso8601String(),
            'needed_by'       => $r->needed_by?->toIso8601String(),
            'expires_at'      => $r->expires_at?->toIso8601String(),
            'confirmed_at'    => $r->confirmed_at?->toIso8601String(),
            'fulfilled_at'    => $r->fulfilled_at?->toIso8601String(),
            'created_at'      => $r->created_at?->toIso8601String(),
            'patient'         => $r->patient === null ? null : [
                'id'        => $r->patient->id,
                'health_id' => $r->patient->health_id,
                'name'      => trim($r->patient->first_name . ' ' . $r->patient->last_name),
            ],
            'facility'        => $r->careFacility === null ? null : [
                'id'   => $r->careFacility->id,
                'name' => $r->careFacility->facility_name,
                'city' => $r->careFacility->city,
            ],
        ];
    }
}
