<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'inventory_item_id', 'batch_id',
        'from_location_id', 'to_location_id',
        'movement_type', 'quantity', 'unit_cost',
        'reference_type', 'reference_id',
        'reason', 'performed_by', 'performed_at',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'unit_cost'    => 'decimal:4',
        'performed_at' => 'datetime',
    ];

    /**
     * APPEND-ONLY. A movement is a statement that a quantity changed, by whom
     * and why, at a moment in time; correcting one is done by recording
     * another movement, never by rewriting or removing the first. Cancelling
     * the update and delete events makes an edit impossible through Eloquent
     * — `$movement->update([...])` and `$movement->delete()` return false and
     * touch nothing — so no controller, job or tinker session can quietly
     * change the ledger's history.
     */
    protected static function booted(): void
    {
        static::updating(fn (self $movement) => false);
        static::deleting(fn (self $movement) => false);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }
}
