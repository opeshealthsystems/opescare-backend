<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PriceListItem — Module 7 (Billing, Payments & Wallet)
 *
 * Individual billable service item within a PriceList.
 * Drives invoice item selection at point of care.
 */
class PriceListItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'price_list_id',
        'item_code',
        'name',
        'description',
        'category',
        'unit_price',
        'currency',
        'unit',
        'is_insurance_billable',
        'requires_authorization',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'unit_price'             => 'decimal:2',
        'is_insurance_billable'  => 'boolean',
        'requires_authorization' => 'boolean',
        'is_active'              => 'boolean',
        'metadata'               => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
