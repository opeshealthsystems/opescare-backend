<?php
namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\ProviderAvailability;
use App\Models\User;
use App\Modules\Appointments\Services\PatientSelfBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSelfBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_set_availability(): void
    {
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $slot = ProviderAvailability::create([
            'provider_id'           => $provider->id,
            'facility_id'           => $facility->id,
            'day_of_week'           => 1,
            'starts_at'             => '08:00',
            'ends_at'               => '12:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ]);

        $this->assertEquals(1, $slot->day_of_week);
        $this->assertStringStartsWith('08:00', $slot->starts_at);
    }

    public function test_patient_can_self_book_available_slot(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        ProviderAvailability::create([
            'provider_id'           => $provider->id,
            'facility_id'           => $facility->id,
            'day_of_week'           => now()->addDay()->dayOfWeekIso,
            'starts_at'             => '08:00',
            'ends_at'               => '17:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ]);

        $service     = new PatientSelfBookingService();
        $appointment = $service->bookSlot(
            patientId:  $patient->id,
            providerId: $provider->id,
            facilityId: $facility->id,
            dateTime:   now()->addDay()->setTime(9, 0),
            reason:     'Annual checkup',
        );

        $this->assertNotNull($appointment);
        $this->assertEquals('scheduled', $appointment->status);
    }

    public function test_double_booking_is_blocked(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        ProviderAvailability::create([
            'provider_id'           => $provider->id,
            'facility_id'           => $facility->id,
            'day_of_week'           => now()->addDay()->dayOfWeekIso,
            'starts_at'             => '08:00',
            'ends_at'               => '17:00',
            'slot_duration_minutes' => 30,
            'is_active'             => true,
        ]);

        $service  = new PatientSelfBookingService();
        $dateTime = now()->addDay()->setTime(9, 0);

        $service->bookSlot($patient->id, $provider->id, $facility->id, $dateTime, 'First');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SLOT_ALREADY_BOOKED');

        $service->bookSlot($patient->id, $provider->id, $facility->id, $dateTime, 'Second');
    }
}
