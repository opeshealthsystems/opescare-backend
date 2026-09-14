<?php

namespace Tests\Feature\Patient;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\SurveyResponse;
use App\Services\Patient\SurveyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit finding S9: every survey question was an English literal inside
 * SurveyService::TEMPLATES, invisible to the i18n audit. The wording now lives
 * in lang/{en,fr}/surveys.php; the question KEY and TYPE — the stored contract
 * in survey_responses — are unchanged, so scored keys still line up and rows
 * already stored are untouched.
 */
class SurveyQuestionsAreBilingualTest extends TestCase
{
    use RefreshDatabase;

    /** The template exactly as it was hardcoded before, so EN is proven unchanged. */
    private const ORIGINAL_POST_VISIT = [
        ['key' => 'overall_experience',    'text' => 'How would you rate your overall experience?',     'type' => 'rating_5'],
        ['key' => 'wait_time',              'text' => 'How would you rate the wait time?',               'type' => 'rating_5'],
        ['key' => 'provider_communication', 'text' => 'How well did the provider communicate with you?', 'type' => 'rating_5'],
        ['key' => 'would_recommend',        'text' => 'Would you recommend this facility to others?',    'type' => 'yes_no'],
        ['key' => 'comments',               'text' => 'Any additional comments?',                        'type' => 'text'],
    ];

    public function test_the_english_template_is_unchanged(): void
    {
        app()->setLocale('en');

        $this->assertSame(self::ORIGINAL_POST_VISIT, app(SurveyService::class)->getTemplate('post_visit'));
    }

    public function test_a_french_patient_is_asked_in_french_with_the_same_keys_and_types(): void
    {
        app()->setLocale('fr');
        $service = app(SurveyService::class);

        $fr = $service->getTemplate('post_visit');

        $this->assertSame(array_column(self::ORIGINAL_POST_VISIT, 'key'), array_column($fr, 'key'));
        $this->assertSame(array_column(self::ORIGINAL_POST_VISIT, 'type'), array_column($fr, 'type'));
        $this->assertSame('Comment évaluez-vous le temps d’attente ?', $fr[1]['text']);

        foreach (['post_visit', 'discharge', 'telemedicine', 'general'] as $template) {
            foreach ($service->getTemplate($template) as $question) {
                $this->assertStringNotContainsString('How would you', $question['text'], "{$template}.{$question['key']}");
                $this->assertStringNotContainsString('Any additional comments', $question['text'], "{$template}.{$question['key']}");
                $this->assertStringNotContainsString('surveys.', $question['text'], "{$template}.{$question['key']} has no translation");
            }
        }
    }

    public function test_a_response_stores_the_question_as_the_patient_saw_it(): void
    {
        app()->setLocale('fr');

        $facility = Facility::factory()->create();
        $patient  = Patient::factory()->create();

        $service = app(SurveyService::class);
        $survey  = $service->createAndSend((string) $patient->id, (string) $facility->id, 'general');

        $service->submitResponse((string) $survey->id, ['overall_experience' => 4, 'would_recommend' => true]);

        $stored = SurveyResponse::where('patient_survey_id', $survey->id)
            ->where('question_key', 'overall_experience')
            ->firstOrFail();

        $this->assertSame('Comment évaluez-vous votre expérience globale ?', $stored->question_text);
        $this->assertSame('rating_5', $stored->response_type);
        $this->assertSame(4, (int) $stored->numeric_response);
    }
}
