<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\MedicineCategory;
use App\Http\Controllers\Controller;
use App\Models\CareFacility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\MedicineReservation;
use App\Models\Prescription;
use App\Modules\Pharmacy\Services\MedicineFinderService;
use App\Modules\Pharmacy\Services\MedicineReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Patient Medicine Finder — catalog search, geolocated pharmacy availability,
 * and medicine reservations.
 *
 * Scoping notes:
 *  - The catalog and stock listings are public reference data (no patient
 *    information), so they carry no ConsentGrant gate.
 *  - Reservations ARE patient data. Every read and write is scoped to the
 *    `patient_id` the auth middleware put on the request — never to an id
 *    supplied by the caller.
 */
class MobilePharmacyController extends Controller
{
    public function __construct(
        private readonly MedicineFinderService $finder,
        private readonly MedicineReservationService $reservations,
    ) {
    }

    /**
     * GET /api/mobile/pharmacy/categories
     *
     * Category chips for the finder, each with the count of active catalog
     * entries so the client never renders an empty category.
     */
    public function categories(): JsonResponse
    {
        $counts = Medicine::query()
            ->active()
            ->selectRaw('category, COUNT(*) AS total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $categories = array_map(static fn (MedicineCategory $c) => [
            'value'         => $c->value,
            'label'         => $c->label(),
            'icon'          => $c->iconKey(),
            'medicine_count' => (int) ($counts[$c->value] ?? 0),
        ], MedicineCategory::cases());

        return response()->json([
            'data' => [
                'total_medicines' => (int) $counts->sum(),
                'categories'      => $categories,
            ],
        ]);
    }

    /**
     * GET /api/mobile/pharmacy/medicines
     * Query: ?q=paracetamol &category=pain_relief &prescription_required=0 &page=1
     */
    public function searchMedicines(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'                     => 'nullable|string|max:120',
            'category'              => ['nullable', 'string', Rule::in(MedicineCategory::values())],
            'prescription_required' => 'nullable|boolean',
            'per_page'              => 'nullable|integer|min:1|max:50',
        ]);

        $query = Medicine::query()->active();

        if (! empty($validated['q'])) {
            $query->matchingTerm($validated['q']);
        }

        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if ($request->has('prescription_required')) {
            $query->where('prescription_required', $request->boolean('prescription_required'));
        }

        $medicines = $query
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 20);

        $summary = $this->finder->availabilitySummary(
            collect($medicines->items())->pluck('id')->all(),
        );

        return response()->json([
            'data' => array_map(
                fn (Medicine $m) => $this->medicinePayload($m, $summary[$m->id] ?? null),
                $medicines->items(),
            ),
            'pagination' => [
                'total'        => $medicines->total(),
                'per_page'     => $medicines->perPage(),
                'current_page' => $medicines->currentPage(),
                'last_page'    => $medicines->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/mobile/pharmacy/medicines/{id}
     */
    public function showMedicine(string $id): JsonResponse
    {
        $medicine = Medicine::query()->active()->findOrFail($id);
        $summary  = $this->finder->availabilitySummary([$medicine->id]);

        return response()->json([
            'data' => $this->medicinePayload($medicine, $summary[$medicine->id] ?? null),
        ]);
    }

    /**
     * GET /api/mobile/pharmacy/nearby
     * Query: ?lat=4.0511 &lng=9.7679 &radius_km=5 &medicine_id=<uuid> &only_stocking=1
     *
     * The coordinates are the *patient's own* search location supplied by the
     * client (a device fix or a chosen city) — they identify a place to search
     * from, never a facility identity, so nothing here weakens the rule that
     * facility_id comes only from auth middleware.
     */
    public function nearbyPharmacies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat'           => 'required|numeric|between:-90,90',
            'lng'           => 'required|numeric|between:-180,180',
            'radius_km'     => 'nullable|numeric|min:0.5|max:' . MedicineFinderService::MAX_RADIUS_KM,
            'medicine_id'   => 'nullable|uuid',
            'only_stocking' => 'nullable|boolean',
            'limit'         => 'nullable|integer|min:1|max:50',
        ]);

        $medicine = null;
        if (! empty($validated['medicine_id'])) {
            $medicine = Medicine::query()->active()->find($validated['medicine_id']);
            if (! $medicine) {
                return response()->json([
                    'message'    => 'Medicine not found.',
                    'error_code' => 'MEDICINE_NOT_FOUND',
                ], 404);
            }
        }

        $rows = $this->finder->nearbyPharmacies(
            latitude:     (float) $validated['lat'],
            longitude:    (float) $validated['lng'],
            radiusKm:     (float) ($validated['radius_km'] ?? 5),
            medicine:     $medicine,
            onlyStocking: $request->boolean('only_stocking'),
            limit:        (int) ($validated['limit'] ?? 25),
        );

        return response()->json([
            'data' => $rows->map(fn (array $row) => $this->pharmacyPayload(
                $row['facility'],
                $row['distance_km'],
                $row['stock'] ?? null,
            ))->all(),
            'meta' => [
                'total'       => $rows->count(),
                'radius_km'   => (float) ($validated['radius_km'] ?? 5),
                'medicine_id' => $medicine?->id,
            ],
        ]);
    }

    /**
     * GET /api/mobile/pharmacy/reservations
     * Query: ?scope=open|all
     */
    public function listReservations(Request $request): JsonResponse
    {
        $patientId = (string) $request->attributes->get('patient_id');

        $query = MedicineReservation::query()
            ->where('patient_id', $patientId)
            ->with(['medicine', 'careFacility']);

        if ($request->query('scope', 'all') === 'open') {
            $query->open();
        }

        $reservations = $query->orderByDesc('created_at')->limit(50)->get();

        return response()->json([
            'data' => $reservations->map(fn (MedicineReservation $r) => $this->reservationPayload($r))->all(),
        ]);
    }

    /**
     * POST /api/mobile/pharmacy/reservations
     * Body: { medicine_id, care_facility_id, quantity, pack_size?, note? }
     */
    public function reserve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medicine_id'      => 'required|uuid',
            'care_facility_id' => 'required|uuid',
            'quantity'         => 'nullable|integer|min:1|max:' . MedicineReservationService::MAX_QUANTITY,
            'pack_size'        => 'nullable|string|max:60',
            'note'             => 'nullable|string|max:500',
            'prescription_id'  => 'nullable|uuid',
        ]);

        $patientId = (string) $request->attributes->get('patient_id');

        $medicine = Medicine::query()->active()->find($validated['medicine_id']);
        if (! $medicine) {
            return response()->json([
                'message'    => 'Medicine not found.',
                'error_code' => 'MEDICINE_NOT_FOUND',
            ], 404);
        }

        $pharmacy = CareFacility::query()
            ->where('listing_status', 'active')
            ->where('facility_type', 'pharmacy')
            ->find($validated['care_facility_id']);

        if (! $pharmacy) {
            return response()->json([
                'message'    => 'Pharmacy not found.',
                'error_code' => 'PHARMACY_NOT_FOUND',
            ], 404);
        }

        // A prescription may only ever be attached by the patient who owns it —
        // resolved against the authenticated patient_id, never trusted from the body.
        $prescriptionId = null;
        if (! empty($validated['prescription_id'])) {
            $prescriptionId = Prescription::query()
                ->where('id', $validated['prescription_id'])
                ->where('patient_id', $patientId)
                ->value('id');

            if (! $prescriptionId) {
                return response()->json([
                    'message'    => 'Prescription not found.',
                    'error_code' => 'PRESCRIPTION_NOT_FOUND',
                ], 404);
            }
        }

        try {
            $reservation = $this->reservations->reserve(
                patientId:      $patientId,
                medicine:       $medicine,
                pharmacy:       $pharmacy,
                quantity:       (int) ($validated['quantity'] ?? 1),
                packSize:       $validated['pack_size'] ?? null,
                patientNote:    $validated['note'] ?? null,
                prescriptionId: $prescriptionId,
            );
        } catch (RuntimeException $e) {
            return $this->reservationError($e->getMessage());
        }

        $reservation->load(['medicine', 'careFacility']);

        return response()->json(['data' => $this->reservationPayload($reservation)], 201);
    }

    /**
     * POST /api/mobile/pharmacy/reservations/{id}/cancel
     */
    public function cancelReservation(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:255']);

        $patientId = (string) $request->attributes->get('patient_id');

        // Scoped to the authenticated patient: another patient's reservation is
        // simply not found, never merely forbidden.
        $reservation = MedicineReservation::query()
            ->where('patient_id', $patientId)
            ->find($id);

        if (! $reservation) {
            return response()->json([
                'message'    => 'Reservation not found.',
                'error_code' => 'RESERVATION_NOT_FOUND',
            ], 404);
        }

        try {
            $reservation = $this->reservations->cancel($reservation, $request->input('reason'));
        } catch (RuntimeException $e) {
            return $this->reservationError($e->getMessage());
        }

        $reservation->load(['medicine', 'careFacility']);

        return response()->json(['data' => $this->reservationPayload($reservation)]);
    }

    // ── Payload builders ────────────────────────────────────────────────────

    /**
     * @param  array{pharmacy_count:int, price_min:?float, price_max:?float, currency:string}|null  $availability
     * @return array<string,mixed>
     */
    private function medicinePayload(Medicine $medicine, ?array $availability): array
    {
        return [
            'id'                    => $medicine->id,
            'name'                  => $medicine->name,
            'generic_name'          => $medicine->generic_name,
            'brand_name'            => $medicine->brand_name,
            'strength'              => $medicine->strength,
            'form'                  => $medicine->form,
            'category'              => $medicine->category->value,
            'category_label'        => $medicine->category->label(),
            'category_icon'         => $medicine->category->iconKey(),
            'description'           => $medicine->description,
            'indications'           => $medicine->indications ?? [],
            'prescription_required' => $medicine->prescription_required,
            'is_controlled'         => $medicine->is_controlled,
            'default_pack_size'     => $medicine->default_pack_size,
            'pack_size_options'     => $medicine->pack_size_options ?? [],
            'price_min'             => $medicine->price_min,
            'price_max'             => $medicine->price_max,
            'currency'              => $medicine->currency,
            'availability'          => [
                'pharmacy_count' => $availability['pharmacy_count'] ?? 0,
                'price_min'      => $availability['price_min'] ?? $medicine->price_min,
                'price_max'      => $availability['price_max'] ?? $medicine->price_max,
                'currency'       => $availability['currency'] ?? $medicine->currency,
                'is_available'   => ($availability['pharmacy_count'] ?? 0) > 0,
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function pharmacyPayload(
        CareFacility $facility,
        float $distanceKm,
        ?MedicinePharmacyStock $stock,
    ): array {
        $opening = $this->finder->openingState($facility);

        return [
            'id'          => $facility->id,
            'name'        => $facility->facility_name,
            'city'        => $facility->city,
            'region'      => $facility->region,
            'address'     => $facility->address,
            'latitude'    => $facility->latitude === null ? null : (float) $facility->latitude,
            'longitude'   => $facility->longitude === null ? null : (float) $facility->longitude,
            'phone'       => $facility->phone_primary,
            'verification_status' => $facility->verification_status,
            'distance_km' => $distanceKm,
            'is_open'     => $opening['is_open'],
            'opens_at'    => $opening['opens_at'],
            'closes_at'   => $opening['closes_at'],
            'is_24_hours' => $opening['is_24_hours'],
            'stock'       => $stock === null ? null : [
                'status'              => $stock->stock_status->value,
                'status_label'        => $stock->stock_status->label(),
                'is_available'        => $stock->stock_status->isAvailable(),
                'packs_available'     => $stock->packs_available,
                'pack_size'           => $stock->pack_size,
                'unit_price'          => $stock->unit_price,
                'currency'            => $stock->currency,
                'reservation_enabled' => $stock->isReservable(),
                'last_reported_at'    => $stock->last_reported_at?->toIso8601String(),
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function reservationPayload(MedicineReservation $reservation): array
    {
        return [
            'id'           => $reservation->id,
            'reference'    => $reservation->reference,
            'status'       => $reservation->status->value,
            'status_label' => $reservation->status->label(),
            'is_open'      => $reservation->status->isOpen(),
            'is_cancellable' => $reservation->status->isCancellableByPatient(),
            'quantity'     => $reservation->quantity,
            'pack_size'    => $reservation->pack_size,
            'unit_price'   => $reservation->unit_price,
            'total_price'  => $reservation->total_price,
            'currency'     => $reservation->currency,
            'prescription_id' => $reservation->prescription_id,
            'patient_note' => $reservation->patient_note,
            'pharmacy_note' => $reservation->pharmacy_note,
            'expires_at'   => $reservation->expires_at?->toIso8601String(),
            'created_at'   => $reservation->created_at?->toIso8601String(),
            'medicine'     => $reservation->medicine === null ? null : [
                'id'           => $reservation->medicine->id,
                'name'         => $reservation->medicine->name,
                'generic_name' => $reservation->medicine->generic_name,
                'strength'     => $reservation->medicine->strength,
                'form'         => $reservation->medicine->form,
            ],
            'pharmacy'     => $reservation->careFacility === null ? null : [
                'id'      => $reservation->careFacility->id,
                'name'    => $reservation->careFacility->facility_name,
                'city'    => $reservation->careFacility->city,
                'address' => $reservation->careFacility->address,
                'phone'   => $reservation->careFacility->phone_primary,
            ],
        ];
    }

    /** Maps a service-layer failure reason onto an HTTP response. */
    private function reservationError(string $code): JsonResponse
    {
        [$status, $message] = match ($code) {
            MedicineReservationService::ERR_NOT_RESERVABLE => [
                409, 'This pharmacy cannot hold that medicine right now.',
            ],
            MedicineReservationService::ERR_TOO_MANY_OPEN => [
                429, 'You already have the maximum number of open reservations.',
            ],
            MedicineReservationService::ERR_DUPLICATE => [
                409, 'You already have an open reservation for this medicine at this pharmacy.',
            ],
            MedicineReservationService::ERR_NOT_CANCELLABLE => [
                409, 'This reservation can no longer be cancelled.',
            ],
            MedicineReservationService::ERR_PRESCRIPTION_REQUIRED => [
                422, 'This medicine requires a prescription. Attach one of your prescriptions to reserve it.',
            ],
            default => [422, 'The reservation could not be completed.'],
        };

        return response()->json(['message' => $message, 'error_code' => $code], $status);
    }
}
