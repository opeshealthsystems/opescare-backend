<?php

namespace Tests\Feature;

use App\Models\BillingAccount;
use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Wallet;
use App\Modules\Billing\Services\BillingService;
use App\Modules\Billing\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_creation_calculates_patient_responsibility_and_audits()
    {
        [$patient, $facility, $cashier] = $this->billingActors();

        $invoice = app(BillingService::class)->createInvoice([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'items' => [
                ['description' => 'Consultation fee', 'service_code' => 'CONSULT', 'quantity' => 1, 'unit_price' => 5000],
                ['description' => 'Lab fee', 'service_code' => 'LAB-MALARIA', 'quantity' => 2, 'unit_price' => 1500, 'discount_amount' => 500],
            ],
            'insurance_covered_amount' => 1000,
            'actor_id' => $cashier->id,
        ]);

        $this->assertEquals('issued', $invoice->status);
        $this->assertEquals(8000, (int) $invoice->subtotal_amount);
        $this->assertEquals(500, (int) $invoice->discount_amount);
        $this->assertEquals(1000, (int) $invoice->insurance_covered_amount);
        $this->assertEquals(6500, (int) $invoice->patient_responsibility_amount);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'invoice',
            'resource_id' => $invoice->id,
            'action_type' => 'create',
        ]);
    }

    public function test_successful_cash_payment_generates_receipt_and_marks_invoice_paid()
    {
        [$patient, $facility, $cashier] = $this->billingActors();
        $invoice = $this->invoiceFor($patient, $facility, 3000);

        $payment = app(PaymentService::class)->recordPayment($invoice, [
            'amount' => 3000,
            'method' => 'cash',
            'cashier_id' => $cashier->id,
        ]);

        $this->assertEquals('successful', $payment->status);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals(0, (int) $invoice->fresh()->balance_amount);
        $this->assertDatabaseHas('receipts', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount' => 3000,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'payment',
            'resource_id' => $payment->id,
            'action_type' => 'receive',
        ]);
    }

    public function test_partial_payment_updates_invoice_to_partially_paid()
    {
        [$patient, $facility, $cashier] = $this->billingActors();
        $invoice = $this->invoiceFor($patient, $facility, 5000);

        app(PaymentService::class)->recordPayment($invoice, [
            'amount' => 2000,
            'method' => 'mobile_money',
            'cashier_id' => $cashier->id,
        ]);

        $this->assertEquals('partially_paid', $invoice->fresh()->status);
        $this->assertEquals(3000, (int) $invoice->fresh()->balance_amount);
    }

    public function test_payment_amount_cannot_be_negative()
    {
        [$patient, $facility, $cashier] = $this->billingActors();
        $invoice = $this->invoiceFor($patient, $facility, 3000);

        $this->expectExceptionMessage('PAYMENT_AMOUNT_MUST_BE_POSITIVE');

        app(PaymentService::class)->recordPayment($invoice, [
            'amount' => -100,
            'method' => 'cash',
            'cashier_id' => $cashier->id,
        ]);
    }

    public function test_refund_creates_reversal_without_deleting_original_payment()
    {
        [$patient, $facility, $cashier] = $this->billingActors();
        $invoice = $this->invoiceFor($patient, $facility, 3000);
        $payment = app(PaymentService::class)->recordPayment($invoice, [
            'amount' => 3000,
            'method' => 'cash',
            'cashier_id' => $cashier->id,
        ]);

        $refund = app(PaymentService::class)->refund($payment, [
            'amount' => 1000,
            'reason' => 'Service cancelled before delivery',
            'actor_id' => $cashier->id,
        ]);

        $this->assertEquals('partially_refunded', $payment->fresh()->status);
        $this->assertEquals(1000, (int) $refund->amount);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'amount' => 3000]);
        $this->assertDatabaseHas('payment_reversals', [
            'payment_id' => $payment->id,
            'amount' => 1000,
            'reason' => 'Service cancelled before delivery',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'payment',
            'resource_id' => $payment->id,
            'action_type' => 'refund',
        ]);
    }

    public function test_wallet_deposit_and_wallet_payment_work()
    {
        [$patient, $facility, $cashier] = $this->billingActors();
        $invoice = $this->invoiceFor($patient, $facility, 2500);

        $wallet = app(PaymentService::class)->depositToWallet($patient->id, $facility->id, 4000, 'prepayment', $cashier->id);
        $payment = app(PaymentService::class)->recordPayment($invoice, [
            'amount' => 2500,
            'method' => 'wallet',
            'cashier_id' => $cashier->id,
            'wallet_id' => $wallet->id,
        ]);

        $this->assertEquals(1500, (int) $wallet->fresh()->balance_amount);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals('wallet', $payment->method);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => 'debit',
            'amount' => 2500,
        ]);
    }

    public function test_cashier_session_closes_with_cash_totals()
    {
        [$patient, $facility, $cashier] = $this->billingActors();
        $session = app(PaymentService::class)->openCashierSession($facility->id, $cashier->id);
        $invoice = $this->invoiceFor($patient, $facility, 3000);
        app(PaymentService::class)->recordPayment($invoice, [
            'amount' => 3000,
            'method' => 'cash',
            'cashier_id' => $cashier->id,
            'cashier_session_id' => $session->id,
        ]);

        $closed = app(PaymentService::class)->closeCashierSession($session, $cashier->id);

        $this->assertEquals('closed', $closed->status);
        $this->assertEquals(3000, (int) $closed->cash_total_amount);
    }

    private function billingActors(): array
    {
        $patient = Patient::create(['health_id' => 'OC-BILL-001', 'first_name' => 'Amina', 'last_name' => 'Billing']);
        $facility = Facility::create(['name' => 'Billing Clinic', 'type' => 'clinic', 'status' => 'active']);
        $cashier = User::create(['name' => 'Cashier', 'email' => 'cashier@test.com', 'password' => 'password', 'primary_facility_id' => $facility->id]);

        return [$patient, $facility, $cashier];
    }

    private function invoiceFor(Patient $patient, Facility $facility, int $amount): Invoice
    {
        return app(BillingService::class)->createInvoice([
            'patient_id' => $patient->id,
            'facility_id' => $facility->id,
            'items' => [
                ['description' => 'Consultation fee', 'service_code' => 'CONSULT', 'quantity' => 1, 'unit_price' => $amount],
            ],
        ]);
    }
}
