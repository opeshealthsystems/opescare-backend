<?php

namespace App\Services\Pharmacy;

use App\Models\InventoryItem;
use App\Models\InventoryPriceChange;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The pharmacy price list — one selling price per SKU, in WHOLE FRANCS.
 *
 * XAF has no minor unit (App\Services\Billing\Xaf), so a price is an integer
 * from 0 to MAX_PRICE_XAF and nothing else is accepted. Every change appends
 * a row to `inventory_price_changes` (old → new, by whom, why) that is never
 * edited or removed, then republishes the medicine's finder row so the price
 * a patient sees is the price the pharmacy set — the ledger owns it now, and
 * a goods receipt no longer publishes a medicine without one.
 */
class PharmacyPriceService
{
    public const MAX_PRICE_XAF = 99_999_999;

    public function __construct(
        private readonly PharmacyStockOpsService $ops,
    ) {
    }

    /**
     * @throws InvalidArgumentException when the price is not a whole franc amount in range
     */
    public function setPrice(InventoryItem $item, int $priceXaf, ?string $actorId = null, ?string $reason = null): InventoryPriceChange
    {
        if ($priceXaf < 0 || $priceXaf > self::MAX_PRICE_XAF) {
            throw new InvalidArgumentException('A selling price is a whole franc amount between 0 and ' . self::MAX_PRICE_XAF . '.');
        }

        $reason = trim((string) $reason);

        return DB::transaction(function () use ($item, $priceXaf, $actorId, $reason) {
            $old = $item->selling_price_xaf;

            $item->forceFill(['selling_price_xaf' => $priceXaf])->save();

            $change = InventoryPriceChange::create([
                'facility_id'       => $item->facility_id,
                'inventory_item_id' => $item->id,
                'old_price_xaf'     => $old,
                'new_price_xaf'     => $priceXaf,
                'changed_by'        => $actorId,
                'reason'            => $reason !== '' ? mb_substr($reason, 0, 120) : null,
            ]);

            $this->ops->syncFinder($item, $actorId);

            return $change;
        });
    }
}
