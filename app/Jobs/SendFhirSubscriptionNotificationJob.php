<?php

namespace App\Jobs;

use App\Models\FhirSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Deliver a FHIR Subscription notification to a rest-hook endpoint.
 *
 * FHIR R4 Subscriptions use the "rest-hook" channel type. When a resource
 * matching the subscription criteria changes, this job POSTs the updated
 * resource (or a notification bundle) to the subscriber's endpoint.
 *
 * Security: HMAC-SHA256 signature in X-OpesCare-FHIR-Signature header.
 * Retry policy: 3 attempts with 60s / 300s / 900s backoff.
 */
class SendFhirSubscriptionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [60, 300, 900];
    public int   $timeout = 30;

    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $resourceType,
        public readonly string $resourceId,
        public readonly array  $resourcePayload,
    ) {}

    public function handle(): void
    {
        $subscription = FhirSubscription::find($this->subscriptionId);

        if (! $subscription || ! $subscription->isActive()) {
            return; // Subscription gone or expired — discard
        }

        if ($subscription->channel_type !== 'rest-hook') {
            // Only rest-hook delivery is implemented for now
            Log::info('FHIR Subscription channel type not implemented — skipped', [
                'subscription_id' => $this->subscriptionId,
                'channel_type'    => $subscription->channel_type,
            ]);
            return;
        }

        $timestamp   = time();
        $payloadJson = json_encode($this->resourcePayload, JSON_THROW_ON_ERROR);
        $signature   = $subscription->signing_secret
            ? hash_hmac('sha256', $timestamp . '.' . $payloadJson, $subscription->signing_secret)
            : null;

        $headers = [
            'Content-Type'                => 'application/fhir+json',
            'X-OpesCare-FHIR-Resource'    => $this->resourceType . '/' . $this->resourceId,
            'X-OpesCare-FHIR-Timestamp'   => (string) $timestamp,
        ];

        if ($signature) {
            $headers['X-OpesCare-FHIR-Signature'] = 't=' . $timestamp . ',v1=' . $signature;
        }

        // Merge any subscriber-configured headers (e.g. auth tokens)
        foreach ($subscription->headers ?? [] as $header) {
            if (str_contains($header, ':')) {
                [$name, $value] = explode(':', $header, 2);
                $headers[trim($name)] = trim($value);
            }
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($subscription->endpoint, $this->resourcePayload);

            if ($response->successful()) {
                $subscription->update([
                    'last_notified_at' => now(),
                    'error_count'      => 0,
                ]);

                Log::info('FHIR Subscription notification delivered', [
                    'subscription_id' => $this->subscriptionId,
                    'resource'        => $this->resourceType . '/' . $this->resourceId,
                    'http_status'     => $response->status(),
                ]);
            } else {
                $subscription->increment('error_count');

                Log::warning('FHIR Subscription notification failed (HTTP error)', [
                    'subscription_id' => $this->subscriptionId,
                    'resource'        => $this->resourceType . '/' . $this->resourceId,
                    'http_status'     => $response->status(),
                ]);

                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1] ?? 900);
                }
            }
        } catch (\Throwable $e) {
            $subscription->increment('error_count');

            Log::warning('FHIR Subscription notification exception', [
                'subscription_id' => $this->subscriptionId,
                'resource'        => $this->resourceType . '/' . $this->resourceId,
                'error'           => $e->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 900);
            }
        }
    }

    /**
     * Mark the subscription as errored when all retries are exhausted.
     */
    public function failed(\Throwable $e): void
    {
        $subscription = FhirSubscription::find($this->subscriptionId);
        if ($subscription) {
            $subscription->update(['status' => 'error']);
        }
    }
}
