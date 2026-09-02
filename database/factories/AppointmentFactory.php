<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'patient_id'       => Patient::factory(),
            'facility_id'      => Facility::factory(),
            'appointment_type' => $this->faker->randomElement(['general', 'follow_up', 'specialist', 'lab']),
            'status'           => 'scheduled',
            'scheduled_at'     => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'booked_by_type'   => 'staff',
            'reason'           => $this->faker->sentence(),
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => [
            'status'        => 'checked_in',
            'checked_in_at' => now(),
        ]);
    }
}
