<?php

namespace App\Modules\Inventory\Services;

use App\Models\StockAudit;
use App\Models\InventoryItem;
use App\Models\AuditEvent;

/**
 * StockAuditService — Manages physical inventory audit/count processes.
 *
 * A stock audit is a physical count of inventory items to verify
 * that system quantities match physical reality. Discrepancies
 * require investigation before adjustment.
 *
 * Flow:
 *  1. Audit initiated (audit period frozen)
 *  2. Physical count recorded for each item
 *  3. Discrepancies computed (expected - actual)
 *  4. Discrepancies reviewed and approved
 *  5. Adjustments posted to inventory
 *  6. Audit closed and audit trail preserved
 */
class StockAuditService
{
    public function openAudit(string $facilityId, string $initiatedBy): StockAudit
    {
        $audit = StockAudit::create([
            'facility_id'  => $facilityId,
            'initiated_by' => $initiatedBy,
            'status'       => 'open',
            'started_at'   => now(),
        ]);

        AuditEvent::create([
            'actor_id'    => $initiatedBy,
            'action'      => 'stock_audit.opened',
            'module'      => 'inventory',
            'facility_id' => $facilityId,
            'metadata'    => ['audit_id' => $audit->id],
        ]);

        return $audit;
    }

    public function recordCount(
        string $auditId,
        string $inventoryItemId,
        float $physicalCount,
        string $countedBy
    ): void {
        $audit = StockAudit::findOrFail($auditId);
        $item  = InventoryItem::findOrFail($inventoryItemId);

        $audit->items()->updateOrCreate(
            ['inventory_item_id' => $inventoryItemId],
            [
                'expected_quantity'  => $item->quantity_on_hand,
                'physical_quantity'  => $physicalCount,
                'discrepancy'        => $physicalCount - $item->quantity_on_hand,
                'counted_by'         => $countedBy,
                'counted_at'         => now(),
            ]
        );
    }

    public function closeAudit(string $auditId, string $closedBy): StockAudit
    {
        $audit = StockAudit::findOrFail($auditId);
        $audit->update([
            'status'      => 'closed',
            'closed_by'   => $closedBy,
            'closed_at'   => now(),
        ]);

        AuditEvent::create([
            'actor_id' => $closedBy,
            'action'   => 'stock_audit.closed',
            'module'   => 'inventory',
            'metadata' => ['audit_id' => $auditId],
        ]);

        return $audit->fresh();
    }
}
