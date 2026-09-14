<?php

namespace App\Http\Controllers\MedicalId;

use App\Enums\BloodComponentType;
use App\Enums\BloodGroup;
use App\Enums\BloodRequestStatus;
use App\Enums\BloodRequestUrgency;
use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\Patient;
use App\Modules\CareMap\Services\BloodRequestService;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Staff web desk for patient blood requests — the browser twin of
 * App\Http\Controllers\Api\Mobile\MobileBloodDeskController.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS EXISTS
 * ─────────────────────────────────────────────────────────────────────────
 *
 * The mobile desk gave blood-bank staff a receiver, but only inside the
 * provider app. On the web there was still no screen: BloodRequestService
 * notified every staff member at the facility the moment a request landed and
 * pointed them at `/facility/inventory/blood` — the STOCK page, which never
 * shows a request. Staff were pinged and taken somewhere that could not answer.
 * This is the queue that notification now points at.
 *
 * It is NOT a second implementation. The lifecycle stays in
 * BloodRequestService::decide(); the legal moves stay in
 * BloodRequestStatus::facilityTransitions(). What is added here is the browser
 * session's authentication, the same facility scoping, and the same access-log
 * trail.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * SCOPING — the rule that matters
 * ─────────────────────────────────────────────────────────────────────────
 *
 *     facility ← PortalContextService::facilityId()  (session / primary posting,
 *                resolved by the auth + facility.context middleware)
 *     actor    ← PortalContextService::actorId()     (Auth::id())
 *
 * Neither is ever read from a header, body, query string, or a fallback
 * default. There is no parameter anywhere in this file that lets a caller name
 * a facility, and there is no "first facility in the table" guess: with no
 * resolved context the desk shows an empty queue and says why, rather than
 * showing somebody else's.
 *
 * `facilityId()` is a `facilities.id` (the tenant record); a blood request is
 * addressed to a `care_facilities.id` (the public listing). The bridge is
 * `care_facilities.facility_id`, so a desk only ever sees requests raised
 * against its OWN listings. Another blood bank's request is 404 — never 403:
 * answering "forbidden" would already confirm that some patient asked
 * elsewhere for blood.
 *
 * Role refusal is the other way round and belongs to the route: the
 * `capability:blood_inventory` middleware answers 403 `role_forbidden` before
 * anything here runs.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT IT WILL NOT DO
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  - No patient search, no patient listing, no enumeration. A patient appears
 *    only inside a request already addressed to this facility.
 *  - No stock movement. Confirming is the blood bank saying "yes, come" — it
 *    does not reserve, hold or decrement a unit. BloodRequestService never
 *    writes to `blood_availability`, and the patient-facing copy was corrected
 *    specifically to stop claiming units are held. Issuing blood stays a
 *    separate, explicit act on the inventory screen next door.
 *  - Nothing is deleted and nothing is rewritten. Status moves forward only,
 *    through the enum's own canTransitionTo(); an illegal move changes nothing
 *    and comes back as an error flash.
 *
 * Every read and every write of a patient-linked request is access-logged, and
 * — as on the mobile desk — a disclosure that cannot be recorded does not
 * happen.
 */
class BloodRequestDeskController extends Controller
{
    /** Hard ceiling on the queue page. */
    private const LIST_LIMIT = 100;

    public function __construct(
        private readonly BloodRequestService $requests,
        private readonly PortalContextService $context,
    ) {}

    // ══════════════════════════════════════════════════════════════════
    // Reading
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /facility/blood-requests
     *
     * This facility's queue and nothing else. Ordering mirrors the mobile desk
     * and the partner API: unanswered first, then most urgent, then oldest, so
     * an emergency never sits beneath a routine request filed earlier.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(BloodRequestStatus::values())],
            'scope'  => ['nullable', 'string', Rule::in(['open', 'all'])],
        ]);

        $facilityId = $this->context->facilityId();
        $listingIds = $facilityId ? $this->listingIdsFor($facilityId) : [];

        $status  = $validated['status'] ?? null;
        $scope   = $validated['scope'] ?? 'open';
        $rows    = collect();
        $counts  = array_fill_keys(BloodRequestStatus::values(), 0);
        $open    = 0;
        $blocked = false;

        if ($listingIds !== []) {
            foreach (BloodRequestStatus::cases() as $case) {
                $counts[$case->value] = BloodRequest::query()
                    ->whereIn('care_facility_id', $listingIds)
                    ->where('status', $case->value)
                    ->count();
            }

            $open = BloodRequest::query()->whereIn('care_facility_id', $listingIds)->open()->count();

            $query = BloodRequest::query()
                ->whereIn('care_facility_id', $listingIds)
                ->with(['careFacility', 'patient']);

            if ($status !== null) {
                $query->where('status', $status);
            } elseif ($scope === 'open') {
                $query->open();
            }

            $rows = $query
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
                ->orderByRaw("CASE urgency WHEN 'emergency' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END")
                ->orderBy('created_at')
                ->limit(self::LIST_LIMIT)
                ->get();

            // The queue is a disclosure of patient-linked records, so it is
            // logged like one — before anything is rendered. A queue that
            // cannot be recorded is not shown.
            foreach ($rows as $row) {
                if ($row->patient_id && ! $this->audit((string) $row->patient_id, $facilityId, 'read', (string) $row->id)) {
                    $blocked = true;
                    break;
                }
            }

            if ($blocked) {
                $rows = collect();
            }
        }

        return view('portals.staff.blood_requests.index', [
            'rows'         => $rows,
            'counts'       => $counts,
            'openTotal'    => $open,
            'activeStatus' => $status,
            'activeScope'  => $scope,
            'hasListing'   => $listingIds !== [],
            'hasFacility'  => $facilityId !== null && $facilityId !== '',
            'auditBlocked' => $blocked,
        ]);
    }

    /**
     * GET /facility/blood-requests/{id}
     *
     * One request, only if it was addressed to this facility. Another blood
     * bank's request is 404, not 403.
     */
    public function show(Request $request, string $id): View
    {
        $facilityId = $this->context->facilityId();

        $bloodRequest = $this->findScoped($id, $facilityId);

        if ($bloodRequest === null) {
            abort(404);
        }

        if ($bloodRequest->patient_id
            && ! $this->audit((string) $bloodRequest->patient_id, $facilityId, 'read', (string) $bloodRequest->id)) {
            abort(503, __('blood_desk.audit_unavailable'));
        }

        return view('portals.staff.blood_requests.show', [
            'bloodRequest' => $bloodRequest,
            'nextMoves'    => $bloodRequest->status->facilityTransitions(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // Raising — the browser's only way to file a request
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /facility/blood-requests/new
     *
     * Until this screen existed the ONLY writer of `blood_requests` was the
     * patient app (MobileBloodController::store). A clinician at the bedside
     * had a desk to answer requests and no way to raise one, so the queue on
     * every clinical dashboard stayed empty.
     *
     * The request is filed against THIS facility's own directory listing —
     * the same listing the queue reads — so it lands on this desk. The
     * facility is never a form field.
     */
    public function create(): View
    {
        $facilityId = $this->context->facilityId();
        $listings   = $facilityId ? $this->listingsFor($facilityId) : collect();

        return view('portals.staff.blood_requests.create', [
            'listings'    => $listings,
            'hasFacility' => $facilityId !== null && $facilityId !== '',
            'hasListing'  => $listings->isNotEmpty(),
            'maxUnits'    => BloodRequestService::MAX_UNITS,
        ]);
    }

    /**
     * POST /facility/blood-requests
     *
     * Same call, same arguments, same rules as the patient app: the request
     * goes through BloodRequestService::request() and nothing else. There is
     * no second write path — the service still checks that the listing reports
     * the group/component as available, still caps units, still refuses a
     * duplicate open request, and still raises the `blood_order_received`
     * notification that points staff at this desk. Nothing here reserves or
     * moves a unit.
     *
     * The patient is named by exact Health ID only (Patient::resolveHealthId,
     * which also follows active aliases). No name search, no enumeration; an id
     * that does not resolve is a validation error and creates nothing.
     *
     * `facility_id` in the body is ignored on purpose: the facility comes from
     * the session context, and a `listing_id`, if posted, must belong to it.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'health_id'      => ['required', 'string', 'max:64'],
            'listing_id'     => ['nullable', 'uuid'],
            'blood_group'    => ['required', 'string', Rule::in(BloodGroup::values())],
            'component_type' => ['required', 'string', Rule::in(BloodComponentType::values())],
            'quantity'       => ['required', 'integer', 'min:1', 'max:' . BloodRequestService::MAX_UNITS],
            'urgency'        => ['required', 'string', Rule::in(BloodRequestUrgency::values())],
            'indication'     => ['required', 'string', 'max:500'],
            'contact_phone'  => ['nullable', 'string', 'max:32'],
            'needed_by'      => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $facilityId = $this->context->facilityId();

        if ($facilityId === null || $facilityId === '') {
            return redirect()
                ->route('portals.staff.blood_requests.index')
                ->with('error', __('blood_desk.facility_required'));
        }

        $listings = $this->listingsFor($facilityId);
        $listing  = isset($validated['listing_id'])
            ? $listings->first(fn (CareFacility $l) => (string) $l->id === (string) $validated['listing_id'])
            : $listings->first();

        if ($listing === null) {
            // Either this facility has no listing, or the posted one is not
            // this facility's. Both read the same: nothing to file against.
            return back()
                ->withInput()
                ->withErrors(['listing_id' => __('blood_desk.no_listing_notice')]);
        }

        $patient = Patient::resolveHealthId($validated['health_id']);

        if ($patient === null) {
            return back()
                ->withInput()
                ->withErrors(['health_id' => __('blood_desk.create_patient_not_found')]);
        }

        // Logged BEFORE the write, exactly as an answer is: filing a request in
        // a patient's name is an act on that patient's record.
        if (! $this->audit((string) $patient->id, $facilityId, 'write', null)) {
            return back()
                ->withInput()
                ->with('error', __('blood_desk.audit_unavailable'));
        }

        try {
            $bloodRequest = $this->requests->request(
                patientId:     (string) $patient->id,
                facility:      $listing,
                bloodGroup:    BloodGroup::from($validated['blood_group']),
                componentType: BloodComponentType::from($validated['component_type']),
                quantity:      (int) $validated['quantity'],
                urgency:       BloodRequestUrgency::from($validated['urgency']),
                contactPhone:  $validated['contact_phone'] ?? null,
                patientNote:   $validated['indication'],
                neededBy:      $validated['needed_by'] ?? null,
            );
        } catch (RuntimeException $e) {
            $field = match ($e->getMessage()) {
                BloodRequestService::ERR_NOT_AVAILABLE => 'blood_group',
                BloodRequestService::ERR_TOO_MANY_OPEN,
                BloodRequestService::ERR_DUPLICATE     => 'health_id',
                default                                => null,
            };

            if ($field === null) {
                throw $e;
            }

            return back()
                ->withInput()
                ->withErrors([$field => __('blood_desk.create_err_' . strtolower($e->getMessage()))]);
        }

        return redirect()
            ->route('portals.staff.blood_requests.show', $bloodRequest->id)
            ->with('success', __('blood_desk.create_recorded', ['reference' => $bloodRequest->reference]));
    }

    // ══════════════════════════════════════════════════════════════════
    // Answering — every path lands on the same transition()
    // ══════════════════════════════════════════════════════════════════

    /**
     * POST /facility/blood-requests/{id}/decide
     * Body: { decision: confirmed|rejected, note?: string }
     */
    public function decide(Request $request, string $id): RedirectResponse
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
     * POST /facility/blood-requests/{id}/ready
     *
     * The units for this request are at the counter. Still not a stock
     * movement — blood inventory changes only when a unit is actually issued
     * on the inventory screen.
     */
    public function ready(Request $request, string $id): RedirectResponse
    {
        return $this->transition($request, $id, BloodRequestStatus::Ready, __('blood_desk.ready_recorded'));
    }

    /** POST /facility/blood-requests/{id}/fulfil — collected. Terminal. */
    public function fulfil(Request $request, string $id): RedirectResponse
    {
        return $this->transition($request, $id, BloodRequestStatus::Fulfilled, __('blood_desk.fulfilled_recorded'));
    }

    /**
     * The one write path. Scope, find, log, then hand the move to
     * BloodRequestService::decide() — which owns the rule about what is legal
     * and is the only thing that touches the row.
     */
    private function transition(Request $request, string $id, BloodRequestStatus $target, string $message): RedirectResponse
    {
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        $facilityId   = $this->context->facilityId();
        $bloodRequest = $this->findScoped($id, $facilityId);

        if ($bloodRequest === null) {
            abort(404);
        }

        // Logged BEFORE the write: an answer recorded on a patient's request
        // with no trail is exactly the event the access log exists for.
        if ($bloodRequest->patient_id
            && ! $this->audit((string) $bloodRequest->patient_id, $facilityId, 'write', (string) $bloodRequest->id)) {
            return redirect()
                ->route('portals.staff.blood_requests.show', $bloodRequest->id)
                ->with('error', __('blood_desk.audit_unavailable'));
        }

        try {
            $this->requests->decide(
                request:      $bloodRequest,
                target:       $target,
                actor:        (string) $this->context->actorId(),
                facilityNote: $note,
            );
        } catch (RuntimeException $e) {
            if ($e->getMessage() === BloodRequestService::ERR_BAD_TRANSITION) {
                return redirect()
                    ->route('portals.staff.blood_requests.show', $bloodRequest->id)
                    ->with('error', __('blood_desk.transition_not_allowed'));
            }

            throw $e;
        }

        return redirect()
            ->route('portals.staff.blood_requests.show', $bloodRequest->id)
            ->with('success', $message);
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

    /**
     * The same listings as models — what the create form files against.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, CareFacility>
     */
    private function listingsFor(string $facilityId): \Illuminate\Database\Eloquent\Collection
    {
        return CareFacility::query()
            ->where('facility_id', $facilityId)
            ->orderBy('facility_name')
            ->get();
    }

    /**
     * Find a request, but only within this facility's listings. A null or
     * unresolved facility finds nothing at all — there is no fallback that
     * widens the search.
     */
    private function findScoped(string $id, ?string $facilityId): ?BloodRequest
    {
        if ($facilityId === null || $facilityId === '') {
            return null;
        }

        $listingIds = $this->listingIdsFor($facilityId);

        if ($listingIds === []) {
            return null;
        }

        return BloodRequest::query()
            ->whereIn('care_facility_id', $listingIds)
            ->with(['careFacility', 'patient'])
            ->find($id);
    }

    /**
     * Record the act against the patient. Returns false when it could not be
     * written, which every caller turns into a refusal rather than proceeding.
     */
    private function audit(string $patientId, ?string $facilityId, string $accessType, ?string $resourceId): bool
    {
        $actorId = $this->context->actorId();

        if (! $actorId) {
            return false;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $actorId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: 'treatment:blood_request',
                dataCategory: 'blood_request',
                resourceType: 'BloodRequest',
                resourceId: $resourceId,
                accessType: $accessType,
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Blood request desk action refused: access log could not be written.', [
                'patient_id'  => $patientId,
                'actor_id'    => $actorId,
                'access_type' => $accessType,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }
}
