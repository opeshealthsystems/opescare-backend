<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServicePrice — Billing & Payments
 *
 * Defines the price for a specific clinical service at a facility.
 * A ServicePrice is the atomic unit for invoice line-item costing.
 * Supports both self-pay (base_price) and insurance tariffs (insurance_price).
 */
class ServicePrice extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'price_list_id',
        'service_code',
        'service_name',
        'service_category',   // consultation|lab|procedure|imaging|etc
        'base_price',
        'currency',
        'insurance_price',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'base_price'      => 'decimal:2',
        'insurance_price' => 'decimal:2',
        'is_active'       => 'boolean',
        'effective_from'  => 'date',
        'effective_to'    => 'date',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function isCurrentlyEffective(): bool
    {
        $today = now()->toDateString();
        if ($this->effective_from && $this->effective_from->gt(now())) {
            return false;
        }
        if ($this->effective_to && $this->effective_to->lt(now())) {
            return false;
        }
        return $this->is_active;
    }

    public function priceFor(bool $isInsuranceClaim = false): string
    {
        if ($isInsuranceClaim && $this->insurance_price !== null) {
            return $this->insurance_price;
        }
        return $this->base_price;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function scopeForCode($query, string $code)
    {
        return $query->where('service_code', $code);
    }
}
