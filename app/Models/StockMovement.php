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
