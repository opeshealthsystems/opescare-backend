<?php
namespace Database\Factories;

use App\Models\DeliveryRecord;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\PregnancyRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryRecordFactory extends Factory
{
    protected $model = DeliveryRecord::class;

    public function definition(): array
    {
        return [
            'pregnancy_record_id' => PregnancyRecord::factory(),
            'patient_id'          => Patient::factory(),
            'facility_id'         => Facility::factory(),
            'provider_id'         => User::factory(),
            'delivery_date'       => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'delivery_mode'       => $this->faker->randomElement(['svd', 'assisted_vaginal', 'caesarean']),
            'indication'          => null,
            'duration_labour_hours' => $this->faker->randomFloat(1, 2, 24),
            'birth_weight_grams'  => $this->faker->numberBetween(2500, 4000),
            'apgar_1min'          => $this->faker->numberBetween(7, 10),
            'apgar_5min'          => $this->faker->numberBetween(8, 10),
            'neonatal_outcome'    => 'live',
            'complications'       => null,
            'notes'               => null,
        ];
    }
}
