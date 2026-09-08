<?php

/**
 * Questionnaire de pré-consultation — chaque texte vu par un patient ou un
 * soignant.
 *
 * L'ensemble des questions se trouve dans config/preconsult.php, qui ne porte
 * que les clés ci-dessous. Aucune question, aucune option n'est écrite en dur
 * dans le code.
 *
 * Ce fichier doit rester 1:1 avec lang/en/preconsult.php — mêmes clés, mêmes
 * :placeholders (php scripts/i18n-audit.php est un contrôle CI).
 *
 * Trois règles de rédaction :
 *
 *   1. Aucun terme clinique. On demande au patient ce qu'il vit ; on ne lui
 *      dit jamais ce que cela signifie, et aucun texte ne donne un constat,
 *      une gradation ou un conseil.
 *   2. Aucune obligation. Pas de « obligatoire », pas de « vous devez », pas
 *      de « incomplet », pas de « en retard ». Un patient qui ne répond pas
 *      n'a rien manqué, et chaque écran le dit.
 *   3. Chaque texte destiné au soignant nomme la source. Ce que le patient a
 *      écrit est présenté comme ce que le patient a dit, jamais comme un fait
 *      établi.
 */

return [

    'title' => 'Avant votre rendez-vous',

    'intro' => 'Quelques questions courtes, pour que votre soignant sache déjà pourquoi vous venez. Cela prend environ deux minutes.',

    /*
     * Affiché bien en vue, au patient, sur le formulaire lui-même — et non
     * caché dans une page de politique. La promesse n'est réelle que si la
     * personne qu'elle protège la lit.
     */
    'never_gated' => 'Répondre est entièrement facultatif. Votre rendez-vous se déroule exactement de la même façon, que vous répondiez à tout, à une partie, ou à rien du tout.',

    'optional_hint' => 'Facultatif',

    'saved' => 'Vos réponses ont été enregistrées. Vous pouvez les modifier à tout moment avant de les envoyer.',

    'submitted' => 'Envoyé à l\'établissement. Votre soignant le verra avant votre rendez-vous.',

    'status' => [
        'draft'     => 'Pas encore envoyé',
        'submitted' => 'Envoyé',
    ],

    /*
     * Le bloc de provenance, porté par chaque réponse dans chaque payload.
     * Voir App\Enums\VitalProvenance : cette plateforme marque la source de
     * façon structurelle plutôt que de compter sur la mémoire du lecteur.
     */
    'provenance' => [
        'label'       => 'Déclaré par le patient',
        'description' => 'Le patient a écrit ceci lui-même avant le rendez-vous. Cela n\'a pas été vérifié par un soignant et ne constitue pas un dossier clinique.',
    ],

    /* Affiché en tête de la vue soignant du questionnaire. */
    'clinician_notice' => 'Antécédents déclarés par le patient, recueillis avant la consultation. Vérifiez tout élément sur lequel vous comptez agir.',

    'window' => [
        'not_open_yet'             => 'Ceci s\'ouvrira à l\'approche de la date de votre rendez-vous.',
        'closed'                   => 'Ce rendez-vous est passé, le questionnaire est donc clos.',
        'appointment_not_upcoming' => 'Ce questionnaire ne concerne qu\'un rendez-vous à venir.',
    ],

    'questions' => [

        'presenting_complaint' => [
            'label' => 'Quel est le principal problème pour lequel vous venez ?',
            'help'  => 'Avec vos propres mots. Il n\'y a pas de mauvaise façon de le dire.',
        ],

        'symptom_duration' => [
            'label' => 'Depuis combien de temps cela dure-t-il ?',
            'help'  => 'Une estimation suffit.',
        ],

        'symptom_severity' => [
            'label' => 'À quel point cela vous gêne-t-il ?',
            'help'  => 'Ce que vous ressentez aujourd\'hui.',
        ],

        'current_medicines' => [
            'label' => 'Quels médicaments prenez-vous en ce moment ?',
            'help'  => 'Y compris ce que vous achetez vous-même, la médecine traditionnelle, et ce que vous ne prenez que de temps en temps.',
        ],

        'allergy_status' => [
            'label' => 'Un médicament ou autre chose vous a-t-il déjà provoqué une mauvaise réaction ?',
            'help'  => 'Si vous n\'êtes pas sûr, dites-le. C\'est plus sûr que de deviner.',
        ],

        'allergy_details' => [
            'label' => 'Qu\'est-ce qui a provoqué la réaction, et que s\'est-il passé ?',
            'help'  => 'Par exemple le nom du médicament, et ce que votre corps a fait.',
        ],

        'pregnancy_status' => [
            'label' => 'Pourriez-vous être enceinte ?',
            'help'  => 'Cela change quels médicaments et quels examens d\'imagerie sont sans danger pour vous. Choisissez « Ne me concerne pas » si ce n\'est pas le cas.',
        ],
    ],

    'options' => [

        'symptom_duration' => [
            'today'        => 'Cela a commencé aujourd\'hui',
            'days_1_3'     => '1 à 3 jours',
            'days_4_7'     => '4 à 7 jours',
            'weeks_1_4'    => '1 à 4 semaines',
            'over_a_month' => 'Plus d\'un mois',
        ],

        'symptom_severity' => [
            'mild'     => 'Un peu — je continue comme d\'habitude',
            'moderate' => 'Assez — certaines choses sont plus difficiles',
            'severe'   => 'Beaucoup — je ne peux plus faire mes activités habituelles',
        ],

        'allergy_status' => [
            'none_known' => 'Pas à ma connaissance',
            'yes'        => 'Oui',
            'not_sure'   => 'Je ne suis pas sûr',
        ],

        'pregnancy_status' => [
            'not_applicable' => 'Ne me concerne pas',
            'no'             => 'Non',
            'yes'            => 'Oui',
            'not_sure'       => 'Je ne suis pas sûre',
        ],
    ],

    'errors' => [
        'appointment_not_found'    => 'Nous n\'avons pas trouvé ce rendez-vous.',
        'already_submitted'        => 'Vous avez déjà envoyé ce questionnaire, il ne peut plus être modifié.',
        'patient_not_found'        => 'Patient introuvable.',
        'no_clinical_relationship' => 'Vous n\'avez pas de relation de soins avec ce patient.',
        'consent_required'         => 'Ce patient a demandé que ses antécédents restent soumis à son consentement. Demandez l\'accès pour les consulter.',
        'audit_unavailable'        => 'Ce dossier ne peut pas être ouvert pour le moment car la trace d\'accès n\'a pas pu être écrite.',
    ],
];
