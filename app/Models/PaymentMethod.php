<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PaymentMethod — Module 7 (Billing, Payments & Wallet)
 *
 * Defines available payment channels for a facility (cash, mobile money,
 * card, bank transfer, insurance, wallet, voucher).
 * Used at point of payment to present the correct options.
 */
class PaymentMethod extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'method_type',
        'provider_name',
        'display_name',
        'is_active',
        'requires_reference',
        'is_digital',
        'configuration',
        'display_order',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'requires_reference' => 'boolean',
        'is_digital'         => 'boolean',
        'configuration'      => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where(function ($q) use ($facilityId) {
            $q->where('facility_id', $facilityId)->orWhereNull('facility_id');
        })->orderBy('display_order');
    }
}
