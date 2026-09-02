<?php

namespace App\Http\Controllers\Api\V1\Sdk;

use App\Http\Controllers\Controller;
use App\Models\WebhookSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SdkWebhookController extends Controller
{
    /**
     * Subscribe an SDK client to webhook events.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'callback_url'      => 'required|url|max:500',
            'subscribed_events' => 'required|array|min:1',
            'subscribed_events.*' => 'string',
        ]);

        $clientId = $request->attributes->get('sdk_client_id', 'sdk');

        $secret = Str::random(40);

        $subscription = WebhookSubscription::create([
            'client_id'         => $clientId,
            'callback_url'      => $data['callback_url'],
            'subscribed_events' => $data['subscribed_events'],
            'webhook_secret'    => $secret,
            'status'            => 'active',
        ]);

        return response()->json([
            'subscription_id'   => $subscription->id,
            'callback_url'      => $subscription->callback_url,
            'subscribed_events' => $subscription->subscribed_events,
            'status'            => $subscription->status,
            'webhook_secret'    => $secret,  // shown once — store securely
            'created_at'        => $subscription->created_at->toIso8601String(),
        ], 201);
    }

    /**
     * Remove a webhook subscription.
     */
    public function unsubscribe(Request $request, string $id): JsonResponse
    {
        $clientId     = $request->attributes->get('sdk_client_id', 'sdk');
        $subscription = WebhookSubscription::where('id', $id)
            ->where('client_id', $clientId)
            ->first();

        if (!$subscription) {
            return response()->json(['error' => 'not_found', 'message' => __('api.subscription_not_owned')], 404);
        }

        $subscription->update(['status' => 'paused']);

        return response()->json(['message' => __('api.subscription_removed')], 200);
    }
}
