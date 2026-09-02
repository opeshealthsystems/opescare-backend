<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AiModelRegistry — AI/ML Model Governance Registry
 *
 * Tracks AI and ML models used within OpesCare for clinical advisory purposes.
 *
 * CDSS SAFETY RULE: AI models registered here MUST NEVER replace clinical judgment.
 * Every approved_use entry must include "advisory only" constraints.
 * No model may autonomously prescribe, diagnose, or alter care plans without
 * explicit clinician review and approval.
 */
class AiModelRegistry extends Model
{
    use HasUuids;

    protected $fillable = [
        'model_name', 'model_version', 'purpose', 'training_data_summary',
        'risk_level', 'approved_uses', 'blocked_uses',
        'approval_status', 'approved_by', 'approved_at',
        'monitoring_metrics', 'rollback_version',
    ];

    protected $casts = [
        'approved_uses'       => 'array',
        'blocked_uses'        => 'array',
        'monitoring_metrics'  => 'array',
        'approved_at'         => 'datetime',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function riskLevelLabel(): string
    {
        return match ($this->risk_level) {
            'low'      => 'Low Risk',
            'medium'   => 'Medium Risk',
            'high'     => 'High Risk',
            'critical' => 'Critical Risk — Extended Review Required',
            default    => ucfirst($this->risk_level ?? 'Unknown'),
        };
    }

    // ── Mutators / Actions ─────────────────────────────────────────────────

    public function approve(string $approvedBy): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by'     => $approvedBy,
            'approved_at'     => now(),
        ]);
    }

    public function deprecate(): void
    {
        $this->update(['approval_status' => 'deprecated']);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeForRiskLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopePendingReview($query)
    {
        return $query->where('approval_status', 'pending');
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
