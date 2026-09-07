<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A volunteer on the blood donor register.
 *
 * See the create migration for why this is a register in its own right and not
 * a view over `patients`: a person is here because they filled in SB-03 and
 * consented, not because they hold a Health ID.
 *
 * The scopes below are the enforcement. `searchable()` is the ONLY way the
 * donor search may read this table, and it is written so that forgetting it is
 * a visible mistake rather than a silent disclosure.
 */
class BloodDonor extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'patient_id',
        'blood_group',
        'city',
        'region',
        'status',
        'listing_consent_at',
        'share_phone',
        'contact_phone',
        'last_donated_on',
        'note',
    ];

    protected $casts = [
        'listing_consent_at' => 'datetime',
        'share_phone'        => 'boolean',
        'last_donated_on'    => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Every row a donor search is allowed to see.
     *
     * Two conditions, both required: the volunteer is currently active, and
     * they have actually consented to be listed. A paused donor keeps their
     * place in the register but disappears from search, and a row without a
     * consent timestamp is never visible at all — which is what makes a
     * half-finished SB-03 registration harmless.
     */
    public function scopeSearchable(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('listing_consent_at');
    }

    /** Is this volunteer contactable by phone, and did they agree to that? */
    public function publishesPhone(): bool
    {
        return $this->share_phone && filled($this->contact_phone);
    }

    /**
     * Months since the last donation, or null when never recorded.
     *
     * Months rather than a date: the reference prints "Last donated: 2 months
     * ago", and the coarser figure is both what a requester needs and less of
     * a fingerprint than an exact date on a named person.
     */
    public function monthsSinceDonation(): ?int
    {
        return $this->last_donated_on?->diffInMonths(now());
    }
}
