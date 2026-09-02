<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ResearchDataset — Module 17 (Research & Data Access Program)
 *
 * Represents a de-identified or anonymised dataset prepared for
 * an approved research request.
 *
 * Security constraint: Only aggregate/de-identified/anonymised datasets.
 * Patient-level PII must be removed before any export.
 * "Do not allow research access without ethics, governance, de-identification, and approval."
 */
class ResearchDataset extends Model
{
    use HasUuids;

    protected $fillable = [
        'research_access_request_id',
        'dataset_name',
        'dataset_type',          // aggregate|de_identified|anonymised
        'included_fields',
        'excluded_fields',
        'time_range_from',
        'time_range_to',
        'record_count_estimate',
        'export_format',         // csv|json|fhir_bundle
        'storage_path',
        'status',                // pending|preparing|ready|delivered|expired
        'expires_at',
    ];

    protected $casts = [
        'included_fields'      => 'array',
        'excluded_fields'      => 'array',
        'record_count_estimate' => 'integer',
        'expires_at'           => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function researchAccessRequest(): BelongsTo
    {
        return $this->belongsTo(ResearchAccessRequest::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAccessible(): bool
    {
        return $this->isReady() && ! $this->isExpired();
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'   => 'badge badge--neutral',
            'preparing' => 'badge badge--info',
            'ready'     => 'badge badge--success',
            'delivered' => 'badge badge--success',
            'expired'   => 'badge badge--danger',
            default     => 'badge badge--neutral',
        };
    }
}
