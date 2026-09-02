<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'inventory_item_id', 'location_id', 'facility_id',
        'batch_number', 'lot_number', 'manufacture_date', 'expiry_date',
        'quantity_in', 'quantity_out', 'quantity_adjusted',
        'unit_cost', 'status', 'supplier_id', 'created_by',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date'      => 'date',
        'unit_cost'        => 'decimal:4',
        'quantity_in'      => 'integer',
        'quantity_out'     => 'integer',
        'quantity_adjusted'=> 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->isAfter(now()) && $this->expiry_date->isBefore(now()->addDays($days));
    }

    public function availableQty(): int
    {
        return (int) ($this->quantity_in - $this->quantity_out + $this->quantity_adjusted);
    }
}
