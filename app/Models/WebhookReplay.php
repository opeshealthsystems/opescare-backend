<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookReplay — Connect Suite / Webhooks
 *
 * Records a manual replay of a webhook event to a specific endpoint.
 */
class WebhookReplay extends Model
{
    use HasUuids;

    protected $fillable = [
        'webhook_event_id',
        'webhook_endpoint_id',
        'replayed_by',
        'status',           // pending|delivered|failed
        'response_code',
        'response_body',
    ];

    protected $casts = [
        'response_code' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }
}
