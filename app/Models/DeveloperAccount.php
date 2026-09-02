<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeveloperAccount extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'display_name',
        'email',
        'company_name',
        'website_url',
        'status',
        'email_verification_token',
        'email_verified_at',
        'api_terms_accepted',
        'api_terms_accepted_at',
        'api_terms_version',
        'sandbox_only',
        'admin_notes',
        'suspended_by',
        'suspend_reason',
        'suspended_at',
    ];

    protected $casts = [
        'api_terms_accepted'     => 'boolean',
        'sandbox_only'           => 'boolean',
        'email_verified_at'      => 'datetime',
        'api_terms_accepted_at'  => 'datetime',
        'suspended_at'           => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function productionRequests(): HasMany
    {
        return $this->hasMany(ProductionAccessRequest::class);
    }

    // ── Status Helpers ────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function isPendingVerification(): bool
    {
        return $this->status === 'pending_verification';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isSandboxOnly(): bool
    {
        return (bool) $this->sandbox_only;
    }

    public function hasAcceptedTerms(): bool
    {
        return (bool) $this->api_terms_accepted;
    }

    public function activate(): void
    {
        $this->update(['status' => 'active', 'sandbox_only' => false]);
    }

    public function suspend(string $reason, string $by): void
    {
        $this->update([
            'status'         => 'suspended',
            'suspend_reason' => $reason,
            'suspended_by'   => $by,
            'suspended_at'   => now(),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'active'               => 'badge badge--success',
            'pending_verification' => 'badge badge--warning',
            'suspended'            => 'badge badge--danger',
            'closed'               => 'badge badge--neutral',
            default                => 'badge badge--neutral',
        };
    }
}
