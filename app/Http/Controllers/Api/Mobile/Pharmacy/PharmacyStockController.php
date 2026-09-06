<?php

namespace App\Http\Controllers\Api\Mobile\Pharmacy;

use App\Http\Resources\PharmacyDesk\PharmacyDeskStockResource;
use App\Services\Pharmacy\PharmacyDeskException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pharmacy desk — the public stock listing, kept right from the phone.
 *
 *   GET  /api/mobile/pharmacy-desk/stock?q=&low=1
 *   POST /api/mobile/pharmacy-desk/stock/{id}/adjust   {delta, reason}
 *
 * Behind `capability:pharmacy_stock` — the pharmacy roles plus
 * `medicine_stock`, the stock-only account that may not dispense.
 */
class PharmacyStockController extends PharmacyDeskBaseController
{
    /** GET /stock?q=&low=1 */
    public function index(Request $request): JsonResponse
    {
        $facility = $this->facility($request);
        if ($facility instanceof JsonResponse) {
            return $facility;
        }

        $validated = $request->validate([
            'q'   => ['nullable', 'string', 'max:120'],
            'low' => ['nullable', 'boolean'],
        ]);

        $rows = $this->desk->stock((string) $facility->id, $validated['q'] ?? null, $request->boolean('low'));

        return response()->json([
            'data' => PharmacyDeskStockResource::collection($rows),
            'meta' => ['total' => $rows->count(), 'low' => $request->boolean('low'), 'q' => $validated['q'] ?? null],
        ]);
    }

    /**
     * POST /stock/{id}/adjust {delta, reason}
     *
     * `delta` is signed and never zero; `reason` is required because the
     * ledger entry is only worth having if it says why.
     */
    public function adjust(Request $request, string $id): JsonResponse
    {
        $facility = $this->facility($request);
        if ($facility instanceof JsonResponse) {
            return $facility;
        }

        $validated = $request->validate([
            'delta'  => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        try {
            $stock = $this->desk->findStock((string) $facility->id, $id);
            $stock = $this->desk->adjustStock(
                $facility,
                $stock,
                (int) $validated['delta'],
                $validated['reason'],
                $this->actorId($request),
            );
        } catch (PharmacyDeskException $e) {
            return $this->refuse($e);
        }

        return response()->json([
            'data' => PharmacyDeskStockResource::make($stock),
        ]);
    }
}
