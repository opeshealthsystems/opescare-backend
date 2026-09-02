<?php

namespace App\Modules\Insurance\Services;

use App\Models\ClaimPayment;
use App\Models\InsuranceClaim;
use Illuminate\Support\Facades\DB;

class ClaimPaymentService
{
    /**
     * Record a payment against an approved claim.
     */
    public function recordPayment(string $claimId, string $actorId, array $data): ClaimPayment
    {
        return DB::transaction(function () use ($claimId, $actorId, $data) {
            $claim = InsuranceClaim::findOrFail($claimId);

            if (!$claim->canReceivePayment()) {
                throw new \Exception('CLAIM_NOT_PAYABLE');
            }

            $payment = ClaimPayment::create([
                'insurance_claim_id' => $claim->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'reference_number' => $data['reference_number'] ?? null,
                'recorded_by' => $actorId,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Accumulate paid amount and update status
            $totalPaid = $claim->payments()->sum('amount');
            $approvedAmount = $claim->approved_amount ?? $claim->claimed_amount;

            $newStatus = 'partially_paid';
            if ($totalPaid >= $approvedAmount) {
                $newStatus = 'paid';
            }

            $claim->update([
                'paid_amount' => $totalPaid,
                'status' => $newStatus,
            ]);

            return $payment;
        });
    }
}
