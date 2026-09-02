<?php
namespace Database\Factories;

use App\Models\PatientPaymentPlan;
use App\Models\PaymentPlanInstallment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentPlanInstallmentFactory extends Factory
{
    protected $model = PaymentPlanInstallment::class;

    public function definition(): array
    {
        return [
            'payment_plan_id'  => PatientPaymentPlan::factory(),
            'due_date'         => Carbon::now()->addMonth()->format('Y-m-d'),
            'amount'           => 100.00,
            'paid_amount'      => 0.00,
            'status'           => 'pending',
        ];
    }
}
