<?php

namespace App\Modules\Billing\Services;

use App\Models\AuditEvent;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\Receipt;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private BillingService $billingService)
    {
    }

    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        if ((float) $data['amount'] <= 0) {
            throw new Exception('PAYMENT_AMOUNT_MUST_BE_POSITIVE');
        }

        return DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if ($data['method'] === 'wallet') {
                $this->debitWallet($data['wallet_id'] ?? null, (float) $data['amount'], $data['cashier_id'] ?? null);
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'facility_id' => $invoice->facility_id,
                'cashier_id' => $data['cashier_id'] ?? null,
                'cashier_session_id' => $data['cashier_session_id'] ?? null,
                'wallet_id' => $data['wallet_id'] ?? null,
                'payment_reference' => 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'method' => $data['method'],
                'status' => 'successful',
                'amount' => $data['amount'],
                'refunded_amount' => 0,
            ]);

            if ($payment->method === 'wallet') {
                WalletTransaction::where('wallet_id', $payment->wallet_id)
                    ->whereNull('payment_id')
                    ->latest()
                    ->first()
                    ?->update(['payment_id' => $payment->id]);
            }

            Receipt::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'receipt_number' => 'RCT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'amount' => $payment->amount,
                'issued_at' => now(),
            ]);

            $invoice->increment('paid_amount', (float) $payment->amount);
            $this->billingService->refreshInvoiceStatus($invoice->fresh());
            $this->audit('payment', $payment->id, $invoice, 'receive', $data['cashier_id'] ?? null, 'Payment received.');

            return $payment->fresh();
        });
    }

    public function refund(Payment $payment, array $data): PaymentReversal
    {
        if ((float) $data['amount'] <= 0) {
            throw new Exception('REFUND_AMOUNT_MUST_BE_POSITIVE');
        }

        if (blank($data['reason'] ?? null)) {
            throw new Exception('REFUND_REASON_REQUIRED');
        }

        return DB::transaction(function () use ($payment, $data) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $available = (float) $payment->amount - (float) $payment->refunded_amount;
            if ((float) $data['amount'] > $available) {
                throw new Exception('REFUND_AMOUNT_EXCEEDS_PAYMENT');
            }

            $reversal = PaymentReversal::create([
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'actor_id' => $data['actor_id'] ?? null,
                'amount' => $data['amount'],
                'reason' => $data['reason'],
            ]);

            $payment->increment('refunded_amount', (float) $data['amount']);
            $payment->refresh();
            $payment->update([
                'status' => (float) $payment->refunded_amount >= (float) $payment->amount ? 'refunded' : 'partially_refunded',
            ]);

            $invoice = $payment->invoice;
            $invoice->increment('refunded_amount', (float) $data['amount']);
            $this->billingService->refreshInvoiceStatus($invoice->fresh());
            $this->audit('payment', $payment->id, $invoice, 'refund', $data['actor_id'] ?? null, $data['reason']);

            return $reversal;
        });
    }

    public function depositToWallet(string $patientId, string $facilityId, float $amount, string $reason, ?string $actorId = null): Wallet
    {
        if ($amount <= 0) {
            throw new Exception('WALLET_DEPOSIT_AMOUNT_MUST_BE_POSITIVE');
        }

        return DB::transaction(function () use ($patientId, $facilityId, $amount, $reason, $actorId) {
            $wallet = Wallet::firstOrCreate([
                'patient_id' => $patientId,
                'facility_id' => $facilityId,
            ], [
                'balance_amount' => 0,
                'status' => 'active',
            ]);
            $wallet->increment('balance_amount', $amount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'actor_id' => $actorId,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'reason' => $reason,
            ]);

            AuditEvent::create([
                'actor_id' => $actorId,
                'facility_id' => $facilityId,
                'patient_id' => $patientId,
                'action_type' => 'deposit',
                'resource_type' => 'wallet',
                'resource_id' => $wallet->id,
                'reason' => $reason,
                'after_state' => $wallet->fresh()->toArray(),
            ]);

            return $wallet->fresh();
        });
    }

    public function openCashierSession(string $facilityId, string $cashierId): CashierSession
    {
        return CashierSession::create([
            'facility_id' => $facilityId,
            'cashier_id' => $cashierId,
            'status' => 'open',
            'cash_total_amount' => 0,
            'opened_at' => now(),
        ]);
    }

    public function closeCashierSession(CashierSession $session, string $actorId): CashierSession
    {
        $cashTotal = Payment::where('cashier_session_id', $session->id)
            ->where('method', 'cash')
            ->where('status', 'successful')
            ->sum('amount');

        $session->update([
            'status' => 'closed',
            'cash_total_amount' => $cashTotal,
            'closed_at' => now(),
        ]);

        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $session->facility_id,
            'action_type' => 'close',
            'resource_type' => 'cashier_session',
            'resource_id' => $session->id,
            'reason' => 'Cashier session closed.',
            'after_state' => $session->fresh()->toArray(),
        ]);

        return $session->fresh();
    }

    private function debitWallet(?string $walletId, float $amount, ?string $actorId): void
    {
        if (! $walletId) {
            throw new Exception('WALLET_REQUIRED');
        }

        $wallet = Wallet::lockForUpdate()->findOrFail($walletId);
        if ((float) $wallet->balance_amount < $amount) {
            throw new Exception('WALLET_INSUFFICIENT_BALANCE');
        }

        $wallet->decrement('balance_amount', $amount);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'actor_id' => $actorId,
            'transaction_type' => 'debit',
            'amount' => $amount,
            'reason' => 'Invoice payment',
        ]);
    }

    private function audit(string $resourceType, string $resourceId, Invoice $invoice, string $action, ?string $actorId, string $reason): void
    {
        AuditEvent::create([
            'actor_id' => $actorId,
            'facility_id' => $invoice->facility_id,
            'patient_id' => $invoice->patient_id,
            'encounter_id' => $invoice->visit_id,
            'action_type' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'reason' => $reason,
        ]);
    }
}
