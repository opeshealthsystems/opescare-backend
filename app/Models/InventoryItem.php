<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'name', 'code', 'category', 'unit',
        'reorder_level', 'reorder_quantity', 'track_expiry', 'track_batch',
        'unit_cost', 'status', 'description', 'created_by',
    ];

    protected $casts = [
        'track_expiry'     => 'boolean',
        'track_batch'      => 'boolean',
        'unit_cost'        => 'decimal:4',
        'reorder_level'    => 'integer',
        'reorder_quantity' => 'integer',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class, 'inventory_item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'inventory_item_id');
    }

    public function reorderRule(): HasMany
    {
        return $this->hasMany(ReorderRule::class, 'inventory_item_id');
    }

    public function totalStock(string $facilityId): int
    {
        return (int) $this->batches()
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->sum('quantity_available');
    }

    public static function categories(): array
    {
        return [
            'medicine'    => 'Medicine / Pharmaceutical',
            'consumable'  => 'Consumable / Disposable',
            'reagent'     => 'Lab Reagent / Chemical',
            'equipment'   => 'Medical Equipment',
            'linen'       => 'Linen / Textile',
            'ppe'         => 'PPE / Protective Equipment',
            'office'      => 'Office / Administrative Supply',
            'other'       => 'Other',
        ];
    }
}
