<?php

namespace Database\Factories;

use App\Models\IntegrationClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IntegrationClient>
 */
class IntegrationClientFactory extends Factory
{
    protected $model = IntegrationClient::class;

    public function definition(): array
    {
        return [
            'client_id'     => 'client_' . Str::lower(Str::random(16)),
            'client_secret' => hash('sha256', Str::random(32)),
            'facility_id'   => (string) Str::uuid(),
            'name'          => $this->faker->company() . ' Integration',
            'description'   => $this->faker->sentence(),
            'contact_email' => $this->faker->safeEmail(),
            'scopes'        => ['patients:read'],
            'status'        => 'active',
            'environment'   => 'sandbox',
            'request_count' => 0,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }

    public function production(): static
    {
        return $this->state(fn () => ['environment' => 'production']);
    }
}
