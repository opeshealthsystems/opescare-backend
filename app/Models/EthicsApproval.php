<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EthicsApproval — Module 17 (Research & Data Access Program)
 *
 * Records ethics committee approval for a research project.
 * Required before any data access can be granted.
 *
 * Security constraint: Research access requires ethics approval.
 */
class EthicsApproval extends Model
{
    use HasUuids;

    protected $fillable = [
        'researcher_profile_id',
        'approval_reference',   // Ethics committee reference number
        'approving_body',
        'study_title',
        'approval_date',
        'expiry_date',
        'document_path',
        'verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'approval_date' => 'date',
        'expiry_date'   => 'date',
        'verified'      => 'boolean',
        'verified_at'   => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function researcherProfile(): BelongsTo
    {
        return $this->belongsTo(ResearcherProfile::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isValid(): bool
    {
        return $this->verified && ! $this->isExpired();
    }

    public function verify(string $verifiedBy): void
    {
        $this->update([
            'verified'    => true,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);
    }
}
