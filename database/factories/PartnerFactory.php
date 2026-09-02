<?php

namespace Database\Factories;

use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Enums\PartnerStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => $this->faker->company(),
            'status' => PartnerStatus::ACTIVE->value,
            'trust_level' => $this->faker->numberBetween(1, 5),
        ];
    }
}
