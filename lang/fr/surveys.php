<?php

/*
|--------------------------------------------------------------------------
| Enquêtes de satisfaction des patients
|--------------------------------------------------------------------------
|
| Texte des questions de App\Services\Patient\SurveyService::TEMPLATES. La
| CLÉ de la question (overall_experience, wait_time, ...) est ce qui est
| stocké dans survey_responses.question_key et ne doit jamais changer ; seule
| la formulation est traduite ici. Les réponses déjà enregistrées conservent le
| texte que le patient a vu.
|
| EN et FR doivent rester 1:1 (scripts/i18n-audit.php l’impose).
|
*/

return [

    'expired'          => 'Cette enquête a expiré.',
    'already_completed' => 'Cette enquête a déjà été remplie.',
    'unknown_template' => 'Modèle d’enquête inconnu : :template',

    'post_visit' => [
        'overall_experience'    => 'Comment évaluez-vous votre expérience globale ?',
        'wait_time'             => 'Comment évaluez-vous le temps d’attente ?',
        'provider_communication' => 'Le soignant a-t-il bien communiqué avec vous ?',
        'would_recommend'       => 'Recommanderiez-vous cet établissement à d’autres personnes ?',
        'comments'              => 'Avez-vous d’autres commentaires ?',
    ],

    'discharge' => [
        'care_quality'           => 'Comment évaluez-vous la qualité des soins reçus ?',
        'discharge_instructions' => 'Vos consignes de sortie vous ont-elles été clairement expliquées ?',
        'follow_up_clarity'      => 'Les consignes de suivi étaient-elles claires ?',
        'comments'               => 'Avez-vous d’autres commentaires ?',
    ],

    'telemedicine' => [
        'connection_quality' => 'Quelle était la qualité de la connexion vidéo/audio ?',
        'overall_experience' => 'Comment évaluez-vous votre expérience de télémédecine ?',
        'would_recommend'    => 'Utiliseriez-vous à nouveau la télémédecine ?',
        'comments'           => 'Avez-vous d’autres commentaires ?',
    ],

    'general' => [
        'overall_experience' => 'Comment évaluez-vous votre expérience globale ?',
        'would_recommend'    => 'Nous recommanderiez-vous à d’autres personnes ?',
        'comments'           => 'Avez-vous d’autres commentaires ?',
    ],

];
