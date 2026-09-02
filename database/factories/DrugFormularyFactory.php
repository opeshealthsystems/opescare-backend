<?php
namespace Database\Factories;

use App\Models\DrugFormulary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DrugFormularyFactory extends Factory {
    protected $model = DrugFormulary::class;

    public function definition(): array {
        return [
            'facility_id'         => null,
            'generic_name'        => $this->faker->word() . ' ' . $this->faker->randomNumber(3),
            'brand_names'         => [$this->faker->word(), $this->faker->word()],
            'drug_code'           => strtoupper($this->faker->lexify('???')) . $this->faker->randomNumber(3),
            'drug_class'          => $this->faker->word(),
            'form'                => $this->faker->randomElement(['tablet','capsule','liquid','injection']),
            'strength'            => $this->faker->randomNumber(3) . 'mg',
            'unit'                => 'mg',
            'is_available'        => true,
            'is_controlled'       => false,
            'requires_prior_auth' => false,
            'restricted_to'       => null,
            'notes'               => null,
            'created_by'          => User::factory(),
        ];
    }
}
