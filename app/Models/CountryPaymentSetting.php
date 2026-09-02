<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CountryPaymentSetting — Module 18 (Country Expansion Framework)
 *
 * Country-level payment configuration: currency, supported payment methods,
 * gateway, and tax settings. One record per country.
 *
 * Rule: Payment methods must be configured before facility launch.
 * "Do not make payment/refund changes without audit."
 */
class CountryPaymentSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'currency_code',
        'currency_symbol',
        'supported_payment_methods',  // JSON array: mobile_money|card|bank|insurance|wallet
        'primary_payment_gateway',
        'gateway_configs',
        'tax_applicable',
        'tax_rate_percent',
        'tax_name',
    ];

    protected $casts = [
        'supported_payment_methods' => 'array',
        'gateway_configs'           => 'array',
        'tax_applicable'            => 'boolean',
        'tax_rate_percent'          => 'float',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function supportsMobileMonety(): bool
    {
        return in_array('mobile_money', $this->supported_payment_methods ?? []);
    }

    public function supportsCard(): bool
    {
        return in_array('card', $this->supported_payment_methods ?? []);
    }

    public function hasTax(): bool
    {
        return $this->tax_applicable && $this->tax_rate_percent > 0;
    }

    public function taxAmount(float $grossAmount): float
    {
        if (! $this->hasTax()) {
            return 0.0;
        }
        return round($grossAmount * ($this->tax_rate_percent / 100), 2);
    }
}
