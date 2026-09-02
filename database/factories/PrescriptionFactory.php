<?php
namespace Database\Factories;

use App\Models\Facility;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'patient_id'    => null,
            'facility_id'   => Facility::factory(),
            'visit_id'      => null,
            // prescriptions.prescribed_by is a uuid (provider user id) — a faker
            // name here crashes Postgres with invalid uuid syntax.
            'prescribed_by' => User::factory(),
            'status'        => $this->faker->randomElement(['active', 'dispensed', 'expired']),
            'notes'         => null,
            'prescribed_at' => now()->subDays(rand(1, 60)),
            'dispensed_at'  => null,
            'expires_at'    => now()->addDays(30),
        ];
    }
}
