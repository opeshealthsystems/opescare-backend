<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrganizationSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscriber_type', 'subscriber_id', 'interval',
        'organization_id', 'organization_name', 'plan_id', 'status',
        'trial_starts_at', 'trial_ends_at',
        'current_period_start', 'current_period_end',
        'cancelled_at', 'expires_at',
        'billing_email', 'billing_name', 'payment_reference', 'payment_method',
        'auto_renew', 'discount_percent', 'notes', 'created_by', 'updated_by',
        'renewal_reminded_at', 'winback_sent_at',
    ];

    protected $casts = [
        'trial_starts_at'      => 'date',
        'trial_ends_at'        => 'date',
        'current_period_start' => 'date',
        'current_period_end'   => 'date',
        'cancelled_at'         => 'date',
        'expires_at'           => 'date',
        'auto_renew'           => 'boolean',
        'discount_percent'     => 'integer',
        'renewal_reminded_at'  => 'datetime',
        'winback_sent_at'      => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Polymorphic subscriber — organization | patient | household.
     * Resolved via the morph map registered in AppServiceProvider.
     */
    public function subscriber(): MorphTo
    {
        return $this->morphTo();
    }

    public function isB2C(): bool
    {
        return in_array($this->subscriber_type, ['patient', 'household'], true);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class, 'subscription_id');
    }

    public function usageMetrics(): HasMany
    {
        return $this->hasMany(SubscriptionUsageMetric::class, 'subscription_id');
    }

    public function moduleEntitlements(): HasMany
    {
        return $this->hasMany(ModuleEntitlement::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing';
    }

    public function isExpired(): bool
    {
        return in_array($this->status, ['expired', 'cancelled']) ||
               ($this->current_period_end && $this->current_period_end->isPast());
    }

    public function daysUntilExpiry(): int
    {
        if (!$this->current_period_end) return 0;
        return max(0, (int) now()->diffInDays($this->current_period_end, false));
    }

    public function hasModule(string $moduleKey): bool
    {
        return $this->moduleEntitlements()
            ->where('module_key', $moduleKey)
            ->where('is_enabled', true)
            ->whereNull('revoked_at')
            ->exists();
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'active'    => 'success',
            'trialing'  => 'info',
            'past_due'  => 'warning',
            'paused'    => 'warning',
            'cancelled' => 'danger',
            'expired'   => 'danger',
            default     => 'default',
        };
    }
}
