<?php
namespace Database\Factories;

use App\Models\BillingAccount;
use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 5000);
        return [
            'billing_account_id'            => BillingAccount::factory(),
            'patient_id'                    => Patient::factory(),
            'facility_id'                   => Facility::factory(),
            'invoice_number'                => 'INV-' . strtoupper(Str::random(8)),
            'status'                        => 'issued',
            'subtotal_amount'               => $amount,
            'discount_amount'               => 0.00,
            'insurance_covered_amount'      => 0.00,
            'patient_responsibility_amount' => $amount,
            'paid_amount'                   => 0.00,
            'refunded_amount'               => 0.00,
            'balance_amount'                => $amount,
        ];
    }
}
