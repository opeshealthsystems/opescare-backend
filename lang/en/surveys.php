<?php

/*
|--------------------------------------------------------------------------
| Patient satisfaction surveys
|--------------------------------------------------------------------------
|
| Question text for App\Services\Patient\SurveyService::TEMPLATES. The
| question KEY (overall_experience, wait_time, ...) is what is stored in
| survey_responses.question_key and must never change; only the wording here
| is translated. Responses already stored keep the text the patient saw.
|
| EN and FR must stay 1:1 (scripts/i18n-audit.php enforces it).
|
*/

return [

    'expired'          => 'Survey has expired.',
    'already_completed' => 'Survey already completed.',
    'unknown_template' => 'Unknown survey template: :template',

    'post_visit' => [
        'overall_experience'    => 'How would you rate your overall experience?',
        'wait_time'             => 'How would you rate the wait time?',
        'provider_communication' => 'How well did the provider communicate with you?',
        'would_recommend'       => 'Would you recommend this facility to others?',
        'comments'              => 'Any additional comments?',
    ],

    'discharge' => [
        'care_quality'           => 'How would you rate the quality of care received?',
        'discharge_instructions' => 'Were your discharge instructions clearly explained?',
        'follow_up_clarity'      => 'How clear were your follow-up instructions?',
        'comments'               => 'Any additional comments?',
    ],

    'telemedicine' => [
        'connection_quality' => 'How was the video/audio connection quality?',
        'overall_experience' => 'How would you rate your telemedicine experience?',
        'would_recommend'    => 'Would you use telemedicine again?',
        'comments'           => 'Any additional comments?',
    ],

    'general' => [
        'overall_experience' => 'How would you rate your overall experience?',
        'would_recommend'    => 'Would you recommend us to others?',
        'comments'           => 'Any additional comments?',
    ],

];
