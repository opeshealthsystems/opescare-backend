<?php
namespace Tests\Feature\Portal;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPortalLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_patient_relationship(): void
    {
        $patient = Patient::factory()->create(['health_id' => 'OC-TEST-001', 'is_demo' => false]);
        $user = User::factory()->create(['patient_id' => $patient->id]);

        $this->assertNotNull($user->patient);
        $this->assertEquals($patient->id, $user->patient->id);
    }

    public function test_user_without_patient_returns_null(): void
    {
        $user = User::factory()->create(['patient_id' => null]);
        $this->assertNull($user->patient);
    }
}
