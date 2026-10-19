<?php

// Sites douloureux (patient_pain_sites) : App\Enums\PainRegion, PainSiteSource, PainTrend,
// champs du poste de triage et point d'accès d'enregistrement du patient.
return [
    'region' => [
        'head'          => 'Tête',
        'forehead'      => 'Front',
        'face'          => 'Visage',
        'neck'          => 'Cou',
        'chest'         => 'Thorax',
        'upper_abdomen' => 'Abdomen supérieur',
        'lower_abdomen' => 'Abdomen inférieur',
        'pelvis'        => 'Bassin',
        'right_arm'     => 'Bras droit',
        'left_arm'      => 'Bras gauche',
        'right_leg'     => 'Jambe droite',
        'left_leg'      => 'Jambe gauche',
        'upper_back'    => 'Haut du dos',
        'lower_back'    => 'Bas du dos',
    ],
    'source' => [
        'patient_checkin' => 'Patient à l\'accueil',
        'triage'          => 'Infirmier au triage',
        'clinician'       => 'Clinicien',
    ],
    'trend' => [
        'better' => 'en amélioration',
        'same'   => 'stable',
        'worse'  => 'en aggravation',
    ],

    'figure_follows'        => 'La silhouette suit le sexe et la tranche d\'âge enregistrés',
    'figure_sex_unrecorded' => 'Sexe non renseigné : la silhouette est indicative et ne dit rien du patient',
    'figure_age_unrecorded' => 'Date de naissance non renseignée : silhouette adulte affichée',
    'back_not_drawn'        => 'vue de dos pas encore dessinée',

    'fieldset'       => 'Sites douloureux',
    'fieldset_hint'  => 'Où cela fait mal. Laissez une ligne vide pour l\'ignorer. Gauche et droite sont ceux du patient.',
    'region_label'   => 'Région',
    'severity_label' => 'Intensité (0–10)',
    'onset_label'    => 'Depuis',
    'trend_label'    => 'Évolution',
    'note_label'     => 'Remarque',
    'add'            => 'Ajouter un site douloureux',
    'resolve'        => 'Marquer comme résolu',
    'recorded'       => 'Site douloureux enregistré.',
    'resolved'       => 'Site douloureux marqué comme résolu.',
    'none_active'    => 'Aucun site douloureux actif dans cet établissement.',
    'list_title'     => 'Sites douloureux dans cet établissement',
    'not_found'      => 'Site douloureux introuvable.',
];
