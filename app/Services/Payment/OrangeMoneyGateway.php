<?php
namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;

class OrangeMoneyGateway
{
    private string $baseUrl;
    private string $merchantKey;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->baseUrl      = config('services.orange_money.base_url', 'https://api.orange.com');
        $this->merchantKey  = config('services.orange_money.merchant_key', '');
        $this->clientId     = config('services.orange_money.client_id', '');
        $this->clientSecret = config('services.orange_money.client_secret', '');
    }

    public function requestPayment(int $amountXaf, string $phoneNumber, string $reference, string $description): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/orange-money-webpay/cm/v1/webpayment", [
                'merchant_key' => $this->merchantKey,
                'currency'     => 'XAF',
                'order_id'     => $reference,
                'amount'       => $amountXaf,
                'return_url'   => config('app.url') . '/api/payments/orange-money/callback',
                'cancel_url'   => config('app.url') . '/api/payments/orange-money/cancel',
                'notif_url'    => config('app.url') . '/api/payments/orange-money/notify',
                'lang'         => 'fr',
                'reference'    => $reference,
            ]);

        return [
            'success'      => $response->successful(),
            'provider_ref' => $response->json('pay_token'),
            'payment_url'  => $response->json('payment_url'),
            'http_status'  => $response->status(),
        ];
    }

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post('https://api.orange.com/oauth/v3/token', ['grant_type' => 'client_credentials']);

        return $response->json('access_token', '');
    }
}
