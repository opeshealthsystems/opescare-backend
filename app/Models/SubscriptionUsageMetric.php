<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsageMetric extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_id', 'organization_id', 'metric_key',
        'metric_value', 'period_start', 'period_end', 'recorded_at',
    ];

    protected $casts = [
        'period_start'  => 'date',
        'period_end'    => 'date',
        'recorded_at'   => 'datetime',
        'metric_value'  => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(OrganizationSubscription::class, 'subscription_id');
    }
}
