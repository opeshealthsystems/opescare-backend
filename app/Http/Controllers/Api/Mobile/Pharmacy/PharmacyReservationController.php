<?php

namespace App\Http\Controllers\Api\Mobile\Pharmacy;

use App\Http\Resources\PharmacyDesk\PharmacyDeskReservationResource;
use App\Models\MedicineReservation;
use App\Services\Pharmacy\PharmacyDeskException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pharmacy desk — medicine reservations placed at this pharmacy.
 *
 *   GET  /api/mobile/pharmacy-desk/reservations?status=pending|accepted|ready|collected|dispatched|delivered|cancelled
 *   POST /api/mobile/pharmacy-desk/reservations/{id}/accept
 *   POST /api/mobile/pharmacy-desk/reservations/{id}/ready
 *   POST /api/mobile/pharmacy-desk/reservations/{id}/collected
 *   POST /api/mobile/pharmacy-desk/reservations/{id}/dispatch
 *   POST /api/mobile/pharmacy-desk/reservations/{id}/delivered
 *   POST /api/mobile/pharmacy-desk/reservations/{id}/cancel   {reason}
 *
 * The PATIENT's own list and cancel live at /api/mobile/pharmacy/reservations
 * (MobilePharmacyController) and are untouched by this.
 */
class PharmacyReservationController extends PharmacyDeskBaseController
{
    /** GET /reservations?status= */
    public function index(Request $request): JsonResponse
    {
        $facility = $this->facility($request);
        if ($facility instanceof JsonResponse) {
            return $facility;
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(MedicineReservation::DESK_STATUSES)],
        ]);

        $rows = $this->desk->reservations((string) $facility->id, $validated['status'] ?? null);

        return response()->json([
            'data' => PharmacyDeskReservationResource::collection($rows),
            'meta' => ['total' => $rows->count(), 'status' => $validated['status'] ?? null],
        ]);
    }

    public function accept(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'accept');
    }

    public function ready(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'ready');
    }

    public function collected(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'collected');
    }

    public function dispatch(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'dispatch');
    }

    public function delivered(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, 'delivered');
    }

    /** POST /reservations/{id}/cancel {reason} — the reason is required: a patient is told why. */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        return $this->transition($request, $id, 'cancel', $validated['reason']);
    }

    private function transition(Request $request, string $id, string $action, ?string $reason = null): JsonResponse
    {
        $facility = $this->facility($request);
        if ($facility instanceof JsonResponse) {
            return $facility;
        }

        try {
            $reservation = $this->desk->findReservation((string) $facility->id, $id);
            $reservation = $this->desk->transitionReservation($reservation, $action, $reason, (string) $facility->name);
        } catch (PharmacyDeskException $e) {
            return $this->refuse($e);
        }

        return response()->json([
            'data' => PharmacyDeskReservationResource::make($reservation),
        ]);
    }
}
