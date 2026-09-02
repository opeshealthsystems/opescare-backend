<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CountryLaunchApproval — Module 18 (Country Expansion Framework)
 *
 * Formal gate that must be passed before OpesCare goes live in a country.
 * Tracks all checklist items and records the final approval decision.
 *
 * Rule: "Do not expand to a new country without legal, language,
 * facility registry, public health, and data residency review."
 * Country launch is blocked if this approval is not in 'approved' status.
 */
class CountryLaunchApproval extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'status',                             // pending|in_progress|approved|rejected|withdrawn
        'checklist_summary',
        'legal_review_complete',
        'health_regulation_review_complete',
        'language_pack_ready',
        'payment_configured',
        'pilot_facility_selected',
        'data_residency_reviewed',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'legal_review_complete'              => 'boolean',
        'health_regulation_review_complete'  => 'boolean',
        'language_pack_ready'                => 'boolean',
        'payment_configured'                 => 'boolean',
        'pilot_facility_selected'            => 'boolean',
        'data_residency_reviewed'            => 'boolean',
        'approved_at'                        => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function allChecklistItemsPassed(): bool
    {
        return $this->legal_review_complete
            && $this->health_regulation_review_complete
            && $this->language_pack_ready
            && $this->payment_configured
            && $this->pilot_facility_selected
            && $this->data_residency_reviewed;
    }

    public function approve(string $approvedBy, ?string $notes = null): void
    {
        $this->update([
            'status'         => 'approved',
            'approved_by'    => $approvedBy,
            'approved_at'    => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function reject(string $rejectedBy, string $notes): void
    {
        $this->update([
            'status'         => 'rejected',
            'approved_by'    => $rejectedBy,
            'approved_at'    => now(),
            'approval_notes' => $notes,
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'approved'    => 'badge badge--success',
            'in_progress' => 'badge badge--info',
            'rejected'    => 'badge badge--danger',
            'withdrawn'   => 'badge badge--neutral',
            'pending'     => 'badge badge--neutral',
            default       => 'badge badge--neutral',
        };
    }

    public function readinessPercent(): int
    {
        $items = [
            $this->legal_review_complete,
            $this->health_regulation_review_complete,
            $this->language_pack_ready,
            $this->payment_configured,
            $this->pilot_facility_selected,
            $this->data_residency_reviewed,
        ];
        $passed = count(array_filter($items));
        return (int) round($passed / count($items) * 100);
    }
}
