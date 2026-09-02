<?php
namespace Database\Factories;

use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionItemFactory extends Factory
{
    protected $model = PrescriptionItem::class;

    public function definition(): array
    {
        return [
            'prescription_id' => null,
            'drug_name'       => $this->faker->randomElement(['Amoxicillin', 'Ibuprofen', 'Metformin', 'Atorvastatin', 'Omeprazole']),
            'drug_code'       => strtoupper($this->faker->bothify('RX-####')),
            'dose'            => $this->faker->randomElement(['500mg', '200mg', '10mg', '20mg', '40mg']),
            'frequency'       => $this->faker->randomElement(['Once daily', 'Twice daily', 'Three times daily', 'As needed']),
            'route'           => $this->faker->randomElement(['Oral', 'IV', 'Topical', 'Inhaled']),
            'duration_days'   => rand(5, 30),
            'quantity'        => rand(10, 60),
            'status'          => $this->faker->randomElement(['pending', 'dispensed']),
            'dispensed_at'    => null,
            'dispense_notes'  => null,
        ];
    }
}
