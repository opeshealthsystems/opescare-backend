<?php

namespace Database\Factories;

use App\Models\Facility;
use App\Models\InsuranceClaim;
use App\Models\PatientInsurancePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InsuranceClaimFactory extends Factory
{
    protected $model = InsuranceClaim::class;

    public function definition(): array
    {
        return [
            'facility_id'                 => Facility::factory(),
            'patient_insurance_policy_id' => PatientInsurancePolicy::factory(),
            'claim_number'                => 'CLM-' . strtoupper(Str::random(8)),
            'status'                      => 'submitted',
            'claimed_amount'              => $this->faker->randomFloat(2, 100, 5000),
            'approved_amount'             => 0.00,
            'paid_amount'                 => 0.00,
            'submitted_at'                => now(),
        ];
    }
}
