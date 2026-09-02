<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PlanLimit — Module 23 (Subscription Billing & SaaS Model)
 *
 * Defines hard limits for a subscription plan (e.g., max API calls/month,
 * max storage, max users, max reports). Used by the usage billing service
 * to enforce plan boundaries.
 */
class PlanLimit extends Model
{
    use HasUuids;

    protected $fillable = [
        'plan_id',
        'limit_key',     // max_api_calls_per_month|max_storage_gb|max_users|max_reports_per_month
        'limit_value',
        'limit_unit',    // count|gb|requests|reports
        'description',
    ];

    protected $casts = [
        'limit_value' => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isUnlimited(): bool
    {
        return $this->limit_value < 0; // -1 = unlimited
    }

    public function displayValue(): string
    {
        if ($this->isUnlimited()) {
            return 'Unlimited';
        }
        $unit = $this->limit_unit ? ' ' . $this->limit_unit : '';
        return number_format($this->limit_value) . $unit;
    }
}
