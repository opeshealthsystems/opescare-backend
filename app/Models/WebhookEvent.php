<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WebhookEvent — Connect Suite / Webhooks
 *
 * An event payload queued for delivery to all subscribed endpoints.
 * Events are immutable once created.
 *
 * Security: is_sensitive=true events require the consumer to have
 * explicit approval before the payload is delivered.
 */
class WebhookEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'event_type',
        'schema_version',  // payload contract version — see docs/API-VERSIONING.md
        'payload',
        'signature',    // HMAC of payload for consumer verification
        'is_sensitive',
    ];

    protected $casts = [
        'payload'      => 'array',
        'is_sensitive' => 'boolean',
    ];

    public function replays(): HasMany
    {
        return $this->hasMany(WebhookReplay::class);
    }

    public function deadLetters(): HasMany
    {
        return $this->hasMany(WebhookDeadLetter::class);
    }

    public function scopeForType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
