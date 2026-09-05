<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Documents exportés — la feuille OpesCare Secure et le PDF du dossier médical
    |--------------------------------------------------------------------------
    */

    // Habillage de la feuille (tout document sécurisé)
    'brand'              => 'OpesCare',
    'secure_kicker'      => 'Document sécurisé OpesCare',
    'reference'          => 'Référence',
    'issued'             => 'Émis le',
    'holder'             => 'Patient',
    'health_id'          => 'Identifiant Santé',
    'page'               => 'Page',
    'microtext'          => 'OpesCare · Sécurisé · Vérifié · Authentique · Interopérable · OpesCare · Sécurisé · Vérifié · Authentique · Interopérable',
    'footer_confidential' => 'Confidentiel. Ce document appartient au patient nommé et a été produit à partir de son dossier OpesCare, à sa demande.',
    'footer_verify'      => 'Un établissement peut confirmer tout document OpesCare en demandant au patient de présenter son Identifiant Santé.',
    'not_recorded'       => 'Non renseigné',

    // Export du dossier médical
    'record_title'       => 'Dossier médical',
    'record_subtitle'    => 'Export complet du dossier',
    'section_identity'   => 'Patient',
    'full_name'          => 'Nom complet',
    'date_of_birth'      => 'Date de naissance',
    'sex'                => 'Sexe',
    'blood_group'        => 'Groupe sanguin',
    'phone'              => 'Téléphone',
    'generated'          => 'Généré le',
    'sex_male'           => 'Masculin',
    'sex_female'         => 'Féminin',
    'sex_other'          => 'Autre',

    'section_allergies'  => 'Allergies',
    'allergen'           => 'Allergène',
    'severity'           => 'Sévérité',
    'allergies_none'     => 'Aucune allergie enregistrée.',

    'section_diagnoses'  => 'Diagnostics actifs',
    'diagnosis'          => 'Diagnostic',
    'code'               => 'Code',
    'status'             => 'Statut',
    'recorded'           => 'Enregistré le',

    'section_medications' => 'Traitements en cours',
    'medicine'           => 'Médicament',
    'dose'               => 'Dose',
    'frequency'          => 'Fréquence',
    'duration'           => 'Durée',
    'days'               => ':count jour|:count jours',
    'prescribed'         => 'Prescrit le',
    'prescribed_at_facility' => 'Prescrit le :date à :facility',
    'prescription_notes' => 'Notes',

    'section_vitals'     => 'Signes vitaux récents',
    'vitals_hint'        => ':count dernières mesures',
    'date'               => 'Date',
    'blood_pressure'     => 'Tension artérielle',
    'pulse'              => 'Pouls',
    'temperature'        => 'Temp. (°C)',
    'spo2'               => 'SpO₂ (%)',
    'weight'             => 'Poids (kg)',

    'section_labs'       => 'Résultats de laboratoire récents',
    'labs_hint'          => ':count derniers résultats',
    'test'               => 'Analyse',
    'result'             => 'Résultat',
    'unit'               => 'Unité',
    'reference_range'    => 'Valeurs de référence',
    'flag'               => 'Indicateur',

    'section_immunizations' => 'Historique vaccinal',
    'vaccine'            => 'Vaccin',
    'date_given'         => 'Date d’administration',
    'dose_number'        => 'Dose',
    'lot_number'         => 'Numéro de lot',

    'scan_to_verify'     => 'Scanner pour vérifier',
];
