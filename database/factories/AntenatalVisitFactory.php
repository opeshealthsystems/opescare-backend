<?php
namespace Database\Factories;

use App\Models\AntenatalVisit;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\PregnancyRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AntenatalVisitFactory extends Factory
{
    protected $model = AntenatalVisit::class;

    public function definition(): array
    {
        return [
            'pregnancy_record_id'   => PregnancyRecord::factory(),
            'patient_id'            => Patient::factory(),
            'facility_id'           => Facility::factory(),
            'provider_id'           => User::factory(),
            'visit_date'            => $this->faker->dateTimeBetween('-8 months', 'now')->format('Y-m-d'),
            'gestational_age_weeks' => $this->faker->numberBetween(8, 40),
            'gestational_age_days'  => $this->faker->numberBetween(0, 6),
            'fundal_height_cm'      => $this->faker->randomFloat(1, 10, 40),
            'fetal_heart_rate'      => $this->faker->numberBetween(110, 160),
            'presentation'          => 'cephalic',
            'weight_kg'             => $this->faker->randomFloat(1, 45, 90),
            'bp_systolic'           => $this->faker->numberBetween(100, 130),
            'bp_diastolic'          => $this->faker->numberBetween(60, 85),
            'urine_protein'         => 'negative',
            'urine_glucose'         => 'negative',
            'oedema'                => 'none',
            'notes'                 => null,
        ];
    }
}
