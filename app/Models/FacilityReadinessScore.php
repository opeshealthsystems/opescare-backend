<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FacilityReadinessScore — Facility Go-Live Readiness (Module 40)
 *
 * Stores the computed readiness score for a facility ahead of go-live.
 * The score is broken into sub-dimensions (staff, config, data, support)
 * and includes a boolean gate (is_ready) that must be true before the
 * GoLiveApproval can be granted.
 *
 * Scores are recalculated by the ReadinessScoreService whenever a
 * GoLiveChecklistItem is completed or a GoLiveBlocker is resolved.
 */
class FacilityReadinessScore extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'go_live_checklist_id',
        'overall_score',     // 0.0 – 100.0
        'staff_score',
        'config_score',
        'data_score',
        'support_score',
        'open_blockers',
        'recommendations',
        'is_ready',
        'calculated_at',
    ];

    protected $casts = [
        'overall_score'   => 'float',
        'staff_score'     => 'float',
        'config_score'    => 'float',
        'data_score'      => 'float',
        'support_score'   => 'float',
        'open_blockers'   => 'array',
        'recommendations' => 'array',
        'is_ready'        => 'boolean',
        'calculated_at'   => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function goLiveChecklist(): BelongsTo
    {
        return $this->belongsTo(GoLiveChecklist::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function scopeReady($query)
    {
        return $query->where('is_ready', true);
    }

    public function scopeNotReady($query)
    {
        return $query->where('is_ready', false);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function openBlockerCount(): int
    {
        return is_array($this->open_blockers) ? count($this->open_blockers) : 0;
    }

    public function formattedScore(): string
    {
        return number_format($this->overall_score, 1) . '%';
    }

    public function readinessLabel(): string
    {
        return match (true) {
            $this->overall_score >= 90 => 'Excellent',
            $this->overall_score >= 75 => 'Good',
            $this->overall_score >= 50 => 'Partial',
            default                    => 'Not Ready',
        };
    }

    public function readinessBadgeClass(): string
    {
        return match (true) {
            $this->overall_score >= 90 => 'badge--success',
            $this->overall_score >= 75 => 'badge--info',
            $this->overall_score >= 50 => 'badge--warning',
            default                    => 'badge--danger',
        };
    }

    /**
     * Return the most recent score for a facility.
     */
    public static function latestFor(string $facilityId): ?self
    {
        return static::forFacility($facilityId)
            ->orderByDesc('calculated_at')
            ->first();
    }
}
