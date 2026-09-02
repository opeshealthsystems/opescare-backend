<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrialPeriod — Subscription / SaaS Billing (Module 23)
 *
 * Tracks a trial subscription period for an organization.
 * One trial per organization per subscription plan.
 */
class TrialPeriod extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_plan_id',
        'organization_id',
        'duration_days',
        'started_at',
        'ends_at',
        'converted',
        'converted_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'ends_at'      => 'datetime',
        'converted'    => 'boolean',
        'converted_at' => 'datetime',
        'duration_days' => 'integer',
    ];

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function isActive(): bool
    {
        return !$this->converted && $this->ends_at->isFuture();
    }

    public function isExpired(): bool
    {
        return !$this->converted && $this->ends_at->isPast();
    }

    public function daysRemaining(): int
    {
        if (!$this->isActive()) {
            return 0;
        }
        return max(0, (int) now()->diffInDays($this->ends_at, false));
    }

    public function convert(): void
    {
        $this->update(['converted' => true, 'converted_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('converted', false)->where('ends_at', '>', now());
    }

    public function scopeForOrganization($query, string $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
}
