<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MTN Mobile Money (MoMo) Payment Service
 *
 * Implements MTN MoMo Collections API (Disbursements API — future).
 * Docs: https://momodeveloper.mtn.com/docs/services/collection
 *
 * Env: MTN_MOMO_BASE_URL, MTN_MOMO_SUBSCRIPTION_KEY, MTN_MOMO_API_KEY,
 *      MTN_MOMO_USER_ID, MTN_MOMO_ENVIRONMENT, MTN_MOMO_CURRENCY, MTN_MOMO_CALLBACK_URL
 */
class MtnMomoService implements PaymentProvider
{
    private string $baseUrl;
    private string $subscriptionKey;
    private string $apiKey;
    private string $userId;
    private string $environment;
    private string $currency;
    private ?string $callbackUrl;
    private int $connectTimeout;
    private int $timeout;
    private int $retries;

    public function __construct()
    {
        // config() returns null (not the default) when the key exists but the
        // env var is unset — coalesce so typed string properties never get null.
        $this->baseUrl         = rtrim((string) (config('services.mtn_momo.base_url') ?? 'https://sandbox.momodeveloper.mtn.com'), '/');
        $this->subscriptionKey = (string) (config('services.mtn_momo.subscription_key') ?? '');
        $this->apiKey          = (string) (config('services.mtn_momo.api_key') ?? '');
        $this->userId          = (string) (config('services.mtn_momo.user_id') ?? '');
        $this->environment     = (string) (config('services.mtn_momo.environment') ?? 'sandbox');
        $this->currency        = (string) (config('services.mtn_momo.currency') ?? 'XAF');
        $this->callbackUrl     = config('services.mtn_momo.callback_url');
        $this->connectTimeout  = (int) (config('services.mtn_momo.connect_timeout') ?? 5);
        $this->timeout         = (int) (config('services.mtn_momo.timeout') ?? 30);
        $this->retries         = max(1, (int) (config('services.mtn_momo.retries') ?? 3));
    }

    public function getName(): string
    {
        return 'mtn_momo';
    }

    /**
     * Configured HTTP client: hard connect/read timeouts so a hung telco
     * endpoint can never block the request thread, plus retries that fire
     * ONLY on connection-level failures (request never reached MoMo) — never
     * on an HTTP response, so an accepted requesttopay is never re-sent.
     */
    private function http(): PendingRequest
    {
        return Http::connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->retry($this->retries, 250, function ($exception) {
                return $exception instanceof ConnectionException;
            }, throw: true);
    }

    /**
     * Request payment via MTN MoMo Collections API.
     * Returns: {success, reference_id, status, raw_response}
     */
    public function requestPayment(
        string $phoneNumber,
        float  $amount,
        string $currency,
        string $externalRef,
        string $description
    ): array {
        $referenceId = (string) Str::uuid();
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to obtain MTN MoMo access token'];
        }

        $payload = [
            'amount'                => (string) intval($amount), // MoMo expects integer string
            'currency'              => $currency ?: $this->currency,
            'externalId'            => $externalRef,
            'payer'                 => [
                'partyIdType' => 'MSISDN',
                'partyId'     => $this->normalizePhone($phoneNumber),
            ],
            'payerMessage'          => substr($description, 0, 160),
            'payeeNote'             => "OpesCare payment {$externalRef}",
        ];

        try {
            $response = $this->http()->withHeaders([
                'Authorization'       => "Bearer {$accessToken}",
                'X-Reference-Id'      => $referenceId,
                'X-Target-Environment'=> $this->environment,
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                'Content-Type'        => 'application/json',
            ])->when($this->callbackUrl, fn($r) => $r->withHeader('X-Callback-Url', $this->callbackUrl))
              ->post("{$this->baseUrl}/collection/v1_0/requesttopay", $payload);

            if ($response->status() === 202) {
                return [
                    'success'      => true,
                    'reference_id' => $referenceId,
                    'status'       => 'pending',
                ];
            }

            Log::error('MTN MoMo requestPayment failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'error'   => "MoMo API error: HTTP {$response->status()}",
                'body'    => $response->json(),
            ];

        } catch (\Throwable $e) {
            Log::error('MTN MoMo exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check transaction status by MTN reference ID.
     * Returns: {status: PENDING|SUCCESSFUL|FAILED, amount, currency, payer, raw}
     */
    public function checkStatus(string $transactionRef): array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['status' => 'UNKNOWN', 'error' => 'Token error'];
        }

        try {
            $response = $this->http()->withHeaders([
                'Authorization'             => "Bearer {$accessToken}",
                'X-Target-Environment'      => $this->environment,
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            ])->get("{$this->baseUrl}/collection/v1_0/requesttopay/{$transactionRef}");

            if ($response->ok()) {
                $data = $response->json();
                return [
                    'status'           => $data['status'] ?? 'UNKNOWN',
                    'amount'           => $data['amount'] ?? null,
                    'currency'         => $data['currency'] ?? null,
                    'financial_trx_id' => $data['financialTransactionId'] ?? null,
                    'raw'              => $data,
                ];
            }

            return ['status' => 'UNKNOWN', 'error' => "HTTP {$response->status()}"];

        } catch (\Throwable $e) {
            return ['status' => 'UNKNOWN', 'error' => $e->getMessage()];
        }
    }

    /**
     * Go-live check: can we obtain an access token with the configured creds?
     */
    public function canAuthenticate(): bool
    {
        return $this->getAccessToken() !== null;
    }

    /**
     * Obtain an OAuth2 Bearer token from MTN MoMo.
     */
    private function getAccessToken(): ?string
    {
        try {
            $response = $this->http()->withBasicAuth($this->userId, $this->apiKey)
                ->withHeaders(['Ocp-Apim-Subscription-Key' => $this->subscriptionKey])
                ->post("{$this->baseUrl}/collection/token/");

            if ($response->ok()) {
                return $response->json('access_token');
            }

            Log::error('MTN MoMo token request failed', ['status' => $response->status()]);
            return null;

        } catch (\Throwable $e) {
            Log::error('MTN MoMo token exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function normalizePhone(string $phone): string
    {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        // Cameroon: 237 country code
        if (str_starts_with($phone, '0')) {
            $phone = '237' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '237') && strlen($phone) === 9) {
            $phone = '237' . $phone;
        }
        return $phone;
    }
}
