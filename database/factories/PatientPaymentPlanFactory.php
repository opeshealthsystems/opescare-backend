<?php
namespace Database\Factories;

use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientPaymentPlan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientPaymentPlanFactory extends Factory
{
    protected $model = PatientPaymentPlan::class;

    public function definition(): array
    {
        $installmentAmount = 100.00;
        $installmentCount  = 3;
        $total             = $installmentAmount * $installmentCount;

        return [
            'patient_id'         => Patient::factory(),
            'invoice_id'         => Invoice::factory(),
            'facility_id'        => Facility::factory(),
            'total_amount'       => $total,
            'down_payment'       => 0.00,
            'installment_amount' => $installmentAmount,
            'installment_count'  => $installmentCount,
            'paid_count'         => 0,
            'frequency'          => 'monthly',
            'status'             => 'active',
            'next_due_date'      => Carbon::now()->addMonth()->format('Y-m-d'),
            'started_at'         => now(),
        ];
    }
}
