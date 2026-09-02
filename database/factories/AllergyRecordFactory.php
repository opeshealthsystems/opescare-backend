<?php

namespace Database\Factories;

use App\Models\AllergyRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AllergyRecord>
 */
class AllergyRecordFactory extends Factory
{
    protected $model = AllergyRecord::class;

    public function definition(): array
    {
        return [
            'patient_id'  => Patient::factory(),
            'provider_id' => User::factory(),
            'substance'   => $this->faker->randomElement(['Penicillin', 'Aspirin', 'Codeine', 'Latex', 'Sulfa']),
            'severity'    => $this->faker->randomElement(['low', 'moderate', 'high']),
            'status'      => 'active',
        ];
    }
}
