<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'purchase_order_id', 'inventory_item_id',
        'quantity_ordered', 'quantity_received', 'unit_price', 'total_price', 'notes',
    ];

    protected $casts = [
        'quantity_ordered'  => 'integer',
        'quantity_received' => 'integer',
        'unit_price'        => 'decimal:4',
        'total_price'       => 'decimal:4',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function pendingQty(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }
}
