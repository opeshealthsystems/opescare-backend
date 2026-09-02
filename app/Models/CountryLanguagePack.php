<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CountryLanguagePack — Country Expansion / Bilingual Support
 *
 * Tracks the translation status of each language supported in a country.
 * English and French are the minimum; additional languages are per country.
 */
class CountryLanguagePack extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'language_code',          // en|fr|sw|ar|pt etc
        'language_name',
        'is_primary',
        'translation_status',     // pending|partial|complete|reviewed
        'missing_keys',
        'last_reviewed_at',
    ];

    protected $casts = [
        'is_primary'       => 'boolean',
        'missing_keys'     => 'array',
        'last_reviewed_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function isComplete(): bool
    {
        return in_array($this->translation_status, ['complete', 'reviewed'], true);
    }

    public function missingKeyCount(): int
    {
        return is_array($this->missing_keys) ? count($this->missing_keys) : 0;
    }

    public function scopeForCountry($query, string $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeComplete($query)
    {
        return $query->whereIn('translation_status', ['complete', 'reviewed']);
    }
}
