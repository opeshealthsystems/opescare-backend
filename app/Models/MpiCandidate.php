<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MpiCandidate — Master Patient Index duplicate candidate pair.
 *
 * Represents a potential duplicate match between two patient records.
 * Status lifecycle: pending_review → merged | rejected
 * match_score: 0.00–100.00 — confidence that source == target
 * match_reasons: JSON array of fields that drove the match score
 */
class MpiCandidate extends Model
{
    use HasUuids;

    protected $fillable = [
        'source_patient_id',
        'target_patient_id',
        'match_score',
        'match_reasons',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'match_score'   => 'decimal:2',
        'match_reasons' => 'array',
        'reviewed_at'   => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function sourcePatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'source_patient_id');
    }

    public function targetPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'target_patient_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeHighConfidence($query, float $threshold = 80.0)
    {
        return $query->where('match_score', '>=', $threshold);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isPending(): bool  { return $this->status === 'pending_review'; }
    public function isMerged(): bool   { return $this->status === 'merged'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}
