<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CountryDataResidencyRule — Module 18 (Country Expansion Framework)
 *
 * Defines data residency constraints for a country.
 * Determines which data categories must remain in-country and
 * which can be transferred cross-border with safeguards.
 *
 * Rule: "Do not expand to a new country without... data residency review."
 */
class CountryDataResidencyRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'data_category',         // patient_records|financial|audit|aggregate|all
        'residency_requirement', // must_remain_in_country|may_transfer_with_safeguards|unrestricted
        'permitted_countries',   // JSON: ISO2 codes if cross-border transfer allowed
        'legal_basis',
        'implementation_notes',
        'is_active',
    ];

    protected $casts = [
        'permitted_countries' => 'array',
        'is_active'           => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function mustRemainInCountry(): bool
    {
        return $this->residency_requirement === 'must_remain_in_country';
    }

    public function canTransferTo(string $iso2): bool
    {
        if ($this->residency_requirement === 'unrestricted') {
            return true;
        }
        if ($this->residency_requirement === 'must_remain_in_country') {
            return false;
        }
        // may_transfer_with_safeguards — check permitted list
        return in_array(strtoupper($iso2), $this->permitted_countries ?? []);
    }

    public function requirementLabel(): string
    {
        return match($this->residency_requirement) {
            'must_remain_in_country'        => 'Must remain in country',
            'may_transfer_with_safeguards'  => 'Transfer with safeguards',
            'unrestricted'                  => 'Unrestricted',
            default                         => $this->residency_requirement,
        };
    }
}
