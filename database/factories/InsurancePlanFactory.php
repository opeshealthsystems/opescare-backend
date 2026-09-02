<?php

namespace Database\Factories;

use App\Models\InsurancePlan;
use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsurancePlanFactory extends Factory
{
    protected $model = InsurancePlan::class;

    public function definition(): array
    {
        return [
            'insurance_provider_id'       => InsuranceProvider::factory(),
            'name'                        => $this->faker->words(3, true) . ' Plan',
            'plan_code'                   => strtoupper($this->faker->bothify('PLN-###')),
            'plan_type'                   => $this->faker->randomElement(['nhia', 'private', 'employer', 'mutual']),
            'requires_preauthorization'   => false,
            'cashless_available'          => true,
            'status'                      => 'active',
        ];
    }
}
