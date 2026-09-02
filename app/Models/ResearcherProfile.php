<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ResearcherProfile — Module 17 (Research & Data Access Program)
 *
 * Represents a verified researcher who may submit requests to access
 * de-identified OpesCare data for approved research purposes.
 *
 * Security constraint: Research access requires ethics approval, governance,
 * de-identification, and explicit data access committee approval.
 */
class ResearcherProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'institution',
        'department',
        'position',
        'orcid_id',
        'status',               // pending|active|suspended
        'research_interests',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function ethicsApprovals(): HasMany
    {
        return $this->hasMany(EthicsApproval::class);
    }

    public function dataAgreements(): HasMany
    {
        return $this->hasMany(ResearchDataAgreement::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(ResearchAccessLog::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function verify(): void
    {
        $this->update(['status' => 'active', 'verified_at' => now()]);
    }

    public function hasActiveEthicsApproval(): bool
    {
        return $this->ethicsApprovals()
            ->where('verified', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->exists();
    }
}
