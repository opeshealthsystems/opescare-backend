<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * WebhookEndpoint — Connect Suite / Webhooks
 *
 * A consumer-registered HTTPS endpoint that receives webhook event payloads.
 * All deliveries are signed with an HMAC secret.
 */
class WebhookEndpoint extends Model
{
    use HasUuids;

    protected $fillable = [
        'webhook_subscription_id',
        'developer_app_id',
        'url',
        'event_types',
        'status',           // active|disabled|failed
        'failure_count',
        'last_delivery_at',
    ];

    protected $casts = [
        'event_types'      => 'array',
        'failure_count'    => 'integer',
        'last_delivery_at' => 'datetime',
    ];

    public function secret(): HasOne
    {
        return $this->hasOne(WebhookSecret::class);
    }

    public function deadLetters(): HasMany
    {
        return $this->hasMany(WebhookDeadLetter::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function incrementFailure(): void
    {
        $this->increment('failure_count');
        if ($this->failure_count >= 5) {
            $this->update(['status' => 'failed']);
        }
    }

    public function resetFailures(): void
    {
        $this->update(['failure_count' => 0, 'status' => 'active']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
