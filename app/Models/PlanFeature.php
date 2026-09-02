<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasUuids;

    protected $fillable = [
        'plan_id', 'feature_key', 'feature_label', 'limit_type', 'limit_value',
    ];

    protected $casts = [
        'limit_value' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isUnlimited(): bool
    {
        return $this->limit_type === 'unlimited' || $this->limit_value === null;
    }
}
