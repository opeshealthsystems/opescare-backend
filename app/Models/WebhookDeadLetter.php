<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookDeadLetter — Connect Suite / Webhooks
 *
 * Stores webhook delivery attempts that have permanently failed after
 * exhausting all retry attempts. Dead-letter events require manual
 * investigation and replay by the developer or support team.
 */
class WebhookDeadLetter extends Model
{
    use HasUuids;

    protected $fillable = [
        'webhook_event_id',
        'webhook_endpoint_id',
        'attempt_count',
        'last_error',
        'last_response_code',
        'last_attempted_at',
        'manually_resolved',
    ];

    protected $casts = [
        'attempt_count'       => 'integer',
        'last_response_code'  => 'integer',
        'last_attempted_at'   => 'datetime',
        'manually_resolved'   => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function resolve(): void
    {
        $this->update(['manually_resolved' => true]);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('manually_resolved', false);
    }
}
