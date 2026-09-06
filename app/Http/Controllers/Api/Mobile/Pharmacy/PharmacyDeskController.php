<?php

namespace App\Http\Controllers\Api\Mobile\Pharmacy;

use App\Http\Resources\PharmacyDesk\PharmacyDeskHomeResource;
use App\Http\Resources\PharmacyDesk\PharmacyDeskPrescriptionResource;
use App\Models\Facility;
use App\Models\Patient;
use App\Services\Pharmacy\PharmacyDeskException;
use App\Services\Pharmacy\PharmacyDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pharmacy desk — home and the prescription bench.
 *
 * Routes (routes/mobile_pharmacy_desk.php), all behind
 * `auth.provider` + `capability:dispense`:
 *
 *   GET  /api/mobile/pharmacy-desk/home
 *   GET  /api/mobile/pharmacy-desk/prescriptions?status=incoming|preparing|ready|dispensed
 *   GET  /api/mobile/pharmacy-desk/prescriptions/lookup?health_id=   (exact)
 *   POST /api/mobile/pharmacy-desk/prescriptions/{id}/accept
 *   POST /api/mobile/pharmacy-desk/prescriptions/{id}/ready
 *   POST /api/mobile/pharmacy-desk/prescriptions/{id}/dispense  {items:[{id, quantity_dispensed?}], notes?}
 *
 * The prefix is `pharmacy-desk`, not `pharmacy`: the PATIENT medicine finder
 * already owns `GET /api/mobile/pharmacy/reservations` and
 * `POST /api/mobile/pharmacy/reservations/{id}/cancel` (routes/api.php), and
 * a second registration of the same URI would silently replace one side
 * with the other.
 */
class PharmacyDeskController extends PharmacyDeskBaseController
{
    /** GET /home */
    public function home(Request $request): JsonResponse
    {
        $facility = $this->facility($request);
        if ($facility instanceof JsonResponse) {
            return $facility;
        }

        return response()->json([
            'data' => PharmacyDeskHomeResource::make($this->desk->home($facility)),
        ]);
    }

    /** GET /prescriptions?status= */
    public function prescriptions(Request $request): JsonResponse
    {
        $facility = $this->facility($request);
        if ($facility instanceof JsonResponse) {
            return $facility;
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(PharmacyDeskService::QUEUE_STATUSES)],
        ]);

        $rows = $this->desk->queue((string) $facility->id, $validated['status'] ?? null);

        return response()->json([
            'data' => PharmacyDeskPrescriptionResource::collection($rows),
            'meta' => ['total' => $rows->count(), 'status' => $validated['status'] ?? null],
        ]);
    }

    /**
     * GET /prescriptions/lookup?health_id=
     *
     * `patient` is additive to the contract: null when the exact Health ID
     * resolves to nobody, so the app can say so — with the identical `data:
     * []` it would get for a patient with nothing to fill.
     */
    public function lookup(Request $request): JsonResponse
    {
        $facility = $this->facility($request);
        if ($facility instanceof JsonResponse) {
            return $facility;
        }

        $validated = $request->validate([
            'health_id' => ['required', 'string', 'max:64'],
        ]);

        $result = $this->desk->lookup((string) $facility->id, $validated['health_id']);

        /** @var Patient|null $patient */
        $patient = $result['patient'];

        return response()->json([
            'data'    => PharmacyDeskPrescriptionResource::collection($result['prescriptions']),
            'patient' => $patient === null ? null : [
                'id'           => $patient->id,
                'health_id'    => $patient->health_id,
                'display_name' => trim($patient->first_name . ' ' . $patient->last_name),
            ],
        ]);
    }

    /** POST /prescriptions/{id}/accept */
    public function accept(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, fn (Facility $facility, $rx) => $this->desk->accept($facility, $rx));
    }

    /** POST /prescriptions/{id}/ready */
    public function ready(Request $request, string $id): JsonResponse
    {
        return $this->transition($request, $id, fn (Facility $facility, $rx) => $this->desk->markReady($facility, $rx));
    }

    /** POST /prescriptions/{id}/dispense  {items:[{id, quantity_dispensed?}], notes?} */
    public function dispense(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'items'                      => ['nullable', 'array', 'max:100'],
            'items.*.id'                 => ['required', 'uuid'],
            'items.*.quantity_dispensed' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'notes'                      => ['nullable', 'string', 'max:1000'],
        ]);

        $actorId = $this->actorId($request);

        return $this->transition(
            $request,
            $id,
            fn (Facility $facility, $rx) => $this->desk->dispense(
                $facility,
                $rx,
                $actorId,
                array_values($validated['items'] ?? []),
                $validated['notes'] ?? null,
            ),
        );
    }

    /** @param  callable(Facility, \App\Models\Prescription): \App\Models\Prescription  $step */
    private function transition(Request $request, string $id, callable $step): JsonResponse
    {
        $facility = $this->facility($request);
        if ($facility instanceof JsonResponse) {
            return $facility;
        }

        try {
            $rx = $this->desk->find($facility, $id);
            $rx = $step($facility, $rx);
        } catch (PharmacyDeskException $e) {
            return $this->refuse($e);
        }

        return response()->json([
            'data' => PharmacyDeskPrescriptionResource::make($rx),
        ]);
    }
}
