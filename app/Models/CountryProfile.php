<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CountryProfile — Country Expansion
 *
 * Comprehensive profile of a country's health system context, regulatory
 * environment, language requirements, and launch readiness.
 */
class CountryProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'official_name',
        'local_name',
        'hie_status',            // none|planned|partial|operational
        'health_system_type',    // public|private|mixed
        'primary_language',
        'official_languages',
        'currency_code',
        'timezone',
        'data_residency_requirement', // local|regional|none
        'gdpr_equivalent',
        'launch_approved',
        'notes',
    ];

    protected $casts = [
        'official_languages'  => 'array',
        'launch_approved'     => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function languagePacks(): HasMany
    {
        return $this->hasMany(CountryLanguagePack::class, 'country_id', 'country_id');
    }

    public function requiresDataResidency(): bool
    {
        return in_array($this->data_residency_requirement, ['local', 'regional'], true);
    }

    public function isLaunched(): bool
    {
        return $this->launch_approved;
    }
}
