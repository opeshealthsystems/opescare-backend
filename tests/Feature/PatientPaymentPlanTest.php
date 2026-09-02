<?php
namespace Tests\Feature;

use App\Models\BillingAccount;
use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientPaymentPlan;
use App\Models\PaymentPlanInstallment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPaymentPlanTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Patient  $patient;
    private Invoice  $invoice;
    private array    $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create();
        $this->patient  = Patient::factory()->create();

        // BillingAccount has unique(patient_id, facility_id) — create explicitly
        $billingAccount = BillingAccount::factory()->create([
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facility->id,
        ]);

        $this->invoice = Invoice::factory()->create([
            'billing_account_id' => $billingAccount->id,
            'patient_id'         => $this->patient->id,
            'facility_id'        => $this->facility->id,
        ]);

        $this->headers = [
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ];
    }

    public function test_create_plan_generates_correct_installment_count(): void
    {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/payment-plans', [
            'patient_id'         => $this->patient->id,
            'invoice_id'         => $this->invoice->id,
            'facility_id'        => $this->facility->id,
            'total_amount'       => 1200.00,
            'down_payment'       => 0.00,
            'installment_amount' => 100.00,
            'installment_count'  => 12,
            'frequency'          => 'monthly',
            'next_due_date'      => Carbon::now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertCreated();

        $planId = $response->json('data.id');
        $this->assertDatabaseCount('payment_plan_installments', 12);

        $installments = PaymentPlanInstallment::where('payment_plan_id', $planId)
            ->orderBy('due_date')
            ->get();

        $this->assertCount(12, $installments);
        $this->assertEquals('pending', $installments->first()->status);
    }

    public function test_installment_dates_advance_by_frequency(): void
    {
        $firstDue = Carbon::now()->addMonth()->startOfDay();

        $response = $this->withHeaders($this->headers)->postJson('/api/v1/payment-plans', [
            'patient_id'         => $this->patient->id,
            'invoice_id'         => $this->invoice->id,
            'facility_id'        => $this->facility->id,
            'total_amount'       => 300.00,
            'down_payment'       => 0.00,
            'installment_amount' => 100.00,
            'installment_count'  => 3,
            'frequency'          => 'monthly',
            'next_due_date'      => $firstDue->format('Y-m-d'),
        ]);

        $response->assertCreated();

        $planId       = $response->json('data.id');
        $installments = PaymentPlanInstallment::where('payment_plan_id', $planId)
            ->orderBy('due_date')
            ->get();

        $this->assertTrue($installments[0]->due_date->isSameDay($firstDue));
        $this->assertTrue($installments[1]->due_date->isSameDay($firstDue->copy()->addMonth()));
        $this->assertTrue($installments[2]->due_date->isSameDay($firstDue->copy()->addMonths(2)));
    }

    public function test_total_amount_validation_fails_when_math_wrong(): void
    {
        $response = $this->withHeaders($this->headers)->postJson('/api/v1/payment-plans', [
            'patient_id'         => $this->patient->id,
            'invoice_id'         => $this->invoice->id,
            'facility_id'        => $this->facility->id,
            'total_amount'       => 999.00,   // wrong — should be 1200
            'down_payment'       => 0.00,
            'installment_amount' => 100.00,
            'installment_count'  => 12,
            'frequency'          => 'monthly',
            'next_due_date'      => Carbon::now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
    }

    public function test_record_payment_updates_installment_status_to_paid(): void
    {
        $plan = PatientPaymentPlan::factory()->create([
            'patient_id'         => $this->patient->id,
            'invoice_id'         => $this->invoice->id,
            'facility_id'        => $this->facility->id,
            'total_amount'       => 300.00,
            'down_payment'       => 0.00,
            'installment_amount' => 100.00,
            'installment_count'  => 3,
            'paid_count'         => 0,
            'frequency'          => 'monthly',
            'status'             => 'active',
            'next_due_date'      => Carbon::now()->addMonth()->format('Y-m-d'),
        ]);

        $installment = PaymentPlanInstallment::factory()->create([
            'payment_plan_id' => $plan->id,
            'due_date'        => Carbon::now()->addMonth()->format('Y-m-d'),
            'amount'          => 100.00,
            'paid_amount'     => 0.00,
            'status'          => 'pending',
        ]);

        $response = $this->withHeaders($this->headers)->postJson(
            "/api/v1/payment-plans/{$plan->id}/installments/{$installment->id}/pay",
            ['amount' => 100.00, 'reference' => 'REF-001'],
        );

        $response->assertOk();
        $this->assertEquals('paid', $response->json('data.status'));
        $this->assertDatabaseHas('patient_payment_plans', ['id' => $plan->id, 'paid_count' => 1]);
    }
}
