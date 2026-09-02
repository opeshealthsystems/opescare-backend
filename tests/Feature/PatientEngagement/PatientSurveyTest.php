<?php
namespace Tests\Feature\PatientEngagement;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSurveyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_survey_template(): void
    {
        $facility = Facility::factory()->create();

        $template = SurveyTemplate::create([
            'facility_id' => $facility->id,
            'title'       => 'Post-Visit Satisfaction Survey',
            'trigger'     => 'post_appointment',
            'questions'   => [
                ['id' => 'q1', 'text' => 'How satisfied were you overall?', 'type' => 'scale_1_5'],
                ['id' => 'q2', 'text' => 'Would you recommend us?', 'type' => 'yes_no'],
                ['id' => 'q3', 'text' => 'Any additional feedback?', 'type' => 'text'],
            ],
            'is_active'   => true,
        ]);

        $this->assertEquals('post_appointment', $template->trigger);
        $this->assertCount(3, $template->questions);
    }

    public function test_patient_can_submit_survey_response(): void
    {
        $patient  = Patient::factory()->create();
        $facility = Facility::factory()->create();

        $template = SurveyTemplate::create([
            'facility_id' => $facility->id,
            'title'       => 'Satisfaction Survey',
            'trigger'     => 'post_appointment',
            'questions'   => [['id' => 'q1', 'text' => 'How satisfied?', 'type' => 'scale_1_5']],
            'is_active'   => true,
        ]);

        $response = SurveyTemplateResponse::create([
            'survey_template_id' => $template->id,
            'patient_id'         => $patient->id,
            'facility_id'        => $facility->id,
            'answers'            => [['question_id' => 'q1', 'answer' => 4]],
            'overall_score'      => 4,
            'submitted_at'       => now(),
        ]);

        $this->assertEquals(4, $response->overall_score);
        $this->assertNotNull($response->submitted_at);
    }
}
