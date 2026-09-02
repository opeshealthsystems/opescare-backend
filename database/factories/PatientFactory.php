<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'health_id'   => 'OC-' . strtoupper($this->faker->bothify('???-###')),
            'first_name'  => $this->faker->firstName(),
            'last_name'   => $this->faker->lastName(),
            'middle_name' => null,
            'date_of_birth' => $this->faker->date('Y-m-d', '-18 years'),
            'is_dob_estimated' => false,
            'sex'         => $this->faker->randomElement(['male', 'female']),
            'phone_number' => $this->faker->phoneNumber(),
            'address'     => $this->faker->address(),
            'emergency_contact' => null,
            'identity_status'   => 'provisional',
            'is_demo'     => false,
        ];
    }
}
