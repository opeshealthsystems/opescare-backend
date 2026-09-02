<?php
namespace Tests\Feature\PatientEngagement;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPushTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_push_token(): void
    {
        $patient = Patient::factory()->create();
        $patient->update([
            'push_token'    => 'fcm:ExampleFcmToken12345',
            'push_platform' => 'android',
        ]);

        $this->assertEquals('fcm:ExampleFcmToken12345', $patient->fresh()->push_token);
        $this->assertEquals('android', $patient->fresh()->push_platform);
    }
}
