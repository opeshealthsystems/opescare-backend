<?php
namespace Tests\Feature\Billing;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_payment_plan_with_installments(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $plan = PaymentPlan::create([
            'patient_id'       => $patient->id,
            'facility_id'      => $facility->id,
            'total_amount'     => 300000,
            'installment_count'=> 3,
            'frequency'        => 'monthly',
            'start_date'       => '2026-07-01',
            'status'           => 'active',
        ]);

        for ($i = 0; $i < 3; $i++) {
            PaymentPlanItem::create([
                'payment_plan_id' => $plan->id,
                'due_date'        => now()->addMonths($i)->toDateString(),
                'amount'          => 100000,
                'status'          => 'pending',
            ]);
        }

        $this->assertCount(3, $plan->installments);
        $this->assertEquals(300000, $plan->total_amount);
    }

    public function test_installment_can_be_marked_paid(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $plan = PaymentPlan::create([
            'patient_id'       => $patient->id,
            'facility_id'      => $facility->id,
            'total_amount'     => 100000,
            'installment_count'=> 1,
            'frequency'        => 'monthly',
            'start_date'       => '2026-07-01',
            'status'           => 'active',
        ]);

        $item = PaymentPlanItem::create([
            'payment_plan_id' => $plan->id,
            'due_date'        => '2026-07-01',
            'amount'          => 100000,
            'status'          => 'pending',
        ]);

        $item->update(['status' => 'paid', 'paid_at' => now()]);
        $this->assertEquals('paid', $item->fresh()->status);
    }
}
