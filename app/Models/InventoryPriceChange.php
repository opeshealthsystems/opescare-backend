<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change of a medicine's selling price, in whole francs.
 */
class InventoryPriceChange extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'facility_id', 'inventory_item_id', 'old_price_xaf', 'new_price_xaf', 'changed_by', 'reason',
    ];

    protected $casts = [
        'old_price_xaf' => 'integer',
        'new_price_xaf' => 'integer',
        'created_at'    => 'datetime',
    ];

    /**
     * APPEND-ONLY. A price change is a statement that the price moved, from
     * what to what, by whom and why. Cancelling the update and delete events
     * makes an edit impossible through Eloquent, as StockMovement does.
     */
    protected static function booted(): void
    {
        static::updating(fn (self $change) => false);
        static::deleting(fn (self $change) => false);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
