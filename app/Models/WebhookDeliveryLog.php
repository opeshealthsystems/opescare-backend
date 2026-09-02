<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDeliveryLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'event_id',
        'webhook_subscription_id',
        'endpoint_url',
        'event_type',
        'payload',
        'status',
        'retry_count',
        'attempts',
        'http_status_code',
        'delivered_at',
        'response_body',
    ];

    protected $casts = [
        'payload'      => 'array',
        'delivered_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'webhook_subscription_id');
    }

    // ── Status Helpers ────────────────────────────────────────────────────────

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'exhausted']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'delivered' => 'badge badge--success',
            'pending'   => 'badge badge--warning',
            'failed'    => 'badge badge--danger',
            'exhausted' => 'badge badge--danger',
            default     => 'badge badge--neutral',
        };
    }
}
