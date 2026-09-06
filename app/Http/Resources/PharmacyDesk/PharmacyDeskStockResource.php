<?php

namespace App\Http\Resources\PharmacyDesk;

use App\Http\Resources\ApiResource;
use App\Models\MedicinePharmacyStock;
use Illuminate\Http\Request;

/**
 * One line of the pharmacy's public stock listing, for the desk.
 *
 *   { id, medicine_id, name, strength, form, quantity, reorder_level,
 *     unit_price, currency, updated_at }
 *
 * `quantity` is `packs_available` — the number a patient searching nearby is
 * told — and `reorder_level` the threshold under which the line counts as
 * low (the pharmacy's own, else the platform default). `stock_status` and
 * `is_low` are additive so the app never has to re-derive them.
 */
class PharmacyDeskStockResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var MedicinePharmacyStock $s */
        $s = $this->resource;

        $medicine = $s->relationLoaded('medicine') ? $s->medicine : null;

        return [
            'id'                  => $s->id,
            'medicine_id'         => $s->medicine_id,
            'name'                => $medicine?->name,
            'generic_name'        => $medicine?->generic_name,
            'strength'            => $medicine?->strength,
            'form'                => $medicine?->form,
            'quantity'            => $s->packs_available,
            'reorder_level'       => $s->effectiveReorderLevel(),
            'pack_size'           => $s->pack_size,
            'unit_price'          => $s->unit_price,
            'currency'            => $s->currency,
            'stock_status'        => $s->stock_status?->value,
            'is_low'              => $s->isLow(),
            'reservation_enabled' => (bool) $s->reservation_enabled,
            'last_reported_at'    => $s->last_reported_at?->toIso8601String(),
            'updated_at'          => $s->updated_at?->toIso8601String(),
        ];
    }
}
