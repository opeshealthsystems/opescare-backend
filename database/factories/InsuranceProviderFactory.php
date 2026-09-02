<?php

namespace Database\Factories;

use App\Models\InsuranceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class InsuranceProviderFactory extends Factory
{
    protected $model = InsuranceProvider::class;

    public function definition(): array
    {
        return [
            'name'          => $this->faker->company() . ' Insurance',
            'code'          => strtoupper($this->faker->bothify('INS-###')),
            'country_code'  => 'CM',
            'contact_email' => $this->faker->companyEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'status'        => 'active',
        ];
    }
}
