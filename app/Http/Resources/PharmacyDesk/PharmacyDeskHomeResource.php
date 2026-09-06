<?php

namespace App\Http\Resources\PharmacyDesk;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * GET /api/mobile/pharmacy-desk/home
 *
 *   { facility: {id, name},
 *     counts: {incoming, preparing, ready, reservations_pending, deliveries_open, low_stock} }
 *
 * Wraps the array PharmacyDeskService::home() builds; the keys are the wire
 * contract the pharmacist app renders its six tiles from, so add to them,
 * never rename them.
 */
class PharmacyDeskHomeResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $home = (array) $this->resource;

        return [
            'facility' => [
                'id'   => $home['facility']['id'] ?? null,
                'name' => $home['facility']['name'] ?? null,
            ],
            'counts' => [
                'incoming'             => (int) ($home['counts']['incoming'] ?? 0),
                'preparing'            => (int) ($home['counts']['preparing'] ?? 0),
                'ready'                => (int) ($home['counts']['ready'] ?? 0),
                'reservations_pending' => (int) ($home['counts']['reservations_pending'] ?? 0),
                'deliveries_open'      => (int) ($home['counts']['deliveries_open'] ?? 0),
                'low_stock'            => (int) ($home['counts']['low_stock'] ?? 0),
            ],
        ];
    }
}
