<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * DataCompletenessScore — Data Quality & Reconciliation (Module 37)
 *
 * Stores the latest data-completeness calculation for any OpesCare resource
 * (Patient, Facility, Encounter, etc.).  Completeness is expressed as a
 * 0–100 score and a breakdown of which fields are present or missing.
 *
 * Completeness scores are INFORMATIONAL — they guide data-entry improvement
 * but do not gate clinical operations.
 */
class DataCompletenessScore extends Model
{
    use HasUuids;

    protected $fillable = [
        'resource_type',    // Patient|Facility|Encounter|etc
        'resource_id',
        'score',            // 0.0 – 100.0
        'missing_fields',
        'present_fields',
        'total_fields',
        'filled_fields',
        'calculated_at',
    ];

    protected $casts = [
        'score'          => 'float',
        'missing_fields' => 'array',
        'present_fields' => 'array',
        'total_fields'   => 'integer',
        'filled_fields'  => 'integer',
        'calculated_at'  => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForRecord($query, string $resourceType, string $resourceId)
    {
        return $query->where('resource_type', $resourceType)
                     ->where('resource_id', $resourceId);
    }

    public function scopeBelowThreshold($query, float $threshold = 80.0)
    {
        return $query->where('score', '<', $threshold);
    }

    public function scopeForResourceType($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isComplete(float $threshold = 100.0): bool
    {
        return $this->score >= $threshold;
    }

    public function isAcceptable(float $threshold = 80.0): bool
    {
        return $this->score >= $threshold;
    }

    public function missingCount(): int
    {
        return is_array($this->missing_fields) ? count($this->missing_fields) : 0;
    }

    public function formattedScore(): string
    {
        return number_format($this->score, 1) . '%';
    }

    /**
     * Return or create the latest score record for a resource.
     */
    public static function latestFor(string $resourceType, string $resourceId): ?self
    {
        return static::forRecord($resourceType, $resourceId)
            ->orderByDesc('calculated_at')
            ->first();
    }
}
