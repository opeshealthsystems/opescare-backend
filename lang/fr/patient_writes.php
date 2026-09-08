<?php

/**
 * Écritures patient — les deux seules choses qu'un patient peut ajouter à son
 * propre dossier : une constante qu'il a mesurée chez lui, et une demande
 * d'orientation.
 *
 * Traduction 1:1 de lang/en/patient_writes.php : mêmes clés, mêmes
 * :placeholders (php scripts/i18n-audit.php est un contrôle CI).
 *
 * Deux règles gouvernent la formulation :
 *
 *   1. Une mesure auto-déclarée n'est jamais décrite en langage clinique. On
 *      dit « enregistré par vous », jamais « votre tension » ; la plateforme
 *      ne la classe pas, et le seul avis qu'elle donne est « contactez un
 *      soignant » — un conseil, pas un constat.
 *   2. Une demande d'orientation n'est jamais décrite comme une orientation.
 *      Aucune chaîne du bloc `referral_request` ne dit « orienté », « approuvé »
 *      ou « envoyé » d'une demande qu'aucun clinicien n'a traitée.
 */

return [

    /*
     * Provenance — l'information la plus importante de toute mesure.
     * Voir App\Enums\VitalProvenance.
     */
    'provenance' => [
        'self_reported' => [
            'label'       => 'Enregistré par vous',
            'description' => 'Vous avez pris cette mesure vous-même. Elle n\'a pas été vérifiée par un soignant et n\'est pas une mesure clinique.',
        ],
        'facility_measured' => [
            'label'       => 'Mesuré dans un établissement',
            'description' => 'Prise par le personnel soignant lors d\'une consultation.',
        ],
        'laboratory_result' => [
            'label'       => 'Résultat de laboratoire',
            'description' => 'Produit par un laboratoire à partir d\'un prélèvement.',
        ],
        'partner_reported' => [
            'label'       => 'Transmis par un autre système',
            'description' => 'Transmis via l\'API d\'interopérabilité par un système externe. Personne ici ne l\'a observé.',
        ],
    ],

    /* Ce qu'un patient peut enregistrer, et le nom de chaque nombre. */
    'measures' => [
        'blood_pressure' => [
            'label'     => 'Tension artérielle',
            'primary'   => 'Systolique (chiffre du haut)',
            'secondary' => 'Diastolique (chiffre du bas)',
        ],
        'weight' => [
            'label'   => 'Poids',
            'primary' => 'Poids',
        ],
        'temperature' => [
            'label'   => 'Température',
            'primary' => 'Température',
        ],
        'blood_sugar' => [
            'label'   => 'Glycémie',
            'primary' => 'Glycémie',
        ],
    ],

    /*
     * Moment de la mesure, pour la glycémie. C'est l'état de jeûne que les
     * tables cliniques ne savent pas enregistrer — seul le patient le sait, on
     * le lui demande donc directement.
     */
    'contexts' => [
        'fasting'     => 'À jeun',
        'before_meal' => 'Avant un repas',
        'after_meal'  => 'Après un repas',
        'bedtime'     => 'Au coucher',
        'random'      => 'À un autre moment',
    ],

    /* Noms des champs, tels qu'ils apparaissent dans un refus. */
    'fields' => [
        'value'       => 'La valeur',
        'systolic'    => 'Le chiffre systolique (du haut)',
        'diastolic'   => 'Le chiffre diastolique (du bas)',
        'measured_at' => 'L\'heure de la mesure',
    ],

    /*
     * Refus. Chacun nomme ce qui a été rejeté et pourquoi. Rien n'est corrigé,
     * ramené dans les bornes ni enregistré : une valeur que la plateforme
     * refuse est une valeur dont le patient est informé.
     */
    'errors' => [
        'out_of_range_title' => 'Cette mesure n\'a pas été enregistrée.',
        // « se situer » et « accepté(e) » sont évités : :field et :measure
        // changent de genre (« La valeur », « Le chiffre »), donc toute
        // formulation qui s'accorde serait fausse une fois sur deux.
        'out_of_range'       => ':field doit se situer entre :min et :max :unit. Vous avez saisi :submitted : rien n\'a été enregistré. Vérifiez votre appareil et saisissez la mesure à nouveau.',
        'reading_rejected'   => 'Cette mesure n\'a pas été enregistrée.',
        'missing_value'      => ':field est nécessaire pour enregistrer cette mesure.',
        'unsupported_unit'   => ':measure : les unités possibles sont :units. Rien n\'a été enregistré.',
        'unsupported_context' => 'Choisissez parmi : :contexts. Rien n\'a été enregistré.',
        'context_not_applicable' => ':measure ne s\'enregistre pas avec un moment de la journée. Rien n\'a été enregistré.',
        'diastolic_not_below_systolic' => 'Le chiffre du bas doit être inférieur à celui du haut. Les deux semblent avoir été inversés : rien n\'a été enregistré.',
        'measured_at_range'  => 'Une mesure ne peut pas être dans le futur, et la date saisie n\'est pas acceptable pour cette application. Rien n\'a été enregistré.',
        'reading_not_found'  => 'Cette mesure ne figure pas dans votre carnet.',
        'already_voided'     => 'Vous avez déjà retiré cette mesure.',
        'invalid_filter'     => 'Ce filtre n\'est pas reconnu par cette liste.',

        'request_rejected'   => 'Cette demande n\'a pas été envoyée.',
        'too_many_open_requests' => 'Vous avez déjà :max demandes en attente de réponse. Attendez une réponse avant d\'en envoyer une autre.',
        'duplicate_open_request' => 'Vous avez déjà une demande en :specialty en attente de réponse (:reference).',
        'facility_not_found' => 'Cet établissement ne figure pas dans l\'annuaire : la demande n\'a pas été envoyée.',
        'request_not_found'  => 'Cette demande n\'est pas la vôtre.',
        'not_withdrawable'   => 'Cette demande est :status : elle ne peut plus être retirée.',
    ],

    /*
     * Le seul avis que la plateforme donne sur une mesure auto-déclarée. C'est
     * une invitation à consulter, jamais une interprétation de la valeur.
     */
    'advisory' => [
        'seek_care' => 'Votre mesure a été enregistrée telle que vous l\'avez saisie. Elle se situe hors des valeurs avec lesquelles on peut attendre sans risque : contactez un soignant.',
    ],

    /* Spécialités suggérées. Le patient peut aussi saisir la sienne. */
    'specialties' => [
        'cardiology'        => 'Cardiologie (coeur)',
        'dermatology'       => 'Dermatologie (peau)',
        'ent'               => 'Oto-rhino-laryngologie',
        'gastroenterology'  => 'Gastro-entérologie (estomac et intestins)',
        'general_surgery'   => 'Chirurgie générale',
        'gynaecology'       => 'Gynécologie',
        'internal_medicine' => 'Médecine interne',
        'neurology'         => 'Neurologie (nerfs et cerveau)',
        'oncology'          => 'Oncologie (cancers)',
        'ophthalmology'     => 'Ophtalmologie (yeux)',
        'orthopaedics'      => 'Orthopédie (os et articulations)',
        'paediatrics'       => 'Pédiatrie (enfants)',
        'psychiatry'        => 'Santé mentale',
        'pulmonology'       => 'Pneumologie (poumons)',
        'urology'           => 'Urologie',
        'dentistry'         => 'Soins dentaires',
        'nutrition'         => 'Nutrition',
        'physiotherapy'     => 'Kinésithérapie',
        'other'             => 'Autre chose',
    ],

    'durations' => [
        'less_than_week'       => 'Moins d\'une semaine',
        'one_to_four_weeks'    => 'D\'une à quatre semaines',
        'one_to_six_months'    => 'D\'un à six mois',
        'more_than_six_months' => 'Plus de six mois',
        'ongoing'              => 'Cela va et vient',
    ],

    'referral_request' => [

        /*
         * Affiché avant que le patient ne remplisse quoi que ce soit, et à
         * nouveau sur chaque demande de la liste.
         */
        'disclaimer' => 'Ceci demande à un clinicien d\'envisager de vous orienter. Ce n\'est pas une orientation et cela ne réserve rien. Un clinicien doit d\'abord l\'examiner.',

        'status' => [
            'submitted'    => 'En attente d\'examen',
            'under_review' => 'En cours d\'examen',
            'accepted'     => 'Orientation émise',
            'declined'     => 'Pas d\'orientation',
            'withdrawn'    => 'Retirée par vous',
        ],

        'status_detail' => [
            'submitted'    => 'Votre demande a bien été reçue. Aucun clinicien ne l\'a encore examinée.',
            'under_review' => 'Un clinicien a votre demande et l\'examine. Il n\'y a pas encore d\'orientation.',
            'accepted'     => 'Un clinicien a examiné votre demande et a émis une orientation. Vous la retrouvez avec vos orientations.',
            'declined'     => 'Un clinicien a examiné votre demande et estime qu\'une orientation n\'est pas la bonne étape pour le moment.',
            'withdrawn'    => 'Vous avez retiré cette demande. Elle n\'aura pas de suite.',
        ],

        'next_step' => [
            'submitted'    => 'Rien à faire pour l\'instant. Vous pouvez retirer la demande tant qu\'elle est en attente.',
            'under_review' => 'Rien à faire pour l\'instant. Vous verrez le résultat ici.',
            'accepted'     => 'Ouvrez vos orientations pour voir où vous êtes orienté et ce qu\'il faut apporter.',
            'declined'     => 'Si vos symptômes changent ou s\'aggravent, parlez-en à un soignant ou envoyez une nouvelle demande.',
            'withdrawn'    => 'Vous pouvez envoyer une nouvelle demande dès que vous en avez besoin.',
        ],

        /*
         * Le ressenti du PATIENT, jamais un triage clinique. Les valeurs ne sont
         * volontairement pas routine/urgent/urgence, qui sont ce qu'un clinicien
         * inscrit sur une orientation émise.
         */
        'reported_urgency' => [
            'routine' => [
                'label'       => 'Cela peut attendre',
                'description' => 'Je gère, mais j\'aimerais être vu.',
            ],
            'soon' => [
                'label'       => 'J\'aimerais être vu bientôt',
                'description' => 'Cela affecte ma vie quotidienne.',
            ],
            'urgent' => [
                'label'       => 'Cela me semble urgent',
                'description' => 'Cela s\'aggrave ou cela m\'inquiète.',
            ],
        ],

        'urgency_caveat' => 'C\'est l\'urgence telle que vous la ressentez. Un clinicien décide de la priorité clinique.',
    ],

];
