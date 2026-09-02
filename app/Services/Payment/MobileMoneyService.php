<?php
namespace App\Services\Payment;

use App\Models\MobileMoneyTransaction;
use App\Services\Payments\MtnMomoService;
use App\Services\Payments\OrangeMoneyService;

class MobileMoneyService
{
    public function initiatePayment(
        string $provider,
        string $patientId,
        string $facilityId,
        int    $amountXaf,
        string $phoneNumber,
        string $reference,
        string $description = '',
    ): MobileMoneyTransaction {
        $txn = MobileMoneyTransaction::create([
            'patient_id'   => $patientId,
            'facility_id'  => $facilityId,
            'provider'     => $provider,
            'amount_xaf'   => $amountXaf,
            'phone_number' => $phoneNumber,
            'reference'    => $reference,
            'description'  => $description,
            'status'       => 'pending',
        ]);

        $gateway = match ($provider) {
            'mtn_momo'     => new MtnMomoService(),
            'orange_money' => new OrangeMoneyService(),
            default        => throw new \InvalidArgumentException("Unknown provider: {$provider}"),
        };

        try {
            $result = $gateway->requestPayment($phoneNumber, $amountXaf, 'XAF', $reference, $description);
            $txn->update([
                'provider_ref'      => $result['reference_id'] ?? $result['provider_ref'] ?? null,
                'provider_response' => $result,
                'status'            => ($result['success'] ?? false) ? 'pending' : 'failed',
            ]);
        } catch (\Exception $e) {
            $txn->update(['status' => 'failed', 'provider_response' => ['error' => $e->getMessage()]]);
        }

        return $txn->fresh();
    }

    public function checkStatus(string $provider, string $providerRef): array
    {
        $gateway = match ($provider) {
            'mtn_momo'     => new MtnMomoService(),
            'orange_money' => new OrangeMoneyService(),
            default        => throw new \InvalidArgumentException("Unknown provider: {$provider}"),
        };

        return $gateway->checkStatus($providerRef);
    }
}
