<?php

namespace Tests\Feature\Scale;

use App\Jobs\SendPatientPushJob;
use App\Models\Patient;
use App\Support\PatientPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Request paths push through the queue, so a clinician's click never waits on
 * Expo. The job carries exactly what send() would have sent.
 */
class PatientPushIsQueuedTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_dispatches_one_job_with_the_same_payload_and_sends_nothing_inline(): void
    {
        Queue::fake();
        $patient = Patient::factory()->create();

        PatientPush::queue($patient, 'lab_result', 'Title', 'Body', ['lab_order_id' => 'x']);

        Queue::assertPushedOn('default', SendPatientPushJob::class, function (SendPatientPushJob $job) use ($patient) {
            return $job->patient->is($patient)
                && $job->type === 'lab_result'
                && $job->title === 'Title'
                && $job->data === ['lab_order_id' => 'x'];
        });
        Queue::assertCount(1);
    }

    public function test_request_path_callers_use_the_queue_not_the_inline_send(): void
    {
        $files = [
            'app/Http/Controllers/Api/Mobile/MobileDoctorLabOrderController.php',
            'app/Http/Controllers/Api/Mobile/MobileDoctorMessagingController.php',
            'app/Http/Controllers/Api/Mobile/MobileResultsInboxController.php',
            'app/Modules/Governance/Services/ConsentService.php',
            'app/Modules/Governance/Services/EmergencyAccessService.php',
            'app/Services/Family/FamilyInvitationNotifier.php',
            'app/Services/FrontDesk/FrontDeskService.php',
            'app/Services/Pharmacy/PharmacyDeskService.php',
        ];

        foreach ($files as $file) {
            $src = file_get_contents(base_path($file));
            $this->assertStringNotContainsString('PatientPush::send(', $src, "{$file} pushes inline");
            $this->assertStringContainsString('PatientPush::queue(', $src, $file);
        }
    }
}
