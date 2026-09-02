<?php

namespace App\Services\Patient;

use App\Models\PatientSurvey;
use App\Models\SurveyResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SurveyService
{
    private const TEMPLATES = [
        'post_visit' => [
            ['key' => 'overall_experience',     'text' => 'How would you rate your overall experience?',          'type' => 'rating_5'],
            ['key' => 'wait_time',               'text' => 'How would you rate the wait time?',                    'type' => 'rating_5'],
            ['key' => 'provider_communication',  'text' => 'How well did the provider communicate with you?',      'type' => 'rating_5'],
            ['key' => 'would_recommend',         'text' => 'Would you recommend this facility to others?',         'type' => 'yes_no'],
            ['key' => 'comments',                'text' => 'Any additional comments?',                             'type' => 'text'],
        ],
        'discharge' => [
            ['key' => 'care_quality',            'text' => 'How would you rate the quality of care received?',     'type' => 'rating_5'],
            ['key' => 'discharge_instructions',  'text' => 'Were your discharge instructions clearly explained?',  'type' => 'yes_no'],
            ['key' => 'follow_up_clarity',       'text' => 'How clear were your follow-up instructions?',          'type' => 'rating_5'],
            ['key' => 'comments',                'text' => 'Any additional comments?',                             'type' => 'text'],
        ],
        'telemedicine' => [
            ['key' => 'connection_quality',      'text' => 'How was the video/audio connection quality?',          'type' => 'rating_5'],
            ['key' => 'overall_experience',      'text' => 'How would you rate your telemedicine experience?',     'type' => 'rating_5'],
            ['key' => 'would_recommend',         'text' => 'Would you use telemedicine again?',                    'type' => 'yes_no'],
            ['key' => 'comments',                'text' => 'Any additional comments?',                             'type' => 'text'],
        ],
        'general' => [
            ['key' => 'overall_experience',      'text' => 'How would you rate your overall experience?',          'type' => 'rating_5'],
            ['key' => 'would_recommend',         'text' => 'Would you recommend us to others?',                    'type' => 'yes_no'],
            ['key' => 'comments',                'text' => 'Any additional comments?',                             'type' => 'text'],
        ],
    ];

    public function createAndSend(
        string $patientId,
        string $facilityId,
        string $templateKey,
        ?string $visitId = null
    ): PatientSurvey {
        if (! isset(self::TEMPLATES[$templateKey])) {
            throw new \InvalidArgumentException("Unknown survey template: {$templateKey}");
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
            throw new \RuntimeException('Survey has expired.');
        }

        if ($survey->status === 'completed') {
            throw new \RuntimeException('Survey already completed.');
        }

        $template = self::TEMPLATES[$survey->template_key] ?? [];

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

    public function getTemplate(string $templateKey): array
    {
        return self::TEMPLATES[$templateKey] ?? [];
    }
}
