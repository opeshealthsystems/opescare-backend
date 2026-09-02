<?php

namespace App\Modules\Inventory\Services;

use App\Models\AuditEvent;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ReorderRule;
use App\Models\StockBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SupplyChainService
{
    // ── Suppliers ─────────────────────────────────────────────

    public function createSupplier(string $facilityId, array $data, string $actorId): Supplier
    {
        $supplier = Supplier::create([
            'facility_id'    => $facilityId,
            'name'           => $data['name'],
            'code'           => $data['code'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'phone'          => $data['phone'] ?? null,
            'email'          => $data['email'] ?? null,
            'address'        => $data['address'] ?? null,
            'tax_id'         => $data['tax_id'] ?? null,
            'status'         => 'active',
            'notes'          => $data['notes'] ?? null,
            'created_by'     => $actorId,
        ]);

        $this->audit('supplier_created', $supplier->id, null, $supplier->toArray(), $actorId, $facilityId);

        return $supplier;
    }

    // ── Inventory Items ───────────────────────────────────────

    public function createItem(string $facilityId, array $data, string $actorId): InventoryItem
    {
        // Prevent duplicate code within facility
        if (!empty($data['code'])) {
            if (InventoryItem::where('facility_id', $facilityId)->where('code', $data['code'])->exists()) {
                throw new RuntimeException("An item with code '{$data['code']}' already exists.");
            }
        }

        $item = InventoryItem::create([
            'facility_id'     => $facilityId,
            'name'            => $data['name'],
            'code'            => $data['code'] ?? null,
            'category'        => $data['category'] ?? 'other',
            'unit'            => $data['unit'] ?? 'unit',
            'reorder_level'   => (int) ($data['reorder_level'] ?? 0),
            'reorder_quantity'=> isset($data['reorder_quantity']) ? (int) $data['reorder_quantity'] : null,
            'track_expiry'    => (bool) ($data['track_expiry'] ?? false),
            'track_batch'     => (bool) ($data['track_batch'] ?? false),
            'unit_cost'       => $data['unit_cost'] ?? null,
            'status'          => 'active',
            'description'     => $data['description'] ?? null,
            'created_by'      => $actorId,
        ]);

        $this->audit('inventory_item_created', $item->id, null, $item->toArray(), $actorId, $facilityId);

        return $item;
    }

    // ── Stock Locations ───────────────────────────────────────

    public function createLocation(string $facilityId, array $data): StockLocation
    {
        return StockLocation::create([
            'facility_id' => $facilityId,
            'name'        => $data['name'],
            'code'        => $data['code'] ?? null,
            'type'        => $data['type'] ?? 'store',
            'is_active'   => true,
        ]);
    }

    // ── Receive Stock Directly (without PO) ──────────────────

    public function receiveStock(string $facilityId, array $data, string $actorId): StockBatch
    {
        return DB::transaction(function () use ($facilityId, $data, $actorId) {
            $item = InventoryItem::where('id', $data['inventory_item_id'])
                ->where('facility_id', $facilityId)
                ->firstOrFail();

            $locationId = $data['location_id']
                ?? StockLocation::where('facility_id', $facilityId)->value('id');

            $batch = StockBatch::create([
                'inventory_item_id' => $item->id,
                'location_id'       => $locationId,
                'facility_id'       => $facilityId,
                'batch_number'      => $data['batch_number'] ?? null,
                'lot_number'        => $data['lot_number'] ?? null,
                'manufacture_date'  => $data['manufacture_date'] ?? null,
                'expiry_date'       => $data['expiry_date'] ?? null,
                'quantity_in'       => (int) $data['quantity'],
                'quantity_out'      => 0,
                'quantity_adjusted' => 0,
                'unit_cost'         => $data['unit_cost'] ?? $item->unit_cost,
                'status'            => 'active',
                'supplier_id'       => $data['supplier_id'] ?? null,
                'created_by'        => $actorId,
            ]);

            StockMovement::create([
                'facility_id'       => $facilityId,
                'inventory_item_id' => $item->id,
                'batch_id'          => $batch->id,
                'to_location_id'    => $locationId,
                'movement_type'     => 'receipt',
                'quantity'          => (int) $data['quantity'],
                'unit_cost'         => $data['unit_cost'] ?? $item->unit_cost,
                'reference_type'    => $data['reference_type'] ?? null,
                'reference_id'      => $data['reference_id'] ?? null,
                'reason'            => $data['reason'] ?? 'Direct stock receipt',
                'performed_by'      => $actorId,
                'performed_at'      => now(),
            ]);

            $this->audit('stock_received', $item->id, null, [
                'item' => $item->name,
                'qty'  => $data['quantity'],
                'batch'=> $batch->id,
            ], $actorId, $facilityId);

            return $batch;
        });
    }

    // ── Stock Adjustment ──────────────────────────────────────

    public function adjustStock(string $facilityId, string $batchId, array $data, string $actorId): StockBatch
    {
        return DB::transaction(function () use ($facilityId, $batchId, $data, $actorId) {
            $batch = StockBatch::where('id', $batchId)
                ->where('facility_id', $facilityId)
                ->lockForUpdate()
                ->firstOrFail();

            $current = $batch->availableQty();
            $newQty   = (int) $data['new_quantity'];
            $diff     = $newQty - $current;

            $batch->increment('quantity_adjusted', $diff);

            StockMovement::create([
                'facility_id'       => $facilityId,
                'inventory_item_id' => $batch->inventory_item_id,
                'batch_id'          => $batch->id,
                'movement_type'     => 'adjustment',
                'quantity'          => abs($diff),
                'reason'            => $data['reason'] ?? 'Stock count correction',
                'performed_by'      => $actorId,
                'performed_at'      => now(),
            ]);

            $this->audit('stock_adjusted', $batch->id, ['qty' => $current], ['qty' => $newQty], $actorId, $facilityId);

            return $batch->fresh();
        });
    }

    // ── Purchase Orders ───────────────────────────────────────

    public function createPurchaseOrder(string $facilityId, array $data, string $actorId): PurchaseOrder
    {
        return DB::transaction(function () use ($facilityId, $data, $actorId) {
            $poNumber = 'PO-' . strtoupper(Str::random(8));

            $po = PurchaseOrder::create([
                'facility_id'            => $facilityId,
                'supplier_id'            => $data['supplier_id'] ?? null,
                'po_number'              => $poNumber,
                'status'                 => 'draft',
                'order_date'             => $data['order_date'] ?? today(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes'                  => $data['notes'] ?? null,
                'created_by'             => $actorId,
            ]);

            $total = 0;
            foreach ($data['items'] ?? [] as $lineItem) {
                $qty   = (int) $lineItem['quantity'];
                $price = (float) ($lineItem['unit_price'] ?? 0);
                $line  = $qty * $price;
                $total += $line;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $lineItem['inventory_item_id'],
                    'quantity_ordered'  => $qty,
                    'quantity_received' => 0,
                    'unit_price'        => $price,
                    'total_price'       => $line,
                    'notes'             => $lineItem['notes'] ?? null,
                ]);
            }

            $po->update(['total_amount' => $total]);

            $this->audit('purchase_order_created', $po->id, null, ['po_number' => $poNumber], $actorId, $facilityId);

            return $po->load('items');
        });
    }

    public function approvePurchaseOrder(PurchaseOrder $po, string $actorId): PurchaseOrder
    {
        if (!in_array($po->status, ['draft', 'submitted'])) {
            throw new RuntimeException('Only draft or submitted purchase orders can be approved.');
        }

        $po->update([
            'status'      => 'approved',
            'approved_by' => $actorId,
            'approved_at' => now(),
        ]);

        $this->audit('purchase_order_approved', $po->id, null, ['status' => 'approved'], $actorId, $po->facility_id);

        return $po->fresh();
    }

    // ── Goods Receipts ────────────────────────────────────────

    public function receiveGoodsReceipt(string $facilityId, array $data, string $actorId): GoodsReceipt
    {
        return DB::transaction(function () use ($facilityId, $data, $actorId) {
            $receiptNumber = 'GR-' . strtoupper(Str::random(8));

            $receipt = GoodsReceipt::create([
                'facility_id'       => $facilityId,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id'       => $data['supplier_id'] ?? null,
                'location_id'       => $data['location_id'] ?? null,
                'receipt_number'    => $receiptNumber,
                'received_date'     => $data['received_date'] ?? today(),
                'received_by'       => $actorId,
                'status'            => 'pending',
                'notes'             => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] ?? [] as $lineItem) {
                GoodsReceiptItem::create([
                    'goods_receipt_id'       => $receipt->id,
                    'inventory_item_id'      => $lineItem['inventory_item_id'],
                    'purchase_order_item_id' => $lineItem['po_item_id'] ?? null,
                    'quantity_received'      => (int) $lineItem['quantity'],
                    'batch_number'           => $lineItem['batch_number'] ?? null,
                    'expiry_date'            => $lineItem['expiry_date'] ?? null,
                    'unit_cost'              => $lineItem['unit_cost'] ?? null,
                    'notes'                  => $lineItem['notes'] ?? null,
                ]);

                // Create stock batch + movement for each line
                $this->receiveStock($facilityId, [
                    'inventory_item_id' => $lineItem['inventory_item_id'],
                    'location_id'       => $data['location_id'] ?? null,
                    'quantity'          => (int) $lineItem['quantity'],
                    'batch_number'      => $lineItem['batch_number'] ?? null,
                    'expiry_date'       => $lineItem['expiry_date'] ?? null,
                    'unit_cost'         => $lineItem['unit_cost'] ?? null,
                    'supplier_id'       => $data['supplier_id'] ?? null,
                    'reference_type'    => 'goods_receipt',
                    'reference_id'      => $receipt->id,
                ], $actorId);

                // Update PO item received qty if linked
                if (!empty($lineItem['po_item_id'])) {
                    PurchaseOrderItem::where('id', $lineItem['po_item_id'])
                        ->increment('quantity_received', (int) $lineItem['quantity']);
                }
            }

            $receipt->update(['status' => 'verified']);

            $this->audit('goods_receipt_created', $receipt->id, null, ['receipt_number' => $receiptNumber], $actorId, $facilityId);

            return $receipt->load('items');
        });
    }

    // ── Low Stock Detection ───────────────────────────────────

    public function getLowStockItems(string $facilityId): Collection
    {
        return InventoryItem::where('facility_id', $facilityId)
            ->where('status', 'active')
            ->where('reorder_level', '>', 0)
            ->get()
            ->filter(function (InventoryItem $item) use ($facilityId) {
                return $item->totalStock($facilityId) <= $item->reorder_level;
            });
    }

    public function getExpiringSoonBatches(string $facilityId, int $days = 30): Collection
    {
        return StockBatch::where('facility_id', $facilityId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>', now())
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->where('quantity_available', '>', 0)
            ->with('item')
            ->orderBy('expiry_date')
            ->get();
    }

    public function getExpiredBatches(string $facilityId): Collection
    {
        return StockBatch::where('facility_id', $facilityId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now())
            ->where('quantity_available', '>', 0)
            ->with('item')
            ->orderBy('expiry_date')
            ->get();
    }

    // ── Dashboard Stats ───────────────────────────────────────

    public function dashboardStats(string $facilityId): array
    {
        $items    = InventoryItem::where('facility_id', $facilityId)->where('status', 'active')->count();
        $lowStock = $this->getLowStockItems($facilityId)->count();
        $expiring = $this->getExpiringSoonBatches($facilityId, 30)->count();
        $expired  = $this->getExpiredBatches($facilityId)->count();
        $suppliers = Supplier::where('facility_id', $facilityId)->where('status', 'active')->count();
        $openPOs  = PurchaseOrder::where('facility_id', $facilityId)
            ->whereIn('status', ['draft', 'submitted', 'approved', 'sent', 'partial'])->count();

        return compact('items', 'lowStock', 'expiring', 'expired', 'suppliers', 'openPOs');
    }

    // ── Audit helper ──────────────────────────────────────────

    private function audit(string $action, string $subjectId, ?array $before, ?array $after, string $actorId, string $facilityId): void
    {
        try {
            AuditEvent::create([
                'actor_id'     => $actorId,
                'facility_id'  => $facilityId,
                'action_type'  => $action,
                'subject_type' => 'supply_chain',
                'subject_id'   => $subjectId,
                'before_state' => $before ? json_encode($before) : null,
                'after_state'  => $after  ? json_encode($after)  : null,
            ]);
        } catch (\Throwable) {
            // Non-fatal — audit should never block operations
        }
    }
}
