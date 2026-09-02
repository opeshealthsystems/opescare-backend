<?php
namespace Tests\Feature;

use App\Models\AppointmentWaitlist;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\Appointments\WaitlistService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitlistTest extends TestCase
{
    use RefreshDatabase;

    private WaitlistService $service;
    private Patient $patient;
    private Facility $facility;
    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(WaitlistService::class);
        $this->patient  = Patient::factory()->create();
        $this->facility = Facility::factory()->create();
        $this->provider = User::factory()->create();
    }

    public function test_can_add_patient_to_waitlist(): void
    {
        $entry = $this->service->addToWaitlist([
            'patient_id'              => $this->patient->id,
            'facility_id'             => $this->facility->id,
            'provider_id'             => $this->provider->id,
            'appointment_type'        => 'consultation',
            'preferred_earliest_date' => '2026-06-01',
            'preferred_latest_date'   => '2026-06-30',
            'urgency'                 => 'routine',
            'status'                  => 'waiting',
        ]);

        $this->assertInstanceOf(AppointmentWaitlist::class, $entry);
        $this->assertEquals('waiting', $entry->status);
        $this->assertDatabaseHas('appointment_waitlists', ['id' => $entry->id]);
    }

    public function test_notify_next_in_line_returns_highest_urgency_entry(): void
    {
        $this->service->addToWaitlist([
            'patient_id'       => Patient::factory()->create()->id,
            'facility_id'      => $this->facility->id,
            'provider_id'      => $this->provider->id,
            'appointment_type' => 'consultation',
            'urgency'          => 'routine',
            'status'           => 'waiting',
        ]);

        $urgentPatient = Patient::factory()->create();
        $urgentEntry   = $this->service->addToWaitlist([
            'patient_id'       => $urgentPatient->id,
            'facility_id'      => $this->facility->id,
            'provider_id'      => $this->provider->id,
            'appointment_type' => 'consultation',
            'urgency'          => 'urgent',
            'status'           => 'waiting',
        ]);

        $notified = $this->service->notifyNextInLine(
            $this->facility->id,
            $this->provider->id,
            Carbon::tomorrow()
        );

        $this->assertNotNull($notified);
        $this->assertEquals($urgentEntry->id, $notified->id);
        $this->assertEquals('notified', $notified->status);
        $this->assertNotNull($notified->notified_at);
    }

    public function test_book_from_waitlist_updates_status(): void
    {
        $entry = $this->service->addToWaitlist([
            'patient_id'       => $this->patient->id,
            'facility_id'      => $this->facility->id,
            'appointment_type' => 'follow_up',
            'urgency'          => 'routine',
            'status'           => 'notified',
        ]);

        $fakeAppointmentId = \Illuminate\Support\Str::uuid()->toString();
        $booked            = $this->service->bookFromWaitlist($entry->id, $fakeAppointmentId);

        $this->assertEquals('booked', $booked->status);
        $this->assertEquals($fakeAppointmentId, $booked->booked_appointment_id);
    }

    public function test_expire_old_entries_marks_past_entries_expired(): void
    {
        AppointmentWaitlist::create([
            'patient_id'            => $this->patient->id,
            'facility_id'           => $this->facility->id,
            'appointment_type'      => 'scan',
            'urgency'               => 'routine',
            'status'                => 'waiting',
            'preferred_latest_date' => Carbon::yesterday()->toDateString(),
        ]);

        $future = $this->service->addToWaitlist([
            'patient_id'            => $this->patient->id,
            'facility_id'           => $this->facility->id,
            'appointment_type'      => 'scan',
            'urgency'               => 'routine',
            'status'                => 'waiting',
            'preferred_latest_date' => Carbon::parse('next Monday')->toDateString(),
        ]);

        $count = $this->service->expireOldEntries();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('appointment_waitlists', [
            'id'     => $future->id,
            'status' => 'waiting',
        ]);
    }
}
