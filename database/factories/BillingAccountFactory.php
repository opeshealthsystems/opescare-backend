<?php
namespace Database\Factories;

use App\Models\BillingAccount;
use App\Models\Facility;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillingAccountFactory extends Factory
{
    protected $model = BillingAccount::class;

    public function definition(): array
    {
        return [
            'patient_id'                => Patient::factory(),
            'facility_id'               => Facility::factory(),
            'status'                    => 'active',
            'outstanding_balance_amount' => 0.00,
        ];
    }
}
