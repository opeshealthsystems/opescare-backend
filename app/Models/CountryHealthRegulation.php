<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CountryHealthRegulation — Module 18 (Country Expansion Framework)
 *
 * Records country-specific health regulations that OpesCare must
 * comply with to operate in that country.
 *
 * Types: emr|pharmacy|lab|telemedicine|data_privacy|insurance
 */
class CountryHealthRegulation extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'regulation_name',
        'regulation_body',       // Ministry of Health, regulator name
        'regulation_type',       // emr|pharmacy|lab|telemedicine|data_privacy|insurance
        'description',
        'compliance_required',
        'compliance_status',     // not_assessed|compliant|non_compliant|in_progress
        'assessment_date',
        'compliance_notes',
    ];

    protected $casts = [
        'compliance_required' => 'boolean',
        'assessment_date'     => 'date',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeRequired($query)
    {
        return $query->where('compliance_required', true);
    }

    public function scopeNonCompliant($query)
    {
        return $query->where('compliance_status', 'non_compliant');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isCompliant(): bool
    {
        return $this->compliance_status === 'compliant';
    }

    public function isNonCompliant(): bool
    {
        return $this->compliance_status === 'non_compliant';
    }

    public function statusBadgeClass(): string
    {
        return match($this->compliance_status) {
            'compliant'     => 'badge badge--success',
            'in_progress'   => 'badge badge--info',
            'non_compliant' => 'badge badge--danger',
            'not_assessed'  => 'badge badge--neutral',
            default         => 'badge badge--neutral',
        };
    }
}
