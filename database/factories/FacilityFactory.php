<?php
namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'name'                   => $this->faker->company() . ' Clinic',
            'type'                   => $this->faker->randomElement(['clinic', 'hospital', 'lab']),
            'status'                 => 'active',
            'license_number'         => strtoupper($this->faker->bothify('??-####')),
            'parent_organization_id' => null,
        ];
    }
}
