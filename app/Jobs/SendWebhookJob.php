<?php

namespace App\Jobs;

use App\Models\WebhookDeliveryLog;
use App\Models\WebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum delivery attempts before the job is failed.
     */
    public int $tries = 3;

    /**
     * Backoff between retries (seconds): 60s, 300s, 900s
     */
    public array $backoff = [60, 300, 900];

    /**
     * Timeout per attempt in seconds.
     */
    public int $timeout = 30;

    public function __construct(
        public readonly string $subscriptionId,
        public readonly array  $payload,
    ) {}

    public function handle(): void
    {
        $subscription = WebhookSubscription::find($this->subscriptionId);

        if (!$subscription || $subscription->status !== 'active') {
            // Subscription gone or paused — discard silently
            return;
        }

        $timestamp   = time();
        $payloadJson = json_encode($this->payload);
        $signature   = hash_hmac('sha256', $timestamp . '.' . $payloadJson, $subscription->webhook_secret);

        // Find or create the delivery log record
        $log = WebhookDeliveryLog::firstOrCreate(
            ['event_id' => $this->payload['event_id'], 'webhook_subscription_id' => $subscription->id],
            [
                'event_type'              => $this->payload['event_type'],
                'endpoint_url'            => $subscription->callback_url,
                'payload'                 => $this->payload,
                'status'                  => 'pending',
                'attempts'                => 0,
            ]
        );

        $log->increment('attempts');

        try {
            $response = Http::withHeaders([
                'Content-Type'             => 'application/json',
                'X-OpesCare-Signature'     => 't=' . $timestamp . ',v1=' . $signature,
                'X-OpesCare-Timestamp'     => (string) $timestamp,
                'X-OpesCare-Event-Id'      => $this->payload['event_id'],
                'X-OpesCare-Webhook-Version'  => $this->payload['schema_version'] ?? 'v1',
                'X-OpesCare-Delivery-Attempt' => (string) $log->attempts,
            ])->timeout(10)->post($subscription->callback_url, $this->payload);

            if ($response->successful()) {
                $log->update([
                    'status'          => 'delivered',
                    'http_status_code'=> $response->status(),
                    'delivered_at'    => now(),
                    'response_body'   => substr($response->body(), 0, 500),
                ]);
            } else {
                $log->update([
                    'status'          => 'failed',
                    'http_status_code'=> $response->status(),
                    'response_body'   => substr($response->body(), 0, 500),
                ]);
                // Re-queue for retry if attempts remain
                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1] ?? 900);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Webhook delivery failed', [
                'subscription' => $this->subscriptionId,
                'event_id'     => $this->payload['event_id'],
                'error'        => $e->getMessage(),
            ]);
            $log->update(['status' => 'failed']);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 900);
            }
        }
    }

    /**
     * Handle exhausted retries — mark delivery as permanently failed.
     */
    public function failed(\Throwable $e): void
    {
        WebhookDeliveryLog::where('event_id', $this->payload['event_id'])
            ->where('status', 'failed')
            ->update(['status' => 'exhausted']);
    }
}
