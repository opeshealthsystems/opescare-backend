<?php

namespace Database\Factories;

use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Diagnosis>
 */
class DiagnosisFactory extends Factory
{
    protected $model = Diagnosis::class;

    public function definition(): array
    {
        return [
            'patient_id'   => Patient::factory(),
            'visit_id'     => Str::uuid(),  // SQLite does not enforce FK — no visit row needed in tests
            'provider_id'  => User::factory(),
            'code_system'  => 'ICD-10',
            'code'         => strtoupper($this->faker->bothify('?##.#')),
            'display_name' => $this->faker->words(3, true),
            'status'       => 'active',
            'is_primary'   => true,
        ];
    }
}
