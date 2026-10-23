<?php

// API partenaires OpesCare Connect (résolution / vérification / consentement / dossier) : messages.
return [
    'missing_lookup_criteria' => 'Fournissez un Health ID, ou un nom complet et une date de naissance.',
    'consent_required_lookup' => 'Une recherche démographique nécessite l\'accord du patient : envoyez consent_acknowledged=true une fois que le patient a accepté.',
    'health_id_not_found'     => 'Ce Health ID n\'est pas enregistré sur OpesCare.',
    'patient_not_found'       => 'Aucun patient OpesCare ne correspond à ces informations. Le patient peut obtenir un Health ID dans l\'application OpesCare.',
    'ambiguous_match'         => 'Plusieurs patients correspondent à ces informations. Demandez au patient son Health ID ou son numéro de téléphone.',
    'health_id_invalid'       => 'Ce Health ID OpesCare n\'est pas valide (format ou bloc de contrôle).',
    'facility_required'       => 'Cette application n\'est rattachée à aucun établissement. Demandez d\'abord l\'accès production pour un établissement.',
    'consent_required'        => 'Aucun consentement actif de ce patient envers votre établissement ne couvre cette demande.',
    'consent_facility'        => 'Ce consentement appartient à un autre établissement.',
    'consent_scope'           => 'Ce consentement ne couvre pas le périmètre patients:read.',
    'health_id_suspended'     => 'Ce Health ID n\'est pas actif.',
    'invalid_payload'         => 'La requête est invalide.',
    'consent_requested'       => 'Consentement demandé. Le patient l\'accepte ou le refuse dans l\'application OpesCare.',
    'consent_already_open'    => 'Une demande de consentement de votre établissement attend déjà la réponse de ce patient.',
    'consent_already_granted' => 'Votre établissement détient déjà un consentement actif couvrant ce périmètre.',
];
