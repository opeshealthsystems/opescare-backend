<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Pharmacy\PharmacyStockDetailService;
use App\Services\Pharmacy\PharmacyStockOpsService;
use App\Services\Portal\PortalContextService;
use Illuminate\Contracts\View\View;

/**
 * One medicine's ledger page and one movement's page. Read-only: every write
 * on these screens posts to the existing stock-ops routes (adjustments.store,
 * reorder.store, receipts.create), so the rules in PharmacyStockOpsService are
 * the only rules.
 *
 * The facility is read from PortalContextService only. An item or a movement
 * of another pharmacy does not exist for this one (404).
 */
class PharmacyStockItemController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly PharmacyStockOpsService $ops,
        private readonly PharmacyStockDetailService $detail,
    ) {
    }

    public function show(string $item): View
    {
        $facilityId = $this->facilityId();

        $inventoryItem = $this->detail->itemQuery($facilityId)
            ->with(['medicine', 'reorderRule' => fn ($q) => $q->where('is_active', true)])
            ->findOrFail($item);

        $movements = $this->detail->movementsFor($inventoryItem);

        $actors = User::query()
            ->whereIn('id', $movements->getCollection()->pluck('performed_by')->filter()->unique())
            ->pluck('name', 'id');

        $code = $inventoryItem->medicine?->atc_code;

        return view('portals.pharmacy.stock_ops.item_show', [
            'item'       => $inventoryItem,
            'atcLabel'   => $code ? ($this->ops->atcLabels([$code])[$code] ?? null) : null,
            'usable'     => $this->ops->usableQuantity($inventoryItem),
            'finder'     => $this->detail->finderStatus($inventoryItem),
            'locations'  => $this->detail->batchesByLocation($inventoryItem),
            'movements'  => $movements,
            'actors'     => $actors,
            'rule'       => $inventoryItem->reorderRule->first(),
            'suppliers'  => $this->detail->suppliersFor($inventoryItem),
            'reasons'    => array_keys(PharmacyStockOpsService::REASONS),
            'windowDays' => PharmacyStockOpsService::EXPIRING_WITHIN_DAYS,
        ]);
    }

    public function showAdjustment(string $id): View
    {
        $facilityId = $this->facilityId();

        $movement = $this->detail->movementQuery($facilityId)
            ->with(['item.medicine', 'batch', 'fromLocation', 'toLocation'])
            ->findOrFail($id);

        [$code, $note] = PharmacyStockOpsService::decodeReason($movement->reason);

        return view('portals.pharmacy.stock_ops.adjustment_show', [
            'movement'    => $movement,
            'reasonCode'  => $code,
            'note'        => $note,
            'beforeAfter' => $this->detail->beforeAfter($movement),
            'actor'       => $movement->performed_by ? User::query()->find($movement->performed_by)?->name : null,
        ]);
    }

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('pharmacy_ops.no_facility_context'));
        }

        return $facilityId;
    }
}
