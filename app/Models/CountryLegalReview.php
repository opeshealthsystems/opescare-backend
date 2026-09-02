<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CountryLegalReview — Module 18 (Country Expansion Framework)
 *
 * Tracks legal reviews required before OpesCare can operate in a country.
 * Covers data protection, health regulation, employment, tax, and licensing.
 *
 * Rule: "Do not expand to a new country without legal... review."
 */
class CountryLegalReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'review_type',           // data_protection|health_regulation|employment|tax|licensing
        'status',                // pending|in_progress|completed|requires_action
        'findings',
        'required_actions',
        'reviewed_by',
        'reviewed_at',
        'next_review_date',
    ];

    protected $casts = [
        'reviewed_at'      => 'datetime',
        'next_review_date' => 'date',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function requiresAction(): bool
    {
        return $this->status === 'requires_action';
    }

    public function complete(string $reviewedBy, ?string $findings = null): void
    {
        $this->update([
            'status'      => 'completed',
            'reviewed_by' => $reviewedBy,
            'findings'    => $findings ?? $this->findings,
            'reviewed_at' => now(),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'completed'       => 'badge badge--success',
            'in_progress'     => 'badge badge--info',
            'requires_action' => 'badge badge--danger',
            'pending'         => 'badge badge--neutral',
            default           => 'badge badge--neutral',
        };
    }
}
