<?php
namespace Tests\Feature\Staff;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Staff\ProviderPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_count_aggregated_for_provider(): void
    {
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();
        $patient  = Patient::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            Appointment::create([
                'patient_id'       => $patient->id,
                'provider_id'      => $provider->id,
                'facility_id'      => $facility->id,
                'scheduled_at'     => now()->subDays($i + 1),
                'appointment_type' => 'consultation',
                'status'           => 'completed',
            ]);
        }

        $service = new ProviderPerformanceService();
        $metrics = $service->getMetrics(
            providerId: $provider->id,
            fromDate:   now()->subMonth()->toDateString(),
            toDate:     now()->toDateString(),
        );

        $this->assertEquals(3, $metrics['total_appointments']);
        $this->assertEquals(3, $metrics['completed_appointments']);
    }

    public function test_no_show_rate_calculated(): void
    {
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();
        $patient  = Patient::factory()->create();

        Appointment::create(['patient_id' => $patient->id, 'provider_id' => $provider->id, 'facility_id' => $facility->id, 'scheduled_at' => now()->subDays(3), 'appointment_type' => 'consultation', 'status' => 'completed']);
        Appointment::create(['patient_id' => $patient->id, 'provider_id' => $provider->id, 'facility_id' => $facility->id, 'scheduled_at' => now()->subDays(2), 'appointment_type' => 'consultation', 'status' => 'no_show']);

        $service = new ProviderPerformanceService();
        $metrics = $service->getMetrics($provider->id, now()->subMonth()->toDateString(), now()->toDateString());

        $this->assertEquals(50.0, $metrics['no_show_rate_pct']);
    }
}
