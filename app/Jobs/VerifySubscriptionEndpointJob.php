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
 * VerifySubscriptionEndpointJob
 *
 * FHIR R4 Subscription handshake — Migration Sprint Item 3.
 *
 * Before a FHIR Subscription becomes active the server MUST verify that the
 * endpoint URL is willing to receive notifications. This job implements the
 * FHIR R4 "handshake notification" pattern (§2.3.2 of the Subscription spec):
 *
 *   1. POST a "subscription-notification" Bundle (type = handshake) to the
 *      subscriber endpoint.
 *   2. On 2xx: transition subscription status to 'active'.
 *   3. On any failure (4xx/5xx/timeout): increment error_count and set
 *      status to 'error' once max retries are exhausted.
 *
 * The job is dispatched immediately after a rest-hook subscription is created.
 * Laravel's retry/backoff mechanism handles transient endpoint failures.
 *
 * ISO 27001 A.13.1 / HL7 FHIR R4 Subscription §2.3.2
 */
class VerifySubscriptionEndpointJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum delivery attempts before marking status = 'error'. */
    public int $tries = 3;

    /** Seconds between retries: 30s, 120s, 300s. */
    public array $backoff = [30, 120, 300];

    /** HTTP request timeout in seconds. */
    private const HTTP_TIMEOUT = 10;

    public function __construct(
        public readonly FhirSubscription $subscription,
    ) {}

    public function handle(): void
    {
        // Guard: only rest-hook subscriptions need an endpoint handshake.
        if ($this->subscription->channel_type !== 'rest-hook') {
            $this->subscription->update(['status' => 'active']);
            return;
        }

        // Guard: endpoint must be set.
        if (empty($this->subscription->endpoint)) {
            Log::warning('fhir_subscription_handshake_skipped_no_endpoint', [
                'subscription_id' => $this->subscription->id,
            ]);
            $this->subscription->update(['status' => 'error']);
            return;
        }

        $handshakeBundle = $this->buildHandshakeBundle();

        try {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout(self::HTTP_TIMEOUT)
                ->post($this->subscription->endpoint, $handshakeBundle);

            if ($response->successful()) {
                $this->subscription->update(['status' => 'active']);

                Log::info('fhir_subscription_activated', [
                    'subscription_id' => $this->subscription->id,
                    'endpoint'        => $this->subscription->endpoint,
                    'http_status'     => $response->status(),
                ]);
            } else {
                // Non-2xx from the subscriber endpoint — record and potentially retry.
                Log::warning('fhir_subscription_handshake_rejected', [
                    'subscription_id' => $this->subscription->id,
                    'endpoint'        => $this->subscription->endpoint,
                    'http_status'     => $response->status(),
                ]);

                $this->handleFailure("Endpoint returned HTTP {$response->status()}");
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('fhir_subscription_handshake_connection_error', [
                'subscription_id' => $this->subscription->id,
                'endpoint'        => $this->subscription->endpoint,
                'error'           => $e->getMessage(),
            ]);

            $this->handleFailure('Connection error: ' . $e->getMessage());
        }
    }

    /**
     * Called by Laravel after all retry attempts are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $this->subscription->update([
            'status'      => 'error',
            'error_count' => \Illuminate\Support\Facades\DB::raw('COALESCE(error_count, 0) + 1'),
        ]);

        Log::error('fhir_subscription_handshake_failed_permanently', [
            'subscription_id' => $this->subscription->id,
            'endpoint'        => $this->subscription->endpoint,
            'exception'       => $exception->getMessage(),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Re-throw to allow Laravel's retry mechanism to re-queue the job.
     * On the final attempt, `failed()` is called automatically.
     */
    private function handleFailure(string $reason): void
    {
        $this->subscription->increment('error_count');
        throw new \RuntimeException("FHIR subscription handshake failed: {$reason}");
    }

    /**
     * Build a FHIR R4 subscription-notification Bundle (type = handshake).
     *
     * @see https://www.hl7.org/fhir/subscription.html#handshake
     */
    private function buildHandshakeBundle(): array
    {
        return [
            'resourceType' => 'Bundle',
            'type'         => 'subscription-notification',
            'timestamp'    => now()->toAtomString(),
            'entry'        => [
                [
                    'resource' => [
                        'resourceType'  => 'SubscriptionStatus',
                        'status'        => 'requested',
                        'type'          => 'handshake',
                        'subscription'  => [
                            'reference' => "Subscription/{$this->subscription->id}",
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build request headers for the handshake POST.
     *
     * Includes the facility-specific signing secret as a shared token so the
     * subscriber can validate that the request is genuine.
     */
    private function buildHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/fhir+json',
            'Accept'       => 'application/fhir+json',
        ];

        if (! empty($this->subscription->signing_secret)) {
            $headers['X-Hub-Signature'] = 'sha256=' . hash_hmac(
                'sha256',
                json_encode($this->buildHandshakeBundle()),
                $this->subscription->signing_secret,
            );
        }

        // Forward any custom headers configured by the subscriber.
        foreach (($this->subscription->headers ?? []) as $header) {
            if (str_contains($header, ':')) {
                [$name, $value] = explode(':', $header, 2);
                $headers[trim($name)] = trim($value);
            }
        }

        return $headers;
    }
}
