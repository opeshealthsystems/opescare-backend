<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'inventory_item_id', 'location_id', 'reorder_level',
        'reorder_quantity', 'preferred_supplier_id', 'auto_alert', 'is_active',
    ];

    protected $casts = [
        'reorder_level'    => 'integer',
        'reorder_quantity' => 'integer',
        'auto_alert'       => 'boolean',
        'is_active'        => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }
}
