<?php

/*
|--------------------------------------------------------------------------
| Health Passport
|--------------------------------------------------------------------------
|
| Les trente pages du Passeport de santé OpesCare, dans l’ordre canonique.
| L’Identifiant Santé dit qui vous êtes ; le Passeport organise le dossier.
|
| Page titles live here rather than in the builder so both languages are
| edited together and stay 1:1 (scripts/i18n-audit.php enforces it).
|
*/

return [

    'title'          => 'Passeport de santé',
    'open'           => 'Ouvrir mon Passeport de santé',
    'subtitle'       => 'Votre dossier, organisé en trente pages',
    'pages_label'    => 'Pages',
    'page_of'        => 'Page :number sur :total',
    'previous'       => 'Précédent',
    'next'           => 'Suivant',
    'loading'        => 'Chargement de votre Passeport de santé…',
    'empty'          => 'Rien n’a encore été enregistré sur cette page.',
    'empty_hint'     => 'Elle se remplit à mesure que vos soins sont enregistrés. Une page vide signifie qu’aucune donnée n’a été enregistrée, pas qu’il en manque.',
    'error'          => 'Cette page n’a pas pu être chargée.',
    'try_again'      => 'Réessayer',
    'current'        => 'À jour',
    'cached'         => 'Copie enregistrée',
    'demo'           => 'Compte de démonstration — ceci n’est pas un justificatif réel',
    'secure'         => 'OpesCare Secure',
    'tagline'        => 'PERSONNES • DONNÉES • CONFIANCE • MEILLEURE SANTÉ • AFRIQUE',
    'not_recorded'   => 'Non renseigné',
    'status'         => 'Statut',
    'health_id'      => 'Identifiant Santé',
    'version'        => 'Version',
    'last_updated'   => 'Dernière mise à jour',
    'none_recorded'  => 'Aucun enregistrement',
    'common_name'    => 'Nom',
    'dob'            => 'Date de naissance',
    'sex'            => 'Sexe',
    'sex_male'       => 'Masculin',
    'sex_female'     => 'Féminin',
    'blood_group'    => 'Groupe sanguin',
    'allergies_label' => 'Allergies',
    'emergency_contact' => 'Contact d’urgence',
    'recorded'       => 'Enregistré',
    'as_prescribed'  => 'Selon la prescription',
    'care_team_member' => 'Membre de l’équipe soignante',
    'a_facility'     => 'Un établissement',
    'until'          => 'Jusqu’au :date',
    'next_appointment' => 'Prochain rendez-vous',
    'checked_against' => 'Vérifié auprès de',
    'checked_against_value' => 'Le registre national, au moment de la vérification',

    // Chaque mot qu’un statut peut prendre, pour que FormatsRecords::statusLabel()
    // n’ait jamais à se rabattre sur le mot anglais remis en forme. Une clé
    // manquante ici ne donne pas une page vide : elle donne un mot anglais sur
    // le passeport d’un lecteur francophone.
    'status_active'      => 'Actif',
    'status_provisional' => 'Provisoire',
    'status_suspended'   => 'Suspendu',
    'status_revoked'     => 'Révoqué',
    'status_expired'     => 'Expiré',
    'status_ordered'     => 'Prescrit',
    'status_collected'   => 'Prélèvement effectué',
    'status_in_progress' => 'En cours',
    'status_resulted'    => 'Résultats disponibles',
    'status_dispensed' => 'Délivré',
    'status_partially_dispensed' => 'Partiellement délivré',
    'status_severe' => 'Grave',
    'status_moderate' => 'Modérée',
    'status_mild' => 'Légère',
    'status_completed' => 'Terminé',
    'status_resolved' => 'Résolu',
    'status_pending' => 'En attente',
    'status_scheduled' => 'Programmé',
    'status_booked' => 'Réservé',
    'status_confirmed' => 'Confirmé',
    'status_verified' => 'Vérifié',
    'status_inactive' => 'Inactif',
    'status_chronic' => 'Chronique',
    'status_entered_in_error' => 'Saisie erronée',
    'status_discharged' => 'Sorti',
    'status_transferred' => 'Transféré',
    'status_voided' => 'Annulée',
    'status_amended' => 'Modifiée',
    'status_final' => 'Définitif',
    'status_preliminary' => 'Préliminaire',
    'status_corrected' => 'Corrigé',
    'status_draft' => 'Brouillon',
    'status_home' => 'Domicile',
    'status_referral' => 'Transféré',
    'status_ama' => 'Contre avis médical',
    'status_deceased' => 'Décédé',
    'status_cancelled'   => 'Annulé',

    // Cycle de vie d’une orientation (referral_cases.status) et tri clinique
    // (referral_cases.urgency). « emergency » est aussi un type de consultation :
    // le mot doit se lire aussi bien comme niveau de tri que comme type de visite.
    'status_sent' => 'Envoyé',
    'status_accepted' => 'Accepté',
    'status_rejected' => 'Rejeté',
    'status_routine' => 'Non urgent',
    'status_urgent' => 'Urgent',
    'status_emergency' => 'Urgence',

    // Demandes de consentement, demandes de correction et liens familiaux —
    // les décisions que le patient prend sur son propre dossier.
    'status_approved' => 'Approuvé',
    'status_denied' => 'Refusé',
    'status_declined' => 'Décliné',
    'status_withdrawn' => 'Retiré',
    'status_pending_patient_approval' => 'En attente de votre accord',
    'status_pending_invite' => 'Invitation en attente',
    'status_treatment' => 'Soins',

    // Un enregistrement qu’un autre a remplacé, et une couverture arrivée à
    // terme. « status_suspended_policy » existe parce que « status_suspended »
    // est déjà le mot du justificatif ; on l’appelle par son nom, il n’est pas
    // déduit d’une valeur enregistrée.
    'status_superseded' => 'Remplacé',
    'status_lapsed' => 'Échu',
    'status_suspended_policy' => 'Suspendue',

    // Rôles au sein de l’équipe soignante (care_team_members.role).
    'status_attending' => 'Médecin référent',
    'status_consulting' => 'Médecin consultant',
    'status_nursing' => 'Soins infirmiers',
    'status_pharmacy' => 'Pharmacie',
    'status_social_work' => 'Service social',

    // Vaccination et dépistage.
    'status_administered' => 'Administré',
    'status_given' => 'Administré',
    'status_not_given' => 'Non administré',
    'status_refused' => 'Refusé',
    'status_due' => 'À faire',

    // Types de couverture, tels que insurance_plans.plan_type les enregistre.
    // Sans ces clés, le formateur transformait « nhia » en « Nhia », un mot
    // qui ne veut rien dire.
    'plan_type_nhia'     => 'Assurance maladie nationale',
    'plan_type_mutual'   => 'Mutuelle de santé',
    'plan_type_employer' => 'Régime employeur',
    'plan_type_private'  => 'Assurance privée',

    'pages' => [
        'cover' => [
            'title'    => 'Passeport de santé',
            'subtitle' => 'Votre dossier de santé longitudinal sécurisé',
        ],
        'patient-identity' => [
            'title'    => 'Identité du patient',
            'subtitle' => 'À qui appartient ce dossier',
        ],
        'emergency-profile' => [
            'title'    => 'Profil d’urgence',
            'subtitle' => 'Ce dont un soignant a besoin en urgence',
        ],
        'medical-summary' => [
            'title'    => 'Résumé médical',
            'subtitle' => 'Une vue d’ensemble de votre dossier',
        ],
        'allergies-alerts' => [
            'title'    => 'Allergies et alertes',
            'subtitle' => 'Informations de sécurité à vérifier en premier',
        ],
        'current-medications' => [
            'title'    => 'Traitements en cours',
            'subtitle' => 'Les médicaments que vous prenez actuellement',
        ],
        'conditions' => [
            'title'    => 'Pathologies',
            'subtitle' => 'Votre liste de problèmes dans le temps',
        ],
        'encounters' => [
            'title'    => 'Consultations',
            'subtitle' => 'Visites et consultations',
        ],
        'hospital-admissions' => [
            'title'    => 'Hospitalisations',
            'subtitle' => 'Séjours hospitaliers',
        ],
        'procedures' => [
            'title'    => 'Actes et interventions',
            'subtitle' => 'Opérations et actes cliniques',
        ],
        'laboratory' => [
            'title'    => 'Résultats de laboratoire',
            'subtitle' => 'Analyses et leurs résultats',
        ],
        'radiology' => [
            'title'    => 'Radiologie et imagerie',
            'subtitle' => 'Examens d’imagerie et comptes rendus',
        ],
        'cardiology' => [
            'title'    => 'Cardiologie',
            'subtitle' => 'Examens et résultats cardiaques',
        ],
        'immunization' => [
            'title'    => 'Carnet de vaccination',
            'subtitle' => 'Les vaccins que vous avez reçus',
        ],
        'prescriptions' => [
            'title'    => 'Ordonnances',
            'subtitle' => 'Les médicaments qui vous ont été prescrits',
        ],
        'dispensing' => [
            'title'    => 'Délivrances',
            'subtitle' => 'Ce qu’une pharmacie a délivré',
        ],
        'referrals' => [
            'title'    => 'Orientations',
            'subtitle' => 'Prise en charge et orientations vers un spécialiste',
        ],
        'medical-certificates' => [
            'title'    => 'Certificats médicaux',
            'subtitle' => 'Les certificats qui vous ont été délivrés',
        ],
        'discharge-summary' => [
            'title'    => 'Comptes rendus de sortie',
            'subtitle' => 'Comment vos séjours se sont conclus',
        ],
        'maternal-child' => [
            'title'    => 'Santé maternelle et infantile',
            'subtitle' => 'Dossiers de grossesse et de santé de l’enfant',
        ],
        'care-providers' => [
            'title'    => 'Équipe soignante',
            'subtitle' => 'L’équipe qui participe à vos soins',
        ],
        'insurance' => [
            'title'    => 'Assurance et couverture',
            'subtitle' => 'Comment vos soins sont couverts',
        ],
        'consent-sharing' => [
            'title'    => 'Consentement et partage',
            'subtitle' => 'Qui vous avez autorisé à consulter votre dossier',
        ],
        'verification' => [
            'title'    => 'Centre de vérification',
            'subtitle' => 'Prouvez que ce dossier est le vôtre et à jour',
        ],
        'vitals' => [
            'title'    => 'Signes vitaux',
            'subtitle' => 'Mesures dans le temps',
        ],
        'family-genetic' => [
            'title'    => 'Antécédents familiaux',
            'subtitle' => 'Pathologies présentes dans votre famille',
        ],
        'preventive-screening' => [
            'title'    => 'Prévention et dépistage',
            'subtitle' => 'Les examens qui vous gardent en bonne santé',
        ],
        'devices-transfusion' => [
            'title'    => 'Dispositifs, implants et transfusions',
            'subtitle' => 'Dispositifs implantés et antécédents transfusionnels',
        ],
        'care-plans' => [
            'title'    => 'Plans de soins et suivi',
            'subtitle' => 'La suite de votre prise en charge',
        ],
        'back-cover' => [
            'title'    => 'Ma santé. Mes données.',
            'subtitle' => 'Une Afrique en meilleure santé',
        ],
    ],

    'sections' => [
        'active_conditions' => 'Affections en cours',
        'past_conditions' => 'Antécédents',
        'surgical_history' => 'Antécédents chirurgicaux',
        'allergies_intolerances' => 'Allergies et intolérances',
        'immunisation_status' => 'Statut vaccinal',
        'recent_encounters' => 'Consultations récentes',
        'care_recommendations' => 'Plan de soins et recommandations',
        'overall_summary' => 'Résumé d’ensemble',
        'primary_insurance' => 'Assurance principale',
        'additional_cover' => 'Couverture complémentaire',
        'consent_preferences' => 'Vos préférences de consentement',
        'manage_consent' => 'Gérer le consentement',
        'document_details' => 'Détails du document',
        'security_features' => 'Éléments de sécurité',
        'obstetric_history' => 'Antécédents obstétricaux',
        'child_health' => 'Carnet de santé de l’enfant',
        'emergency_contacts' => 'Contacts d’urgence',
        'critical_alerts' => 'Alertes critiques',
        'cover_benefits' => 'Prestations couvertes',
        'who_has_access' => 'Qui a accès',
        'imaging_reports' => 'Comptes rendus d’imagerie',
        'recent_results' => 'Résultats récents',
        'identity' => 'Identité',
        'credential' => 'Justificatif',
        'summary' => 'En un coup d’œil',
        'allergies' => 'Allergies',
        'medications' => 'Médicaments',
        'conditions' => 'Pathologies',
        'encounters' => 'Consultations récentes',
        'admissions' => 'Hospitalisations',
        'lab_orders' => 'Demandes d’analyses',
        'laboratory' => 'Résultats récents',
        'imaging' => 'Imagerie',
        'immunisations' => 'Vaccins administrés',
        'awaiting_collection' => 'En attente de retrait',
        'prescriptions' => 'Ordonnances',
        'dispensing' => 'Délivrances',
        'referrals' => 'Orientations',
        'documents' => 'Documents',
        'discharge' => 'Sorties',
        'providers' => 'Votre équipe soignante',
        'insurance' => 'Couverture',
        'consent' => 'Autorisations actives',
        'verification_history' => 'Historique des vérifications',
        'verification' => 'Comment vérifier',
        'vitals' => 'Dernières mesures',
        'care_plans' => 'Plans',
        'emergency' => 'En cas d’urgence',
        // Titres que des pages devaient emprunter ailleurs, faute des leurs.
        'sharing_history' => 'Historique des partages',
        'primary_care_provider' => 'Professionnel de santé référent',
        'facilities' => 'Établissements',
        'outcome' => 'Issue',
        'corrections' => 'Corrections',
    ],

    // The column headers, block captions and section names the approved
    // page design uses. One vocabulary, so every page reads the same.
    'columns' => [
        'condition' => 'Affection',
        'since' => 'Depuis',
        'status' => 'Statut',
        'notes' => 'Notes',
        'year' => 'Année',
        'procedure' => 'Intervention',
        'date' => 'Date',
        'medication' => 'Médicament',
        'dose' => 'Dose',
        'frequency' => 'Fréquence',
        'indication' => 'Indication',
        'allergen' => 'Allergène',
        'reaction' => 'Réaction',
        'severity' => 'Gravité',
        'vaccine' => 'Vaccin',
        'last_dose' => 'Dernière dose',
        'next_due' => 'Prochaine dose',
        'type' => 'Type',
        'facility' => 'Établissement',
        'route' => 'Voie',
        'reason' => 'Motif',
        'test' => 'Analyse',
        'result' => 'Résultat',
        'range' => 'Valeurs de référence',
        'flag' => 'Signalement',
        'ordered' => 'Prescrite le',
        'reported' => 'Rendue le',
        'study' => 'Examen',
        'modality' => 'Modalité',
        'findings' => 'Conclusion',
        'reference' => 'Référence',
        'prescribed' => 'Prescrit le',
        'prescriber' => 'Prescripteur',
        'quantity' => 'Quantité',
        'dispensed' => 'Délivré le',
        'pharmacy' => 'Pharmacie',
        'specialty' => 'Spécialité',
        'referred_to' => 'Adressé à',
        'document' => 'Document',
        'issued' => 'Délivré le',
        'valid_until' => 'Valable jusqu’au',
        'number' => 'Numéro',
        'admitted' => 'Admis le',
        'discharged' => 'Sorti le',
        'ward' => 'Service',
        'outcome' => 'Issue',
        'provider' => 'Professionnel',
        'role' => 'Rôle',
        'contact' => 'Contact',
        'scheme' => 'Régime',
        'policy_number' => 'Numéro de police',
        'member_id' => 'N° d’adhérent',
        'cover' => 'Couverture',
        'expires' => 'Expire le',
        'who' => 'Qui',
        'purpose' => 'Finalité',
        'granted' => 'Accordé le',
        'measure' => 'Mesure',
        'value' => 'Valeur',
        'unit' => 'Unité',
        'measured' => 'Mesuré le',
        'plan' => 'Plan',
        'started' => 'Débuté le',
        'review' => 'Revue',
        'detail' => 'Détail',
        // Titres qui manquaient au vocabulaire. « urgency » est le tri clinique
        // et n’est PAS « flag » ; « impression » est la conclusion du radiologue
        // et n’est PAS « findings » ; « lot », « manufacturer » et « site »
        // nomment ce que contient un carnet de vaccination et ne pouvaient pas
        // être imprimés faute d’en-tête.
        'urgency' => 'Degré d’urgence',
        'impression' => 'Interprétation',
        'recommendation' => 'Recommandation',
        'insurer' => 'Assureur',
        'qualification' => 'Qualification',
        'address' => 'Adresse',
        'lot' => 'Numéro de lot',
        'manufacturer' => 'Fabricant',
        'site' => 'Site d’injection',
    ],

    'blocks' => [
        'scan_to_verify' => 'SCANNER POUR VÉRIFIER',
        'scan_full_record' => 'SCANNER POUR VOIR LE DOSSIER',
        'document_id' => 'Numéro de document',
        'issued_on' => 'Délivré le',
        'valid_until_label' => 'Valable jusqu’au',
        'authorised_by' => 'Autorisé par',
        'authority' => 'OpesCare Health Systems',
        'updated_by' => 'Mis à jour par',
        'in_case_of_emergency' => 'EN CAS D’URGENCE',
        'emergency_note' => 'Cette page contient les informations nécessaires à une prise en charge immédiate.',
        'healthier_people' => 'Des personnes en meilleure santé',
        'stronger_africa' => 'Une Afrique plus forte',
        'safer_healthier' => 'UNE AFRIQUE PLUS SÛRE ET EN MEILLEURE SANTÉ',
        'people_united' => 'UNIS POUR UNE MEILLEURE SANTÉ',
        'no_rows' => 'Rien n’est encore enregistré ici.',
    ],

    'stats' => [
        'dispensed' => 'Délivrés',
        'conditions' => 'Affections',
        'medicines' => 'Médicaments',
        'encounters' => 'Consultations',
        'allergies' => 'Allergies',
        'results' => 'Résultats',
        'vaccines' => 'Vaccins',
        'admissions' => 'Hospitalisations',
        'documents' => 'Documents',
        'studies' => 'Examens',
        'readings' => 'Relevés',
    ],

];
