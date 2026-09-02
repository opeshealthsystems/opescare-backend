<?php
namespace Database\Factories;

use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabOrderFactory extends Factory
{
    protected $model = LabOrder::class;

    public function definition(): array
    {
        return [
            'patient_id'          => Patient::factory(),
            'facility_id'         => Facility::factory(),
            'visit_id'            => null,
            'ordered_by'          => null,
            'test_name'           => $this->faker->randomElement(['Full Blood Count', 'Liver Function Test', 'Renal Panel', 'Blood Glucose', 'Lipid Panel']),
            'test_code'           => strtoupper($this->faker->bothify('??###')),
            'urgency'             => 'routine',
            'status'              => 'resulted',
            'clinical_indication' => null,
            'notes'               => null,
            'ordered_at'          => now()->subDays(rand(1, 30)),
            'collected_at'        => now()->subDays(rand(1, 29)),
            'resulted_at'         => now()->subDays(rand(1, 28)),
        ];
    }
}
