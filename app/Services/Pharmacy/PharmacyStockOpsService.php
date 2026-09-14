<?php

namespace App\Services\Pharmacy;

use App\Enums\PharmacyStockStatus;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ReorderRule;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The pharmacy's own ledger: lots, expiry dates, every quantity change with
 * its reason, minimum levels and deliveries.
 *
 * ── What already existed and what this sits on ──────────────────────────
 * The supply-chain tables (`inventory_items`, `stock_locations`,
 * `stock_batches`, `stock_movements`, `goods_receipts`, `reorder_rules`,
 * `suppliers`, `purchase_orders`) shipped in 2026_05_27_000008 with models
 * and no screen. This service is their first real writer for a pharmacy.
 * It does not touch `pharmacy_inventories` (the dashboard counters and the
 * controlled register) and it does not replace ControlledSubstanceService —
 * controlled drugs keep their own witnessed register.
 *
 * ── The one rule that matters: EXPIRED STOCK IS NOT AVAILABLE ────────────
 * `medicine_pharmacy_stocks` is the row the patient Medicine Finder reads.
 * After every movement, syncFinder() republishes the medicine's USABLE
 * quantity there — the sum over batches that are `active` and either carry
 * no expiry date or expire today or later. A batch past its expiry date
 * contributes nothing to what a patient is told, whether or not anyone has
 * remembered to write it off. The status is derived from that quantity and
 * the pharmacy's own minimum level.
 *
 * A batch with NO expiry date is unknown, not fine: it still counts as
 * usable (the pharmacy has the packs), but batches() lists it separately so
 * the manager sees which lots have never had a date recorded. Nothing here
 * ever guesses a date.
 *
 * ── The ledger is append-only ────────────────────────────────────────────
 * Every quantity change is one StockMovement row with a reason and an actor.
 * StockMovement refuses updates and deletes at the model; this service has
 * no method that edits one. A wrong entry is corrected by another entry.
 */
class PharmacyStockOpsService
{
    /** `stock_movements.reference_type` for a movement recorded from the adjustments screen. */
    public const REFERENCE_ADJUSTMENT = 'pharmacy_stock_ops';

    /** `stock_movements.reference_type` for a line received on a goods receipt. */
    public const REFERENCE_RECEIPT = 'goods_receipt';

    /** Days ahead that count as "expiring soon" on the batches screen. */
    public const EXPIRING_WITHIN_DAYS = 90;

    /**
     * The reasons a quantity may change, and what each does to the batch.
     *
     *   received          more of an existing lot arrived outside a receipt
     *   returned          packs came back from a ward or a patient, unopened
     *   dispensed         handed over the counter
     *   damaged           broken, wet, unsellable — written off
     *   expired           past its date — written off, and the lot is closed
     *   count_correction  the shelf count disagrees with the ledger; signed
     *
     * @var array<string, array{type: string, direction: int}>
     */
    public const REASONS = [
        'received'         => ['type' => 'receipt',    'direction' => +1],
        'returned'         => ['type' => 'return',     'direction' => +1],
        'dispensed'        => ['type' => 'dispense',   'direction' => -1],
        'damaged'          => ['type' => 'write_off',  'direction' => -1],
        'expired'          => ['type' => 'write_off',  'direction' => -1],
        'count_correction' => ['type' => 'adjustment', 'direction' => 0],
    ];

    public function __construct(
        private readonly PharmacyStockReportService $stockReports,
    ) {
    }

    // ══════════════════════════════════════════════════════════════════════
    // Reading
    // ══════════════════════════════════════════════════════════════════════

    /** Batches for the facility, only those with a medicine behind them. */
    public function batchQuery(string $facilityId): Builder
    {
        return StockBatch::query()
            ->where('stock_batches.facility_id', $facilityId)
            ->whereHas('item', fn (Builder $q) => $q->whereNotNull('medicine_id'));
    }

    /**
     * A batch that may be counted towards what a patient is told: still open,
     * and not past its expiry date. No date is not the same as a good date —
     * it is kept usable because the packs exist, and surfaced as unknown.
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->where('stock_batches.status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('stock_batches.expiry_date')
                  ->orWhereDate('stock_batches.expiry_date', '>=', Carbon::today());
            });
    }

    /** Usable packs of one item, as the finder will be told. */
    public function usableQuantity(InventoryItem $item): int
    {
        return (int) $this->scopeUsable($item->batches()->getQuery())->sum('quantity_available');
    }

    /** @return Collection<int, StockBatch> expiring within EXPIRING_WITHIN_DAYS, soonest first */
    public function expiringSoon(string $facilityId): Collection
    {
        return $this->batchQuery($facilityId)
            ->with('item.medicine')
            ->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '>=', Carbon::today())
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays(self::EXPIRING_WITHIN_DAYS))
            ->orderBy('expiry_date')
            ->get();
    }

    /** @return Collection<int, StockBatch> past their date and still holding packs, regardless of status */
    public function expired(string $facilityId): Collection
    {
        return $this->batchQuery($facilityId)
            ->with('item.medicine')
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '<', Carbon::today())
            ->orderBy('expiry_date')
            ->get();
    }

    /** @return Collection<int, StockBatch> open lots nobody has dated */
    public function expiryUnknown(string $facilityId): Collection
    {
        return $this->batchQuery($facilityId)
            ->with('item.medicine')
            ->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->whereNull('expiry_date')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Every medicine this pharmacy keeps a ledger for, with its batches and
     * usable total. The usable total is the same arithmetic syncFinder() uses.
     *
     * @return Collection<int, InventoryItem>
     */
    public function itemsWithBatches(string $facilityId): Collection
    {
        return InventoryItem::query()
            ->where('facility_id', $facilityId)
            ->whereNotNull('medicine_id')
            ->with([
                'medicine',
                'batches' => fn ($q) => $q->orderByRaw('expiry_date ASC NULLS LAST')->orderBy('created_at'),
                'reorderRule' => fn ($q) => $q->where('is_active', true),
            ])
            ->get()
            ->sortBy(fn (InventoryItem $i) => $i->medicine?->name ?? $i->name)
            ->values();
    }

    /**
     * Items below their minimum, with the numbers side by side.
     *
     * The usable quantity is computed in SQL with the same predicate as
     * scopeUsable() so the list and the finder can never disagree. Only items
     * with an ACTIVE rule are judged — a medicine with no minimum set is not
     * "below" anything.
     *
     * @return Collection<int, InventoryItem>
     */
    public function belowMinimum(string $facilityId): Collection
    {
        $today = Carbon::today()->toDateString();

        $usable = "(SELECT COALESCE(SUM(b.quantity_available), 0) FROM stock_batches b
                     WHERE b.inventory_item_id = inventory_items.id
                       AND b.status = 'active'
                       AND (b.expiry_date IS NULL OR b.expiry_date >= ?))";

        return InventoryItem::query()
            ->select('inventory_items.*')
            ->selectRaw("{$usable} AS usable_quantity", [$today])
            ->selectRaw('reorder_rules.reorder_level AS rule_level, reorder_rules.reorder_quantity AS rule_quantity')
            ->join('reorder_rules', function ($join) {
                $join->on('reorder_rules.inventory_item_id', '=', 'inventory_items.id')
                     ->where('reorder_rules.is_active', true);
            })
            ->where('inventory_items.facility_id', $facilityId)
            ->whereNotNull('inventory_items.medicine_id')
            ->whereRaw("{$usable} < reorder_rules.reorder_level", [$today])
            ->with('medicine')
            ->orderBy('inventory_items.name')
            ->get();
    }

    /** ATC names for a set of codes, in the reader's language, from the read-only dictionary. */
    public function atcLabels(iterable $codes): array
    {
        $codes = collect($codes)->filter()->unique()->values();

        if ($codes->isEmpty()) {
            return [];
        }

        return \App\Models\ClinicalCodeDictionary::query()
            ->where('system', 'ATC')
            ->whereIn('code', $codes)
            ->get()
            ->mapWithKeys(fn ($row) => [$row->code => $row->label])
            ->all();
    }

    // ══════════════════════════════════════════════════════════════════════
    // Resolving the facility's SKU for a medicine
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The inventory row this pharmacy keeps for a catalogue medicine —
     * created on first use, one per (facility, medicine).
     */
    public function itemFor(string $facilityId, Medicine $medicine, ?string $actorId = null): InventoryItem
    {
        return InventoryItem::query()->firstOrCreate(
            ['facility_id' => $facilityId, 'medicine_id' => $medicine->id],
            [
                'name'         => $medicine->name,
                'category'     => 'medicine',
                'unit'         => 'pack',
                'track_expiry' => true,
                'track_batch'  => true,
                'status'       => 'active',
                'created_by'   => $actorId,
            ],
        );
    }

    /** The pharmacy's shelf — one stock location per facility, made on first use. */
    public function defaultLocation(string $facilityId): StockLocation
    {
        return StockLocation::query()->firstOrCreate(
            ['facility_id' => $facilityId, 'type' => 'pharmacy', 'code' => 'PHARM'],
            ['name' => 'Pharmacy', 'is_active' => true],
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // Writing — every path is one transaction and ends in one StockMovement
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Record one quantity change on one batch, with its reason.
     *
     * `$quantity` is the number of packs (positive) for every reason except
     * count_correction, where it is the SIGNED difference the shelf count
     * showed. The batch is locked for the write; the result may never go
     * below zero (that is a wrong request, not a wrong state).
     *
     * @throws InvalidArgumentException on an unknown reason or a negative result
     */
    public function adjust(StockBatch $batch, string $reason, int $quantity, ?string $note, ?string $actorId): StockMovement
    {
        if (! isset(self::REASONS[$reason])) {
            throw new InvalidArgumentException("Unknown stock movement reason: {$reason}");
        }

        $rule = self::REASONS[$reason];

        return DB::transaction(function () use ($batch, $reason, $rule, $quantity, $note, $actorId) {
            /** @var StockBatch $locked */
            $locked = StockBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();

            $delta = $rule['direction'] === 0 ? $quantity : $rule['direction'] * abs($quantity);

            if ($delta === 0) {
                throw new InvalidArgumentException('A movement of zero packs records nothing.');
            }

            $before = $locked->availableQty();
            $after  = $before + $delta;

            if ($after < 0) {
                throw new InvalidArgumentException("Cannot remove {$quantity} packs from a lot holding {$before}.");
            }

            match ($rule['type']) {
                'receipt', 'return' => $locked->quantity_in  += $delta,
                'dispense', 'write_off' => $locked->quantity_out += -$delta,
                default => $locked->quantity_adjusted += $delta,
            };

            // Writing off a lot as expired closes it: it must never count
            // again, even if a later correction is typed against it.
            if ($reason === 'expired') {
                $locked->status = 'expired';
            }

            $locked->save();

            $movement = StockMovement::create([
                'facility_id'       => $locked->facility_id,
                'inventory_item_id' => $locked->inventory_item_id,
                'batch_id'          => $locked->id,
                'from_location_id'  => $delta < 0 ? $locked->location_id : null,
                'to_location_id'    => $delta > 0 ? $locked->location_id : null,
                'movement_type'     => $rule['type'],
                'quantity'          => $delta,
                'unit_cost'         => $locked->unit_cost,
                'reference_type'    => self::REFERENCE_ADJUSTMENT,
                'reference_id'      => null,
                'reason'            => self::encodeReason($reason, $note),
                'performed_by'      => $actorId,
                'performed_at'      => now(),
            ]);

            $this->syncFinder($locked->item()->firstOrFail(), $actorId);

            return $movement;
        });
    }

    /**
     * Receive a delivery: the receipt, its lines, one batch and one movement
     * per line, and the finder republished — or nothing at all.
     *
     * @param  array{supplier_id?: string|null, purchase_order_id?: string|null, received_date: string, receipt_number?: string|null, notes?: string|null}  $header
     * @param  list<array{medicine_id: string, quantity: int, lot_number?: string|null, expiry_date?: string|null, unit_cost?: int|null}>  $lines
     *
     * @throws InvalidArgumentException when a line names a medicine that cannot be stocked
     */
    public function receiveDelivery(string $facilityId, array $header, array $lines, ?string $actorId): GoodsReceipt
    {
        if ($lines === []) {
            throw new InvalidArgumentException('A delivery with no lines cannot be received.');
        }

        return DB::transaction(function () use ($facilityId, $header, $lines, $actorId) {
            $location = $this->defaultLocation($facilityId);

            // A supplier or purchase order from ANOTHER facility does not
            // exist for this one — the same 404 rule the controller applies.
            $supplier = ! empty($header['supplier_id'])
                ? Supplier::query()->where('facility_id', $facilityId)->findOrFail($header['supplier_id'])
                : null;

            $order = ! empty($header['purchase_order_id'])
                ? PurchaseOrder::query()->where('facility_id', $facilityId)->lockForUpdate()->findOrFail($header['purchase_order_id'])
                : null;

            $receipt = GoodsReceipt::create([
                'facility_id'       => $facilityId,
                'purchase_order_id' => $order?->id,
                'supplier_id'       => $supplier?->id ?? $order?->supplier_id,
                'location_id'       => $location->id,
                'receipt_number'    => filled($header['receipt_number'] ?? null)
                    ? trim((string) $header['receipt_number'])
                    : $this->nextReceiptNumber(),
                'received_date'     => $header['received_date'],
                'received_by'       => $actorId,
                // Verified on entry: the person typing the lines is the person
                // who counted the boxes. There is no second-approver step here.
                'status'            => 'verified',
                'notes'             => $header['notes'] ?? null,
            ]);

            $touched = [];

            foreach ($lines as $line) {
                $medicine = Medicine::query()->active()->find($line['medicine_id']);

                if (! $medicine) {
                    throw new InvalidArgumentException('A line names a medicine that is not in the catalogue.');
                }

                $quantity = (int) $line['quantity'];

                if ($quantity < 1) {
                    throw new InvalidArgumentException('A received line must hold at least one pack.');
                }

                $item = $this->itemFor($facilityId, $medicine, $actorId);
                $cost = isset($line['unit_cost']) && $line['unit_cost'] !== '' && $line['unit_cost'] !== null
                    ? (int) $line['unit_cost']
                    : null;

                $orderLine = $order
                    ? PurchaseOrderItem::query()
                        ->where('purchase_order_id', $order->id)
                        ->where('inventory_item_id', $item->id)
                        ->lockForUpdate()
                        ->first()
                    : null;

                $receiptLine = GoodsReceiptItem::create([
                    'goods_receipt_id'       => $receipt->id,
                    'inventory_item_id'      => $item->id,
                    'purchase_order_item_id' => $orderLine?->id,
                    'quantity_received'      => $quantity,
                    'batch_number'           => $line['lot_number'] ?? null,
                    'expiry_date'            => $line['expiry_date'] ?? null,
                    'unit_cost'              => $cost,
                    'notes'                  => null,
                ]);

                $batch = StockBatch::create([
                    'inventory_item_id' => $item->id,
                    'location_id'       => $location->id,
                    'facility_id'       => $facilityId,
                    'batch_number'      => $line['lot_number'] ?? null,
                    'lot_number'        => $line['lot_number'] ?? null,
                    'expiry_date'       => $line['expiry_date'] ?? null,
                    'quantity_in'       => $quantity,
                    'quantity_out'      => 0,
                    'quantity_adjusted' => 0,
                    'unit_cost'         => $cost,
                    'status'            => 'active',
                    'supplier_id'       => $receipt->supplier_id,
                    'created_by'        => $actorId,
                ]);

                StockMovement::create([
                    'facility_id'       => $facilityId,
                    'inventory_item_id' => $item->id,
                    'batch_id'          => $batch->id,
                    'from_location_id'  => null,
                    'to_location_id'    => $location->id,
                    'movement_type'     => 'receipt',
                    'quantity'          => $quantity,
                    'unit_cost'         => $cost,
                    'reference_type'    => self::REFERENCE_RECEIPT,
                    'reference_id'      => $receipt->id,
                    'reason'            => self::encodeReason('received', $receipt->receipt_number),
                    'performed_by'      => $actorId,
                    'performed_at'      => now(),
                ]);

                if ($orderLine) {
                    $orderLine->quantity_received += $quantity;
                    $orderLine->save();
                }

                $touched[$item->id] = $item;
            }

            if ($order) {
                $outstanding = PurchaseOrderItem::query()
                    ->where('purchase_order_id', $order->id)
                    ->whereColumn('quantity_received', '<', 'quantity_ordered')
                    ->exists();

                $order->status = $outstanding ? 'partial' : 'received';
                $order->save();
            }

            foreach ($touched as $item) {
                $this->syncFinder($item, $actorId);
            }

            return $receipt;
        });
    }

    /** Set (or replace) the minimum level for a medicine. One active rule per item. */
    public function setReorderRule(string $facilityId, Medicine $medicine, int $level, int $quantity, ?string $actorId): ReorderRule
    {
        return DB::transaction(function () use ($facilityId, $medicine, $level, $quantity, $actorId) {
            $item = $this->itemFor($facilityId, $medicine, $actorId);

            // Mirror onto the item's own columns too, so a reader of
            // inventory_items alone sees the same minimum.
            $item->forceFill(['reorder_level' => $level, 'reorder_quantity' => $quantity])->save();

            $rule = ReorderRule::query()->updateOrCreate(
                ['inventory_item_id' => $item->id, 'location_id' => null],
                [
                    'reorder_level'    => $level,
                    'reorder_quantity' => $quantity,
                    'auto_alert'       => true,
                    'is_active'        => true,
                ],
            );

            // The finder's low/in-stock line moves with the minimum.
            $this->syncFinder($item, $actorId);

            return $rule;
        });
    }

    public function createSupplier(string $facilityId, array $data, ?string $actorId): Supplier
    {
        return Supplier::create([
            'facility_id'    => $facilityId,
            'name'           => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'email'          => $data['email'] ?? null,
            'status'         => 'active',
            'created_by'     => $actorId,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Publishing to the patient finder
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Republish one medicine's availability from the ledger.
     *
     * The usable quantity, the status derived from it and — when the price
     * list has one — the selling price (whole francs, PharmacyPriceService)
     * are written. Pack size and the reservation switch stay whatever the
     * pharmacy last declared on the public page — the ledger does not know
     * them — and so does the price while the price list has none.
     * A pharmacy without a linked public listing has no finder row to write,
     * and its ledger still works.
     */
    public function syncFinder(InventoryItem $item, ?string $actorId = null): ?MedicinePharmacyStock
    {
        if (! $item->medicine_id) {
            return null;
        }

        $listing = $this->stockReports->listingFor($item->facility_id);

        if (! $listing) {
            return null;
        }

        $medicine = $item->medicine ?: Medicine::query()->find($item->medicine_id);

        if (! $medicine) {
            return null;
        }

        $existing = MedicinePharmacyStock::query()
            ->where('medicine_id', $medicine->id)
            ->where('care_facility_id', $listing->id)
            ->first();

        $usable = $this->usableQuantity($item);

        $level = ReorderRule::query()
            ->where('inventory_item_id', $item->id)
            ->where('is_active', true)
            ->value('reorder_level')
            ?? $existing?->reorder_level
            ?? MedicinePharmacyStock::DEFAULT_REORDER_LEVEL;

        $status = match (true) {
            $usable <= 0     => PharmacyStockStatus::OutOfStock,
            $usable <= $level => PharmacyStockStatus::LowStock,
            default          => PharmacyStockStatus::InStock,
        };

        $stock = $this->stockReports->report($listing, $medicine, [
            'stock_status'        => $status,
            'packs_available'     => $usable,
            'pack_size'           => $existing?->pack_size,
            'unit_price'          => $item->selling_price_xaf ?? $existing?->unit_price,
            'reservation_enabled' => (bool) ($existing?->reservation_enabled ?? false),
        ], $actorId);

        $this->stockReports->touchListingFreshness($listing);

        return $stock;
    }

    // ══════════════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════════════

    /** `reason` column layout: the reason code, then ` | ` and the free note when there is one. */
    public static function encodeReason(string $code, ?string $note): string
    {
        $note = trim((string) $note);

        return $note === '' ? $code : "{$code} | {$note}";
    }

    /** @return array{0: string, 1: string|null} [code, note] */
    public static function decodeReason(?string $reason): array
    {
        $reason = (string) $reason;
        $parts  = explode(' | ', $reason, 2);
        $code   = trim($parts[0]);

        return [
            isset(self::REASONS[$code]) ? $code : 'other',
            isset($parts[1]) ? trim($parts[1]) : (isset(self::REASONS[$code]) ? null : ($reason !== '' ? $reason : null)),
        ];
    }

    private function nextReceiptNumber(): string
    {
        return 'GR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
    }
}
