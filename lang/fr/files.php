<?php

// Pièces jointes du dossier patient + transmission du compte rendu de radiologie (docs/PLATFORM_IMPORTS.md, groupe A).
return [
    'title'               => 'Fichiers du patient',
    'subtitle'            => 'Imageries, comptes rendus et documents signés joints au dossier par cet établissement.',
    'chart_link'          => 'Fichiers du patient',
    'back_to_chart'       => 'Retour au dossier',
    'back'                => 'Retour aux fichiers',
    'upload_btn'          => 'Téléverser un fichier',
    'upload_title'        => 'Téléverser un fichier patient',
    'upload_subtitle'     => 'Le fichier reste privé à cet établissement jusqu’à ce que vous le transmettiez au patient.',
    'attachments'         => 'Fichiers joints ici',
    'none'                => 'Cet établissement n’a joint aucun fichier à ce patient.',
    'no_facility_context' => 'Aucun établissement n’est sélectionné pour cette session.',
    'audit_unavailable'   => 'La trace d’accès n’a pas pu être enregistrée : le fichier n’est pas affiché.',

    'col_file'        => 'Fichier',
    'col_category'    => 'Type',
    'col_description' => 'Description',
    'col_size'        => 'Taille',
    'col_date'        => 'Téléversé le',
    'col_status'      => 'Statut',
    'col_actions'     => 'Actions',
    'col_released'    => 'Transmis le',

    'status_voided'       => 'Annulé',
    'status_released'     => 'Transmis au patient',
    'status_not_released' => 'Non transmis',

    'download'        => 'Télécharger',
    'release'         => 'Transmettre au patient',
    'release_confirm' => 'Transmettre maintenant',
    'release_hint'    => 'Le patient verra ce fichier dans ses documents.',
    'void'            => 'Annuler',
    'void_confirm'    => 'Annuler ce fichier',
    'void_hint'       => 'Un fichier annulé est masqué au patient. Il n’est jamais supprimé.',

    'lbl_file'        => 'Fichier',
    'lbl_category'    => 'Type de document',
    'lbl_description' => 'Description',
    'lbl_void_reason' => 'Motif de l’annulation',
    'lbl_modality'    => 'Modalité',
    'lbl_body_part'   => 'Région anatomique',
    'lbl_study_date'  => 'Date de l’examen',
    'select_category' => 'Choisir un type',
    'ph_description'  => 'Par exemple : compte rendu de radiographie thoracique',
    'click_browse'    => 'Cliquez pour choisir un fichier, ou déposez-le ici',
    'size_hint'       => 'PDF, JPEG ou PNG, jusqu’à :max Mo',
    'upload_attach'   => 'Téléverser et joindre',
    'cancel'          => 'Annuler',

    'categories' => [
        'lab_result'       => 'Résultat de laboratoire',
        'imaging'          => 'Imagerie',
        'radiology_report' => 'Compte rendu de radiologie',
        'consent'          => 'Formulaire de consentement',
        'prescription'     => 'Ordonnance',
        'referral'         => 'Lettre de référence',
        'report'           => 'Compte rendu clinique',
        'id_document'      => 'Pièce d’identité',
        'other'            => 'Autre',
    ],

    'modalities' => [
        'xray'       => 'Radiographie',
        'ct'         => 'Scanner (TDM)',
        'mri'        => 'IRM',
        'ultrasound' => 'Échographie',
        'echo'       => 'Échocardiographie',
        'nuclear'    => 'Médecine nucléaire',
        'pet'        => 'TEP',
        'other'      => 'Autre',
    ],

    'err' => [
        'type'      => 'Seuls les fichiers PDF, JPEG ou PNG peuvent être joints.',
        'too_large' => 'Le fichier dépasse :max Mo.',
        'voided'    => 'Ce fichier a été annulé.',
    ],

    'flash' => [
        'uploaded'         => 'Le fichier a été joint au dossier.',
        'voided'           => 'Le fichier a été annulé. Il est conservé mais masqué au patient.',
        'released'         => 'Le fichier a été transmis au patient.',
        'already_released' => 'Ce fichier a déjà été transmis au patient.',
    ],

    'patient_title' => 'Fichiers de vos établissements',
    'patient_hint'  => 'Comptes rendus et imageries qu’un établissement a partagés avec vous.',

    'notify' => [
        'title'  => 'Votre compte rendu de radiologie est prêt',
        'body'   => ':facility a partagé un compte rendu de radiologie avec vous.',
        'action' => 'Ouvrir les documents',
    ],
];
