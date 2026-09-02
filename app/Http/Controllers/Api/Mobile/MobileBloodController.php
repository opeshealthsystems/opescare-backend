<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\BloodComponentType;
use App\Enums\BloodGroup;
use App\Enums\BloodRequestUrgency;
use App\Http\Controllers\Controller;
use App\Models\BloodAvailability;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Modules\CareMap\Services\BloodAvailabilitySearchService;
use App\Modules\CareMap\Services\BloodRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Patient Blood Finder — search facilities reporting blood availability, and
 * request (reserve) units at one of them.
 *
 * This is the PATIENT-facing twin of two existing surfaces that stay untouched:
 *  - GET /api/v1/blood/search (CareMapController) — the public directory search
 *  - Api\V1\BloodInventoryController — the staff stock-management surface
 *
 * The geolocation search itself is not reimplemented here: it is delegated to
 * App\Modules\CareMap\Services\BloodAvailabilitySearchService, which already
 * does distance filtering and sorting over `blood_availability`.
 *
 * Scoping notes:
 *  - Availability listings are facility reference data (no patient
 *    information), so they carry no ConsentGrant gate — exactly as the Medicine
 *    Finder's catalog and stock listings do.
 *  - Blood requests ARE patient data. Every read and write is scoped to the
 *    `patient_id` the auth middleware put on the request — never to an id
 *    supplied by the caller. `facility_id` is never read from input either: the
 *    lat/lng below is a search *origin* the patient chose, not a facility
 *    identity, and the requested facility is a public directory listing looked
 *    up by its own id.
 */
class MobileBloodController extends Controller
{
    /** Ceiling on the search radius, so a "nearby" search stays nearby. */
    public const MAX_RADIUS_KM = 200;

    public function __construct(
        private readonly BloodAvailabilitySearchService $search,
        private readonly BloodRequestService $requests,
    ) {
    }

    /**
     * GET /api/mobile/blood/options
     *
     * The chip vocabularies for the finder: blood groups (with how many
     * facilities currently report each as available) and component types.
     */
    public function options(): JsonResponse
    {
        // Same provenance gate the search itself applies
        // (BloodAvailabilitySearchService). A chip that promises "12
        // facilities" and a search that then returns three is a worse lie than
        // either alone — the count and the result set must be the same query.
        $groupCounts = BloodAvailability::query()
            ->available()
            ->reportedByRealSource()
            ->selectRaw('blood_group, COUNT(DISTINCT facility_id) AS total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        $componentCounts = BloodAvailability::query()
            ->available()
            ->reportedByRealSource()
            ->selectRaw('component_type, COUNT(DISTINCT facility_id) AS total')
            ->groupBy('component_type')
            ->pluck('total', 'component_type');

        return response()->json([
            'data' => [
                'blood_groups' => array_map(static fn (BloodGroup $g) => [
                    'value'             => $g->value,
                    'label'             => $g->label(),
                    'can_receive_from'  => $g->canReceiveFrom(),
                    'facility_count'    => (int) ($groupCounts[$g->value] ?? 0),
                ], BloodGroup::cases()),
                'component_types' => array_map(static fn (BloodComponentType $c) => [
                    'value'          => $c->value,
                    'label'          => $c->label(),
                    'icon'           => $c->iconKey(),
                    'facility_count' => (int) ($componentCounts[$c->value] ?? 0),
                ], BloodComponentType::cases()),
                'urgencies' => array_map(static fn (BloodRequestUrgency $u) => [
                    'value' => $u->value,
                    'label' => $u->label(),
                ], BloodRequestUrgency::cases()),
                'max_units' => BloodRequestService::MAX_UNITS,
            ],
        ]);
    }

    /**
     * GET /api/mobile/blood/search
     * Query: ?blood_group=O- &component_type=whole_blood &lat=4.0511 &lng=9.7679 &radius_km=25
     *
     * lat/lng are the patient's own chosen search origin (a device fix or a
     * picked city). Omitting both widens the search nationwide, which matters
     * for a rare group — the underlying service already handles a null origin.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'blood_group'    => ['required', 'string', Rule::in(BloodGroup::values())],
            'component_type' => ['nullable', 'string', Rule::in(BloodComponentType::values())],
            'lat'            => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng'            => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'radius_km'      => ['nullable', 'numeric', 'min:1', 'max:' . self::MAX_RADIUS_KM],
            'limit'          => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $bloodGroup = BloodGroup::from($validated['blood_group']);
        $component  = BloodComponentType::from(
            $validated['component_type'] ?? BloodComponentType::WholeBlood->value,
        );
        $radiusKm = (float) ($validated['radius_km'] ?? 25);
        $limit    = (int) ($validated['limit'] ?? 25);

        $facilities = $this->search->searchBlood(
            $bloodGroup->value,
            $component->value,
            isset($validated['lat']) ? (float) $validated['lat'] : null,
            isset($validated['lng']) ? (float) $validated['lng'] : null,
            $radiusKm,
        );

        // The shared search service does not know about listing state (it also
        // serves the staff/public directory), so a patient-facing list filters
        // out anything not publicly listed before it is rendered.
        $rows = array_values(array_filter(
            $facilities,
            static fn (CareFacility $facility) => $facility->listing_status === 'active',
        ));

        return response()->json([
            'data' => array_map(
                fn (CareFacility $facility) => $this->facilityPayload($facility),
                array_slice($rows, 0, $limit),
            ),
            'meta' => [
                'total'          => count($rows),
                'blood_group'    => $bloodGroup->value,
                'component_type' => $component->value,
                'radius_km'      => $radiusKm,
                'has_origin'     => isset($validated['lat']),
            ],
        ]);
    }

    /**
     * GET /api/mobile/blood/requests
     * Query: ?scope=open|all
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = (string) $request->attributes->get('patient_id');

        $query = BloodRequest::query()
            ->where('patient_id', $patientId)
            ->with('careFacility');

        if ($request->query('scope', 'all') === 'open') {
            $query->open();
        }

        $rows = $query->orderByDesc('created_at')->limit(50)->get();

        return response()->json([
            'data' => $rows->map(fn (BloodRequest $r) => $this->requestPayload($r))->all(),
        ]);
    }

    /**
     * POST /api/mobile/blood/requests
     * Body: { care_facility_id, blood_group, component_type?, quantity?, urgency?, contact_phone?, note?, needed_by? }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'care_facility_id' => ['required', 'uuid'],
            'blood_group'      => ['required', 'string', Rule::in(BloodGroup::values())],
            'component_type'   => ['nullable', 'string', Rule::in(BloodComponentType::values())],
            'quantity'         => ['nullable', 'integer', 'min:1', 'max:' . BloodRequestService::MAX_UNITS],
            'urgency'          => ['nullable', 'string', Rule::in(BloodRequestUrgency::values())],
            'contact_phone'    => ['nullable', 'string', 'max:32'],
            'note'             => ['nullable', 'string', 'max:500'],
            'needed_by'        => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $patientId = (string) $request->attributes->get('patient_id');

        $facility = CareFacility::query()
            ->where('listing_status', 'active')
            ->find($validated['care_facility_id']);

        if (! $facility) {
            return response()->json([
                'message'    => 'Facility not found.',
                'error_code' => 'FACILITY_NOT_FOUND',
            ], 404);
        }

        try {
            $bloodRequest = $this->requests->request(
                patientId:     $patientId,
                facility:      $facility,
                bloodGroup:    BloodGroup::from($validated['blood_group']),
                componentType: BloodComponentType::from(
                    $validated['component_type'] ?? BloodComponentType::WholeBlood->value,
                ),
                quantity:      (int) ($validated['quantity'] ?? 1),
                urgency:       BloodRequestUrgency::from(
                    $validated['urgency'] ?? BloodRequestUrgency::Routine->value,
                ),
                contactPhone:  $validated['contact_phone'] ?? null,
                patientNote:   $validated['note'] ?? null,
                neededBy:      $validated['needed_by'] ?? null,
            );
        } catch (RuntimeException $e) {
            return $this->requestError($e->getMessage());
        }

        $bloodRequest->load('careFacility');

        return response()->json(['data' => $this->requestPayload($bloodRequest)], 201);
    }

    /**
     * POST /api/mobile/blood/requests/{id}/cancel
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        $patientId = (string) $request->attributes->get('patient_id');

        // Scoped to the authenticated patient: another patient's request is
        // simply not found, never merely forbidden.
        $bloodRequest = BloodRequest::query()
            ->where('patient_id', $patientId)
            ->find($id);

        if (! $bloodRequest) {
            return response()->json([
                'message'    => 'Request not found.',
                'error_code' => 'REQUEST_NOT_FOUND',
            ], 404);
        }

        try {
            $bloodRequest = $this->requests->cancel($bloodRequest, $request->input('reason'));
        } catch (RuntimeException $e) {
            return $this->requestError($e->getMessage());
        }

        $bloodRequest->load('careFacility');

        return response()->json(['data' => $this->requestPayload($bloodRequest)]);
    }

    // ── Payload builders ────────────────────────────────────────────────────

    /**
     * The search service hangs the matched availability row on the facility as
     * `matched_blood` and the computed distance as `distance`.
     *
     * @return array<string,mixed>
     */
    private function facilityPayload(CareFacility $facility): array
    {
        /** @var BloodAvailability|null $availability */
        $availability = $facility->matched_blood ?? null;
        $component    = $availability
            ? BloodComponentType::tryFrom((string) $availability->component_type)
            : null;

        return [
            'id'                  => $facility->id,
            'name'                => $facility->facility_name,
            'facility_type'       => $facility->facility_type,
            'city'                => $facility->city,
            'region'              => $facility->region,
            'address'             => $facility->address,
            'latitude'            => $facility->latitude === null ? null : (float) $facility->latitude,
            'longitude'           => $facility->longitude === null ? null : (float) $facility->longitude,
            'phone'               => $facility->phone_primary,
            'emergency_contact'   => $availability?->emergency_contact ?? $facility->emergency_contact,
            'verification_status' => $facility->verification_status,
            'distance_km'         => $facility->distance === null ? null : round((float) $facility->distance, 1),
            'availability'        => $availability === null ? null : [
                'id'               => $availability->id,
                'blood_group'      => $availability->blood_group,
                'component_type'   => $availability->component_type,
                'component_label'  => $component?->label(),
                'units_range'      => $availability->units_available_range,
                'status'           => $availability->availability_status,
                'freshness'        => $availability->freshness_status,
                'last_updated_at'  => $availability->last_updated_at?->toIso8601String(),
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function requestPayload(BloodRequest $request): array
    {
        return [
            'id'              => $request->id,
            'reference'       => $request->reference,
            'status'          => $request->status->value,
            'status_label'    => $request->status->label(),
            'is_open'         => $request->status->isOpen(),
            'is_cancellable'  => $request->status->isCancellableByPatient(),
            'blood_group'     => $request->blood_group->value,
            'component_type'  => $request->component_type->value,
            'component_label' => $request->component_type->label(),
            'quantity'        => $request->quantity,
            'urgency'         => $request->urgency->value,
            'contact_phone'   => $request->contact_phone,
            'patient_note'    => $request->patient_note,
            'facility_note'   => $request->facility_note,
            'needed_by'       => $request->needed_by?->toIso8601String(),
            'expires_at'      => $request->expires_at?->toIso8601String(),
            'created_at'      => $request->created_at?->toIso8601String(),
            'facility'        => $request->careFacility === null ? null : [
                'id'      => $request->careFacility->id,
                'name'    => $request->careFacility->facility_name,
                'city'    => $request->careFacility->city,
                'address' => $request->careFacility->address,
                'phone'   => $request->careFacility->phone_primary,
            ],
        ];
    }

    /** Maps a service-layer failure reason onto an HTTP response. */
    private function requestError(string $code): JsonResponse
    {
        [$status, $message] = match ($code) {
            BloodRequestService::ERR_NOT_AVAILABLE => [
                409, 'This facility is not reporting that blood type as available right now.',
            ],
            BloodRequestService::ERR_TOO_MANY_OPEN => [
                429, 'You already have the maximum number of open blood requests.',
            ],
            BloodRequestService::ERR_DUPLICATE => [
                409, 'You already have an open request for this blood type at this facility.',
            ],
            BloodRequestService::ERR_NOT_CANCELLABLE => [
                409, 'This request can no longer be cancelled.',
            ],
            default => [422, 'The request could not be completed.'],
        };

        return response()->json(['message' => $message, 'error_code' => $code], $status);
    }
}
