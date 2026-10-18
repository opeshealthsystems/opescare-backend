<?php

namespace App\Services\Pharmacy;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\MedicinePharmacyStock;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

/**
 * Read-side of the stock desk's detail pages: one medicine's ledger (the item
 * page), one movement (the adjustment page) and the supplier register.
 *
 * Nothing here writes a movement — PharmacyStockOpsService owns every write
 * and the rules that go with it (expired stock never reaches the finder; the
 * ledger is append-only). This service only arranges what the ledger already
 * holds so a pharmacist can read it in one place.
 *
 * Every query is facility-scoped by the caller's facility id, read from the
 * portal context by the controller. A record of another pharmacy does not
 * exist for this one (404 through findOrFail).
 */
class PharmacyStockDetailService
{
    /** Purchase-order statuses that still expect a delivery. */
    public const OPEN_ORDER_STATUSES = ['approved', 'sent', 'partial'];

    /** Supplier statuses a pharmacist may set from the desk. */
    public const SUPPLIER_STATUSES = ['active', 'inactive'];

    public function __construct(
        private readonly PharmacyStockReportService $stockReports,
    ) {
    }

    // ══════════════════════════════════════════════════════════════════════
    // Item detail
    // ══════════════════════════════════════════════════════════════════════

    public function itemQuery(string $facilityId): Builder
    {
        return InventoryItem::query()->where('facility_id', $facilityId);
    }

    /** Facility's item ids keyed by medicine id — for linking finder rows to the ledger page. */
    public function itemIdsByMedicine(string $facilityId): BaseCollection
    {
        return $this->itemQuery($facilityId)->whereNotNull('medicine_id')->pluck('id', 'medicine_id');
    }

    /**
     * Each batch of the item tagged with its shelf state, grouped by location.
     *
     *   usable    open, and not past its date (counts towards the finder)
     *   expiring  usable, but inside EXPIRING_WITHIN_DAYS
     *   expired   past its date, whatever its status
     *   closed    not active (written off, quarantined, recalled)
     *   empty     holds no packs
     *
     * @return BaseCollection<string, BaseCollection<int, array{batch: StockBatch, state: string, days_left: int|null}>>
     */
    public function batchesByLocation(InventoryItem $item): BaseCollection
    {
        $today = Carbon::today();

        return $item->batches()
            ->with('location')
            ->orderByRaw('expiry_date ASC NULLS LAST')
            ->orderBy('created_at')
            ->get()
            ->map(function (StockBatch $b) use ($today) {
                $daysLeft = $b->expiry_date ? (int) $today->diffInDays($b->expiry_date, false) : null;

                $state = match (true) {
                    $b->quantity_available <= 0                               => 'empty',
                    $b->expiry_date && $b->expiry_date->lt($today)            => 'expired',
                    $b->status !== 'active'                                   => 'closed',
                    $daysLeft !== null && $daysLeft <= PharmacyStockOpsService::EXPIRING_WITHIN_DAYS => 'expiring',
                    default                                                   => 'usable',
                };

                return ['batch' => $b, 'state' => $state, 'days_left' => $daysLeft];
            })
            ->groupBy(fn (array $row) => $row['batch']->location?->name ?? '');
    }

    /** Movements of one item, newest first. */
    public function movementsFor(InventoryItem $item, int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return StockMovement::query()
            ->where('facility_id', $item->facility_id)
            ->where('inventory_item_id', $item->id)
            ->with('batch')
            ->orderByDesc('performed_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Where this medicine stands on the patient finder, and why.
     *
     * @return array{state: string, row: MedicinePharmacyStock|null, issues: list<string>}
     *   state: published | not_reported | synthetic | no_listing | listing_issue
     */
    public function finderStatus(InventoryItem $item): array
    {
        $listing = $this->stockReports->listingFor($item->facility_id);

        if (! $listing) {
            return ['state' => 'no_listing', 'row' => null, 'issues' => $this->stockReports->listingIssues(null)];
        }

        $row = $item->medicine_id
            ? MedicinePharmacyStock::query()
                ->where('care_facility_id', $listing->id)
                ->where('medicine_id', $item->medicine_id)
                ->first()
            : null;

        $issues = $this->stockReports->listingIssues($listing);

        $state = match (true) {
            $row === null                                                                    => 'not_reported',
            $row->source_system === null
                || in_array($row->source_system, MedicinePharmacyStock::SYNTHETIC_SOURCE_SYSTEMS, true) => 'synthetic',
            $issues !== []                                                                   => 'listing_issue',
            default                                                                          => 'published',
        };

        return ['state' => $state, 'row' => $row, 'issues' => $issues];
    }

    /**
     * Suppliers who delivered this item, with their last delivery and the
     * packs received in total — read from the goods receipts, never guessed.
     *
     * @return BaseCollection<int, object{supplier: Supplier, last_delivery: Carbon|null, deliveries: int, packs: int}>
     */
    public function suppliersFor(InventoryItem $item): BaseCollection
    {
        $rows = GoodsReceiptItem::query()
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->where('goods_receipts.facility_id', $item->facility_id)
            ->where('goods_receipt_items.inventory_item_id', $item->id)
            ->whereNotNull('goods_receipts.supplier_id')
            ->groupBy('goods_receipts.supplier_id')
            ->selectRaw('goods_receipts.supplier_id, MAX(goods_receipts.received_date) AS last_delivery, COUNT(DISTINCT goods_receipts.id) AS deliveries, SUM(goods_receipt_items.quantity_received) AS packs')
            ->get();

        $suppliers = Supplier::query()
            ->where('facility_id', $item->facility_id)
            ->whereIn('id', $rows->pluck('supplier_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->filter(fn ($r) => $suppliers->has($r->supplier_id))
            ->map(fn ($r) => (object) [
                'supplier'      => $suppliers->get($r->supplier_id),
                'last_delivery' => $r->last_delivery ? Carbon::parse($r->last_delivery) : null,
                'deliveries'    => (int) $r->deliveries,
                'packs'         => (int) $r->packs,
            ])
            ->sortByDesc(fn ($r) => $r->last_delivery?->timestamp ?? 0)
            ->values();
    }

    // ══════════════════════════════════════════════════════════════════════
    // Adjustment (one movement) detail
    // ══════════════════════════════════════════════════════════════════════

    public function movementQuery(string $facilityId): Builder
    {
        return StockMovement::query()->where('facility_id', $facilityId);
    }

    /**
     * The lot's quantity before and after one movement, replayed from the
     * ledger: the sum of every movement on that lot up to and including this
     * one. Null when the movement has no lot.
     *
     * @return array{0: int, 1: int}|null
     */
    public function beforeAfter(StockMovement $movement): ?array
    {
        if (! $movement->batch_id) {
            return null;
        }

        $at = $movement->performed_at ?? $movement->created_at;

        $after = (int) StockMovement::query()
            ->where('batch_id', $movement->batch_id)
            ->where(function (Builder $q) use ($movement, $at) {
                $q->where(DB::raw('COALESCE(performed_at, created_at)'), '<', $at)
                  ->orWhere(function (Builder $w) use ($movement, $at) {
                      $w->where(DB::raw('COALESCE(performed_at, created_at)'), '=', $at)
                        ->where('created_at', '<=', $movement->created_at);
                  });
            })
            ->sum('quantity');

        return [$after - $movement->quantity, $after];
    }

    // ══════════════════════════════════════════════════════════════════════
    // Suppliers
    // ══════════════════════════════════════════════════════════════════════

    public function supplierQuery(string $facilityId): Builder
    {
        return Supplier::query()->where('facility_id', $facilityId);
    }

    /**
     * The register with, per supplier, the last delivery date, the number of
     * deliveries and the purchase orders still expecting one.
     *
     * @return Collection<int, Supplier>
     */
    public function suppliersIndex(string $facilityId): Collection
    {
        $open = implode(',', array_map(fn ($s) => "'{$s}'", self::OPEN_ORDER_STATUSES));

        return $this->supplierQuery($facilityId)
            ->select('suppliers.*')
            ->selectSub(GoodsReceipt::query()->selectRaw('MAX(received_date)')->whereColumn('supplier_id', 'suppliers.id'), 'last_delivery')
            ->selectSub(GoodsReceipt::query()->selectRaw('COUNT(*)')->whereColumn('supplier_id', 'suppliers.id'), 'deliveries_count')
            ->selectSub(PurchaseOrder::query()->selectRaw('COUNT(*)')->whereColumn('supplier_id', 'suppliers.id')->whereRaw("status IN ({$open})"), 'open_orders_count')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, GoodsReceipt> deliveries from one supplier, newest first */
    public function deliveriesFrom(Supplier $supplier): Collection
    {
        return GoodsReceipt::query()
            ->where('facility_id', $supplier->facility_id)
            ->where('supplier_id', $supplier->id)
            ->withCount('items')
            ->orderByDesc('received_date')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    /**
     * Items one supplier has ever delivered, with packs received and the last
     * delivery of each.
     *
     * @return BaseCollection<int, object{item: InventoryItem, packs: int, last_delivery: Carbon|null}>
     */
    public function itemsFrom(Supplier $supplier): BaseCollection
    {
        $rows = GoodsReceiptItem::query()
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->where('goods_receipts.facility_id', $supplier->facility_id)
            ->where('goods_receipts.supplier_id', $supplier->id)
            ->groupBy('goods_receipt_items.inventory_item_id')
            ->selectRaw('goods_receipt_items.inventory_item_id, SUM(goods_receipt_items.quantity_received) AS packs, MAX(goods_receipts.received_date) AS last_delivery')
            ->get();

        $items = InventoryItem::query()
            ->where('facility_id', $supplier->facility_id)
            ->whereIn('id', $rows->pluck('inventory_item_id'))
            ->with('medicine')
            ->get()
            ->keyBy('id');

        return $rows
            ->filter(fn ($r) => $items->has($r->inventory_item_id))
            ->map(fn ($r) => (object) [
                'item'          => $items->get($r->inventory_item_id),
                'packs'         => (int) $r->packs,
                'last_delivery' => $r->last_delivery ? Carbon::parse($r->last_delivery) : null,
            ])
            ->sortBy(fn ($r) => $r->item->medicine?->name ?? $r->item->name)
            ->values();
    }

    /** @return Collection<int, PurchaseOrder> orders to this supplier still expecting a delivery */
    public function openOrdersFor(Supplier $supplier): Collection
    {
        return PurchaseOrder::query()
            ->where('facility_id', $supplier->facility_id)
            ->where('supplier_id', $supplier->id)
            ->whereIn('status', self::OPEN_ORDER_STATUSES)
            ->withCount('items')
            ->orderByDesc('order_date')
            ->get();
    }

    /** Edit a supplier's card. Status is limited to active/inactive from the desk. */
    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->fill([
            'name'           => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'email'          => $data['email'] ?? null,
            'address'        => $data['address'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'status'         => in_array($data['status'] ?? null, self::SUPPLIER_STATUSES, true) ? $data['status'] : $supplier->status,
        ])->save();

        return $supplier;
    }
}
