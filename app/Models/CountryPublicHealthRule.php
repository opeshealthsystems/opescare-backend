<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CountryPublicHealthRule — Module 18 (Country Expansion Framework)
 *
 * Defines country-specific public health reporting rules.
 * Drives automated or manual reporting to MOH, DHIS2, CDC, IDSR, etc.
 *
 * Rule: "Do not make care map, medicine, blood, emergency, or insurance
 * availability sound guaranteed." Public health submissions are best-effort
 * and subject to network/connectivity constraints in low-resource settings.
 */
class CountryPublicHealthRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'rule_name',
        'rule_type',             // mandatory_reporting|notifiable_disease|aggregate_submission|syndromic
        'description',
        'reporting_frequency',   // daily|weekly|monthly|real_time|event_driven
        'reporting_destination', // MOH|DHIS2|CDC|IDSR|custom
        'is_active',
        'implementation_notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('rule_type', 'mandatory_reporting');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isMandatory(): bool
    {
        return in_array($this->rule_type, ['mandatory_reporting', 'notifiable_disease']);
    }

    public function isRealTime(): bool
    {
        return $this->reporting_frequency === 'real_time';
    }
}
