<?php

// Pain sites (patient_pain_sites): App\Enums\PainRegion, PainSiteSource, PainTrend,
// the triage desk fields and the patient check-in endpoint.
return [
    'region' => [
        'head'          => 'Head',
        'forehead'      => 'Forehead',
        'face'          => 'Face',
        'neck'          => 'Neck',
        'chest'         => 'Chest',
        'upper_abdomen' => 'Abdomen, above the navel',
        'lower_abdomen' => 'Abdomen, below the navel',
        'pelvis'        => 'Pelvis',
        'right_arm'     => 'Right arm',
        'left_arm'      => 'Left arm',
        'right_leg'     => 'Right leg',
        'left_leg'      => 'Left leg',
        'upper_back'    => 'Back, upper part',
        'lower_back'    => 'Back, lumbar area',
    ],
    'source' => [
        'patient_checkin' => 'Patient at check-in',
        'triage'          => 'Nurse at triage',
        'clinician'       => 'Clinician',
    ],
    'trend' => [
        'better' => 'better',
        'same'   => 'same',
        'worse'  => 'worse',
    ],

    'figure_follows'        => 'Figure follows recorded sex and age band',
    'figure_sex_unrecorded' => 'Sex not recorded: the figure is a stand-in, not a statement about the patient',
    'figure_age_unrecorded' => 'Date of birth not recorded: adult figure shown',
    'back_not_drawn'        => 'back view not drawn yet',

    'fieldset'       => 'Pain sites',
    'fieldset_hint'  => 'Where it hurts. Leave a row empty to skip it. Left and right are the patient\'s.',
    'region_label'   => 'Region',
    'severity_label' => 'Severity (0–10)',
    'onset_label'    => 'Since',
    'trend_label'    => 'Trend',
    'note_label'     => 'Note',
    'add'            => 'Add pain site',
    'resolve'        => 'Mark resolved',
    'recorded'       => 'Pain site recorded.',
    'resolved'       => 'Pain site marked resolved.',
    'none_active'    => 'No active pain site at this facility.',
    'list_title'     => 'Pain sites at this facility',
    'not_found'      => 'Pain site not found.',
];
