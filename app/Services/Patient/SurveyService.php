<?php

namespace App\Services\Patient;

use App\Models\PatientSurvey;
use App\Models\SurveyResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SurveyService
{
    /**
     * Question templates. `key` and `type` are the stored contract
     * (survey_responses.question_key / response_type) and must never change;
     * the wording lives in lang/{en,fr}/surveys.php under
     * surveys.<template>.<key> and is resolved in the request locale by
     * getTemplate(), so a French-speaking patient is asked in French.
     */
    private const TEMPLATES = [
        'post_visit' => [
            ['key' => 'overall_experience',     'type' => 'rating_5'],
            ['key' => 'wait_time',               'type' => 'rating_5'],
            ['key' => 'provider_communication',  'type' => 'rating_5'],
            ['key' => 'would_recommend',         'type' => 'yes_no'],
            ['key' => 'comments',                'type' => 'text'],
        ],
        'discharge' => [
            ['key' => 'care_quality',            'type' => 'rating_5'],
            ['key' => 'discharge_instructions',  'type' => 'yes_no'],
            ['key' => 'follow_up_clarity',       'type' => 'rating_5'],
            ['key' => 'comments',                'type' => 'text'],
        ],
        'telemedicine' => [
            ['key' => 'connection_quality',      'type' => 'rating_5'],
            ['key' => 'overall_experience',      'type' => 'rating_5'],
            ['key' => 'would_recommend',         'type' => 'yes_no'],
            ['key' => 'comments',                'type' => 'text'],
        ],
        'general' => [
            ['key' => 'overall_experience',      'type' => 'rating_5'],
            ['key' => 'would_recommend',         'type' => 'yes_no'],
            ['key' => 'comments',                'type' => 'text'],
        ],
    ];

    public function createAndSend(
        string $patientId,
        string $facilityId,
        string $templateKey,
        ?string $visitId = null
    ): PatientSurvey {
        if (! isset(self::TEMPLATES[$templateKey])) {
            throw new \InvalidArgumentException(__('surveys.unknown_template', ['template' => $templateKey]));
        }

        $survey = PatientSurvey::create([
            'patient_id'   => $patientId,
            'facility_id'  => $facilityId,
            'visit_id'     => $visitId,
            'template_key' => $templateKey,
            'status'       => 'sent',
            'sent_at'      => Carbon::now(),
            'expires_at'   => Carbon::now()->addDays(7),
        ]);

        return $survey;
    }

    public function submitResponse(string $surveyId, array $responses): PatientSurvey
    {
        $survey = PatientSurvey::findOrFail($surveyId);

        if ($survey->status === 'expired') {
            throw new \RuntimeException(__('surveys.expired'));
        }

        if ($survey->status === 'completed') {
            throw new \RuntimeException(__('surveys.already_completed'));
        }

        // question_text is stored as the patient saw it, in their locale.
        // Rows already stored keep whatever wording they were answered under.
        $template = $this->getTemplate($survey->template_key);

        DB::transaction(function () use ($survey, $responses, $template) {
            foreach ($template as $question) {
                if (! isset($responses[$question['key']])) {
                    continue;
                }

                $value = $responses[$question['key']];

                SurveyResponse::create([
                    'patient_survey_id' => $survey->id,
                    'question_key'      => $question['key'],
                    'question_text'     => $question['text'],
                    'response_type'     => $question['type'],
                    'numeric_response'  => in_array($question['type'], ['rating_5', 'rating_10'])
                        ? (int) $value
                        : ($question['type'] === 'yes_no' ? ($value ? 1 : 0) : null),
                    'text_response'     => $question['type'] === 'text' ? (string) $value : null,
                ]);
            }

            $survey->update([
                'status'       => 'completed',
                'completed_at' => Carbon::now(),
            ]);
        });

        return $survey->fresh();
    }

    public function getSatisfactionScore(string $facilityId, Carbon $from, Carbon $to): array
    {
        $responses = SurveyResponse::whereHas('survey', function ($q) use ($facilityId, $from, $to) {
            $q->where('facility_id', $facilityId)
              ->where('status', 'completed')
              ->whereBetween('completed_at', [$from, $to]);
        })->whereIn('response_type', ['rating_5', 'rating_10'])
          ->whereNotNull('numeric_response')
          ->get(['question_key', 'numeric_response']);

        return $responses
            ->groupBy('question_key')
            ->map(fn ($group) => round($group->avg('numeric_response'), 2))
            ->toArray();
    }

    public function expirePendingSurveys(): int
    {
        return PatientSurvey::whereIn('status', ['pending', 'sent'])
            ->where('expires_at', '<', Carbon::now())
            ->update(['status' => 'expired']);
    }

    /**
     * The questions of a template with `text` resolved in the current locale.
     *
     * @return list<array{key: string, text: string, type: string}>
     */
    public function getTemplate(string $templateKey): array
    {
        $questions = self::TEMPLATES[$templateKey] ?? [];

        return array_map(fn (array $q) => [
            'key'  => $q['key'],
            'text' => __("surveys.{$templateKey}.{$q['key']}"),
            'type' => $q['type'],
        ], $questions);
    }
}
