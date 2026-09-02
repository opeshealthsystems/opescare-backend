<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * InventoryCategory — Module 17 (Inventory & Supply Chain)
 *
 * Hierarchical categorisation of inventory items (consumable/equipment/
 * medicine/reagent). Supports parent-child nesting for sub-categories.
 */
class InventoryCategory extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'slug', 'parent_id', 'item_type', 'is_active', 'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
