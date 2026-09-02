<?php
namespace Database\Factories;

use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyLinkFactory extends Factory
{
    protected $model = FamilyLink::class;

    public function definition(): array
    {
        return [
            'guardian_user_id'           => User::factory(),
            'dependent_patient_id'       => Patient::factory(),
            'relationship'               => $this->faker->randomElement(['parent', 'grandparent', 'caregiver', 'spouse']),
            'access_level'               => 'read_only',
            'status'                     => 'active',
            'created_by'                 => 'self_registered',
            'invite_token'               => null,
            'invite_expires_at'          => null,
            'notification_prefs'         => [],
            'age_transition_notified_at' => null,
            'age_transition_expires_at'  => null,
        ];
    }
}
