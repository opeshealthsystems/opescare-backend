<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WebhookSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'callback_url',
        'webhook_secret',
        'subscribed_events',
        'status'
    ];

    protected $casts = [
        'subscribed_events' => 'array'
    ];
}
