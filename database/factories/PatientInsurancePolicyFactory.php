<?php

namespace Database\Factories;

use App\Models\InsurancePlan;
use App\Models\Patient;
use App\Models\PatientInsurancePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientInsurancePolicyFactory extends Factory
{
    protected $model = PatientInsurancePolicy::class;

    public function definition(): array
    {
        return [
            'patient_id'               => Patient::factory(),
            'insurance_plan_id'        => InsurancePlan::factory(),
            'policy_number'            => 'POL-' . strtoupper($this->faker->bothify('???-###')),
            'member_id'                => $this->faker->bothify('MBR-####'),
            'relationship_to_primary'  => 'self',
            'status'                   => 'active',
            'effective_date'           => now()->subYear()->toDateString(),
            'expiry_date'              => now()->addYear()->toDateString(),
        ];
    }
}
