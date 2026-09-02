<?php
namespace Tests\Feature\Appointments;

use App\Jobs\BackfillWaitlistJob;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Modules\Appointments\Services\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WaitlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_join_waitlist(): void
    {
        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new WaitlistService();
        $entry   = $service->addToWaitlist(
            patientId:      $patient->id,
            providerId:     $provider->id,
            facilityId:     $facility->id,
            preferredDates: ['2026-07-01', '2026-07-02'],
            reason:         'Urgent review',
        );

        $this->assertInstanceOf(WaitlistEntry::class, $entry);
        $this->assertEquals('waiting', $entry->status);
    }

    public function test_cancellation_triggers_backfill_job(): void
    {
        Queue::fake();

        $patient  = Patient::factory()->create();
        $provider = User::factory()->create();
        $facility = Facility::factory()->create();

        $service = new WaitlistService();
        $service->addToWaitlist($patient->id, $provider->id, $facility->id, ['2026-07-01'], 'Review');

        $service->triggerBackfill($provider->id, $facility->id, '2026-07-01');

        Queue::assertPushed(BackfillWaitlistJob::class);
    }
}
