<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'goods_receipt_id', 'inventory_item_id', 'purchase_order_item_id',
        'quantity_received', 'batch_number', 'expiry_date', 'unit_cost', 'notes',
    ];

    protected $casts = [
        'expiry_date'       => 'date',
        'unit_cost'         => 'decimal:4',
        'quantity_received' => 'integer',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
