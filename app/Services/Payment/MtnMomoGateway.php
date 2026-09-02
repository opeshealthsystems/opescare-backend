<?php
namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MtnMomoGateway
{
    private string $baseUrl;
    private string $subscriptionKey = '';
    private string $environment;

    public function __construct()
    {
        $this->baseUrl         = config('services.mtn_momo.base_url', 'https://sandbox.momodeveloper.mtn.com');
        $this->subscriptionKey = config('services.mtn_momo.subscription_key') ?? '';
        $this->environment     = config('services.mtn_momo.environment', 'sandbox');
    }

    public function requestPayment(int $amountXaf, string $phoneNumber, string $reference, string $description): array
    {
        $token       = $this->getAccessToken();
        $referenceId = (string) Str::uuid();

        $response = Http::withToken($token)
            ->withHeaders([
                'X-Reference-Id'            => $referenceId,
                'X-Target-Environment'      => $this->environment,
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            ])
            ->post("{$this->baseUrl}/collection/v1_0/requesttopay", [
                'amount'       => (string) $amountXaf,
                'currency'     => 'XAF',
                'externalId'   => $reference,
                'payer'        => ['partyIdType' => 'MSISDN', 'partyId' => ltrim($phoneNumber, '+')],
                'payerMessage' => $description,
                'payeeNote'    => $description,
            ]);

        return [
            'success'      => $response->status() === 202,
            'provider_ref' => $referenceId,
            'http_status'  => $response->status(),
        ];
    }

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth(
            config('services.mtn_momo.api_user') ?? '',
            config('services.mtn_momo.api_key') ?? ''
        )->withHeaders(['Ocp-Apim-Subscription-Key' => $this->subscriptionKey])
          ->post("{$this->baseUrl}/collection/token/");

        return $response->json('access_token', '');
    }
}
