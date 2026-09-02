<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ResearchDataAgreement — Module 17 (Research & Data Access Program)
 *
 * Legal agreement that a researcher must sign before accessing any dataset.
 * Records digital signature, IP address, and effective dates for audit.
 *
 * Security constraint: "Researcher signs agreement" (step 8 of research flow).
 * Agreement must be signed before data access is granted.
 */
class ResearchDataAgreement extends Model
{
    use HasUuids;

    protected $fillable = [
        'research_access_request_id',
        'researcher_profile_id',
        'agreement_text',
        'signed',
        'signed_at',
        'signature_ip',
        'effective_date',
        'expiry_date',
    ];

    protected $casts = [
        'signed'         => 'boolean',
        'signed_at'      => 'datetime',
        'effective_date' => 'date',
        'expiry_date'    => 'date',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function researchAccessRequest(): BelongsTo
    {
        return $this->belongsTo(ResearchAccessRequest::class);
    }

    public function researcherProfile(): BelongsTo
    {
        return $this->belongsTo(ResearcherProfile::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function sign(string $ipAddress): void
    {
        $this->update([
            'signed'       => true,
            'signed_at'    => now(),
            'signature_ip' => $ipAddress,
        ]);
    }

    public function isSigned(): bool
    {
        return $this->signed;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isActive(): bool
    {
        return $this->isSigned() && ! $this->isExpired();
    }
}
