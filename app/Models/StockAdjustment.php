<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockAdjustment — Module 17 (Inventory & Supply Chain)
 *
 * Records corrections to stock quantities (physical count discrepancy,
 * damage, expiry write-off, theft, etc.).
 * High-risk adjustments may require approval before taking effect.
 */
class StockAdjustment extends Model
{
    use HasUuids;

    protected $fillable = [
        'inventory_item_id', 'stock_batch_id', 'stock_location_id', 'facility_id',
        'adjustment_type', 'quantity_before', 'quantity_adjusted', 'quantity_after',
        'unit', 'reason', 'notes',
        'adjusted_by', 'approved_by', 'approved_at', 'requires_approval',
    ];

    protected $casts = [
        'quantity_before'   => 'decimal:3',
        'quantity_adjusted' => 'decimal:3',
        'quantity_after'    => 'decimal:3',
        'requires_approval' => 'boolean',
        'approved_at'       => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function isApproved(): bool
    {
        return $this->approved_by !== null;
    }

    public function isPendingApproval(): bool
    {
        return $this->requires_approval && $this->approved_by === null;
    }

    public function approve(string $approvedBy): void
    {
        $this->update(['approved_by' => $approvedBy, 'approved_at' => now()]);
    }

    public function isDecrease(): bool
    {
        return (float) $this->quantity_adjusted < 0;
    }
}
