<?php
namespace Database\Factories;

use App\Models\ConsentRequest;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsentRequestFactory extends Factory
{
    protected $model = ConsentRequest::class;

    public function definition(): array
    {
        return [
            'patient_id'             => null,
            'requesting_facility_id' => Facility::factory(),
            'requesting_user_id'     => null,
            'purpose'                => $this->faker->sentence(),
            'requested_scope'        => ['read_records'],
            'duration_minutes'       => 1440,
            'status'                 => 'pending',
        ];
    }
}
