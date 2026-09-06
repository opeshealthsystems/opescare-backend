<?php

/*
|--------------------------------------------------------------------------
| Health Passport
|--------------------------------------------------------------------------
|
| Les trente pages du Passeport de sante OpesCare, dans l’ordre canonique.
| L’Identifiant Sante dit qui vous etes ; le Passeport organise le dossier.
|
| Page titles live here rather than in the builder so both languages are
| edited together and stay 1:1 (scripts/i18n-audit.php enforces it).
|
*/

return [

    'title'          => 'Passeport de sante',
    'open'           => 'Ouvrir mon Passeport de sante',
    'subtitle'       => 'Votre dossier, organise en trente pages',
    'pages_label'    => 'Pages',
    'page_of'        => 'Page :number sur :total',
    'previous'       => 'Precedent',
    'next'           => 'Suivant',
    'loading'        => 'Chargement de votre Passeport de sante…',
    'empty'          => 'Rien n\'a encore ete enregistre sur cette page.',
    'empty_hint'     => 'Elle se remplit a mesure que vos soins sont enregistres. Une page vide signifie qu\'aucune donnee n\'a ete enregistree, pas qu\'il en manque.',
    'error'          => 'Cette page n\'a pas pu etre chargee.',
    'try_again'      => 'Reessayer',
    'current'        => 'A jour',
    'cached'         => 'Copie enregistree',
    'demo'           => 'Compte de demonstration — ceci n\'est pas un justificatif reel',
    'secure'         => 'OpesCare Secure',
    'tagline'        => 'PERSONNES • DONNEES • CONFIANCE • MEILLEURE SANTE • AFRIQUE',
    'not_recorded'   => 'Non renseigne',
    'status'         => 'Statut',
    'health_id'      => 'Identifiant Sante',
    'version'        => 'Version',
    'last_updated'   => 'Derniere mise a jour',
    'none_recorded'  => 'Aucun enregistrement',
    'common_name'    => 'Nom',
    'dob'            => 'Date de naissance',
    'sex'            => 'Sexe',
    'sex_male'       => 'Masculin',
    'sex_female'     => 'Feminin',
    'blood_group'    => 'Groupe sanguin',
    'allergies_label' => 'Allergies',
    'emergency_contact' => 'Contact d\'urgence',
    'recorded'       => 'Enregistre',
    'as_prescribed'  => 'Selon la prescription',
    'care_team_member' => 'Membre de l\'equipe soignante',
    'a_facility'     => 'Un etablissement',
    'until'          => 'Jusqu\'au :date',
    'next_appointment' => 'Prochain rendez-vous',
    'checked_against' => 'Verifie aupres de',
    'checked_against_value' => 'Le registre national, au moment de la verification',
    'status_active'      => 'Actif',
    'status_provisional' => 'Provisoire',
    'status_suspended'   => 'Suspendu',
    'status_revoked'     => 'Revoque',
    'status_expired'     => 'Expire',
    'status_ordered'     => 'Prescrit',
    'status_collected'   => 'Prelevement effectue',
    'status_in_progress' => 'En cours',
    'status_resulted'    => 'Resultats disponibles',
    'status_cancelled'   => 'Annule',

    'pages' => [
        'cover' => [
            'title'    => 'Passeport de sante',
            'subtitle' => 'Votre dossier de sante longitudinal securise',
        ],
        'patient-identity' => [
            'title'    => 'Identite du patient',
            'subtitle' => 'A qui appartient ce dossier',
        ],
        'emergency-profile' => [
            'title'    => 'Profil d’urgence',
            'subtitle' => 'Ce dont un soignant a besoin en urgence',
        ],
        'medical-summary' => [
            'title'    => 'Resume medical',
            'subtitle' => 'Une vue d’ensemble de votre dossier',
        ],
        'allergies-alerts' => [
            'title'    => 'Allergies et alertes',
            'subtitle' => 'Informations de securite a verifier en premier',
        ],
        'current-medications' => [
            'title'    => 'Traitements en cours',
            'subtitle' => 'Les medicaments que vous prenez actuellement',
        ],
        'conditions' => [
            'title'    => 'Pathologies',
            'subtitle' => 'Votre liste de problemes dans le temps',
        ],
        'encounters' => [
            'title'    => 'Consultations',
            'subtitle' => 'Visites et consultations',
        ],
        'hospital-admissions' => [
            'title'    => 'Hospitalisations',
            'subtitle' => 'Sejours hospitaliers',
        ],
        'procedures' => [
            'title'    => 'Actes et interventions',
            'subtitle' => 'Operations et actes cliniques',
        ],
        'laboratory' => [
            'title'    => 'Resultats de laboratoire',
            'subtitle' => 'Analyses et leurs resultats',
        ],
        'radiology' => [
            'title'    => 'Radiologie et imagerie',
            'subtitle' => 'Examens d’imagerie et comptes rendus',
        ],
        'cardiology' => [
            'title'    => 'Cardiologie',
            'subtitle' => 'Examens et resultats cardiaques',
        ],
        'immunization' => [
            'title'    => 'Carnet de vaccination',
            'subtitle' => 'Les vaccins que vous avez recus',
        ],
        'prescriptions' => [
            'title'    => 'Ordonnances',
            'subtitle' => 'Les medicaments qui vous ont ete prescrits',
        ],
        'dispensing' => [
            'title'    => 'Delivrances',
            'subtitle' => 'Ce qu’une pharmacie a delivre',
        ],
        'referrals' => [
            'title'    => 'Orientations',
            'subtitle' => 'Prise en charge et orientations vers un specialiste',
        ],
        'medical-certificates' => [
            'title'    => 'Certificats medicaux',
            'subtitle' => 'Les certificats qui vous ont ete delivres',
        ],
        'discharge-summary' => [
            'title'    => 'Comptes rendus de sortie',
            'subtitle' => 'Comment vos sejours se sont conclus',
        ],
        'maternal-child' => [
            'title'    => 'Sante maternelle et infantile',
            'subtitle' => 'Dossiers de grossesse et de sante de l’enfant',
        ],
        'care-providers' => [
            'title'    => 'Equipe soignante',
            'subtitle' => 'L’equipe qui participe a vos soins',
        ],
        'insurance' => [
            'title'    => 'Assurance et couverture',
            'subtitle' => 'Comment vos soins sont couverts',
        ],
        'consent-sharing' => [
            'title'    => 'Consentement et partage',
            'subtitle' => 'Qui vous avez autorise a consulter votre dossier',
        ],
        'verification' => [
            'title'    => 'Centre de verification',
            'subtitle' => 'Prouvez que ce dossier est le votre et a jour',
        ],
        'vitals' => [
            'title'    => 'Signes vitaux',
            'subtitle' => 'Mesures dans le temps',
        ],
        'family-genetic' => [
            'title'    => 'Antecedents familiaux',
            'subtitle' => 'Pathologies presentes dans votre famille',
        ],
        'preventive-screening' => [
            'title'    => 'Prevention et depistage',
            'subtitle' => 'Les examens qui vous gardent en bonne sante',
        ],
        'devices-transfusion' => [
            'title'    => 'Dispositifs, implants et transfusions',
            'subtitle' => 'Dispositifs implantes et antecedents transfusionnels',
        ],
        'care-plans' => [
            'title'    => 'Plans de soins et suivi',
            'subtitle' => 'La suite de votre prise en charge',
        ],
        'back-cover' => [
            'title'    => 'Ma sante. Mes donnees.',
            'subtitle' => 'Une Afrique en meilleure sante',
        ],
    ],

    'sections' => [
        'identity' => 'Identite',
        'credential' => 'Justificatif',
        'summary' => 'En un coup d’oeil',
        'allergies' => 'Allergies',
        'medications' => 'Medicaments',
        'conditions' => 'Pathologies',
        'encounters' => 'Consultations recentes',
        'admissions' => 'Hospitalisations',
        'laboratory' => 'Resultats recents',
        'imaging' => 'Imagerie',
        'immunisations' => 'Vaccins administres',
        'prescriptions' => 'Ordonnances',
        'dispensing' => 'Delivrances',
        'referrals' => 'Orientations',
        'documents' => 'Documents',
        'discharge' => 'Sorties',
        'providers' => 'Votre equipe soignante',
        'insurance' => 'Couverture',
        'consent' => 'Autorisations actives',
        'verification' => 'Comment verifier',
        'vitals' => 'Dernieres mesures',
        'care_plans' => 'Plans',
        'emergency' => 'En cas d’urgence',
    ],

];
