<?php
namespace Database\Factories;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\PregnancyRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PregnancyRecordFactory extends Factory
{
    protected $model = PregnancyRecord::class;

    public function definition(): array
    {
        $lmp = $this->faker->dateTimeBetween('-9 months', '-4 months');
        $edd = (clone $lmp)->modify('+280 days');

        return [
            'patient_id'       => Patient::factory(),
            'facility_id'      => Facility::factory(),
            'provider_id'      => User::factory(),
            'gravida'          => $this->faker->numberBetween(1, 5),
            'para'             => $this->faker->numberBetween(0, 4),
            'lmp'              => $lmp->format('Y-m-d'),
            'edd'              => $edd->format('Y-m-d'),
            'pregnancy_status' => 'active',
            'blood_type'       => $this->faker->randomElement(['A', 'B', 'AB', 'O']),
            'rhesus_factor'    => $this->faker->randomElement(['positive', 'negative']),
            'high_risk'        => false,
            'risk_factors'     => [],
            'notes'            => null,
            'registered_at'    => now(),
        ];
    }
}
