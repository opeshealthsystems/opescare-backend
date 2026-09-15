<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WebhookSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        // Subscribing facility (the app's facility at registration / approval).
        // WebhookService::dispatch scopes patient-data events by this column, so
        // a subscription without it never receives a facility-scoped event.
        'facility_id',
        'callback_url',
        'webhook_secret',
        'subscribed_events',
        'status'
    ];

    protected $casts = [
        'subscribed_events' => 'array'
    ];
}
