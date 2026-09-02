<?php
namespace Database\Factories;

use App\Models\LabOrder;
use App\Models\LabResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabResultFactory extends Factory
{
    protected $model = LabResult::class;

    public function definition(): array
    {
        return [
            'lab_order_id'    => LabOrder::factory(),
            'patient_id'      => null,
            'parameter_name'  => $this->faker->randomElement(['Haemoglobin', 'WBC', 'Platelet Count', 'Glucose', 'Creatinine', 'ALT', 'AST']),
            'value'           => (string) $this->faker->randomFloat(2, 1, 200),
            'unit'            => $this->faker->randomElement(['g/dL', '×10³/µL', 'mg/dL', 'µmol/L', 'U/L']),
            'reference_range' => '4.0–11.0',
            'flag'            => $this->faker->randomElement([null, null, null, 'H', 'L']),
            'notes'           => null,
            'verified_by'     => null,
            'resulted_at'     => now()->subDays(rand(1, 30)),
        ];
    }
}
