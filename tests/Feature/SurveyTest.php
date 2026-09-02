<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Patient;
use App\Services\Patient\SurveyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    private SurveyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SurveyService::class);
    }

    public function test_can_create_and_send_survey(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $survey = $this->service->createAndSend($patient->id, $facility->id, 'post_visit');

        $this->assertDatabaseHas('patient_surveys', [
            'id'           => $survey->id,
            'status'       => 'sent',
            'template_key' => 'post_visit',
        ]);
    }

    public function test_can_submit_responses(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $survey = $this->service->createAndSend($patient->id, $facility->id, 'post_visit');

        $completed = $this->service->submitResponse($survey->id, [
            'overall_experience'     => 5,
            'wait_time'              => 4,
            'provider_communication' => 5,
            'would_recommend'        => true,
            'comments'               => 'Excellent service!',
        ]);

        $this->assertEquals('completed', $completed->status);
        $this->assertCount(5, $completed->responses);
    }

    public function test_average_score_calculated_correctly(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $survey1 = $this->service->createAndSend($patient->id, $facility->id, 'post_visit');
        $survey2 = $this->service->createAndSend($patient->id, $facility->id, 'post_visit');

        $this->service->submitResponse($survey1->id, [
            'overall_experience'     => 4,
            'wait_time'              => 4,
            'provider_communication' => 4,
        ]);

        $this->service->submitResponse($survey2->id, [
            'overall_experience'     => 2,
            'wait_time'              => 2,
            'provider_communication' => 2,
        ]);

        $scores = $this->service->getSatisfactionScore(
            $facility->id,
            Carbon::now()->subDay(),
            Carbon::now()->addDay(),
        );

        $this->assertEquals(3.0, $scores['overall_experience']);
        $this->assertEquals(3.0, $scores['wait_time']);
    }

    public function test_expire_pending_surveys(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $survey = $this->service->createAndSend($patient->id, $facility->id, 'general');

        // Force expiry
        $survey->update(['expires_at' => Carbon::now()->subDay()]);

        $count = $this->service->expirePendingSurveys();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertDatabaseHas('patient_surveys', ['id' => $survey->id, 'status' => 'expired']);
    }
}
