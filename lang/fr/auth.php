<?php

return [
    'login' => [
        'title' => 'Connexion',
        'subtitle' => 'Accédez à votre portail OpesCare',
        'email' => 'Adresse e-mail',
        'password' => 'Mot de passe',
        'remember' => 'Se souvenir de moi',
        'forgot' => 'Mot de passe oublié ?',
        'submit' => 'Se connecter',
        'no_account' => "Vous n'avez pas de compte ?",
        'register' => 'Commencer',
    ],
    'register' => [
        'title' => 'Commencer avec OpesCare',
        'subtitle' => 'Choisissez votre type de compte pour commencer',
        'patient_title' => 'Pour les Patients',
        'patient_desc' => 'Obtenez votre ID Santé numérique et gérez votre historique médical.',
        'hospital_title' => 'Pour les Institutions',
        'hospital_desc' => 'Connectez votre hôpital, clinique, labo ou pharmacie au réseau OpesCare.',
        'submit_patient' => 'S\'enregistrer comme Patient',
        'submit_hospital' => 'Enregistrer l\'Institution',
        'already_have' => 'Vous avez déjà un compte ?',
        'login' => 'Se connecter',
        
        'patient' => [
            'title' => 'Enregistrement du Patient',
            'first_name' => 'Prénom',
            'last_name' => 'Nom',
            'dob' => 'Date de naissance',
            'gender' => 'Sexe',
            'phone' => 'Numéro de téléphone',
            'email' => 'Adresse e-mail',
            'password' => 'Créer un mot de passe',
            'confirm_password' => 'Confirmer le mot de passe',
            'submit' => 'Créer mon ID Santé',
        ],
        
        'hospital' => [
            'title' => 'Enregistrement Institutionnel',
            'name' => 'Nom de l\'établissement',
            'type' => 'Type d\'établissement',
            'license' => 'Numéro de licence',
            'admin_name' => 'Nom complet de l\'administrateur',
            'admin_email' => 'E-mail de l\'administrateur',
            'admin_phone' => 'Téléphone de contact',
            'submit' => 'Soumettre la demande',
        ],
    ],
    /* ── Portail gelé hors de la version actuelle ───── */
    'portal_unavailable' => [
        'page_title'    => 'Portail indisponible | OpesCare',
        'title'         => 'Ce portail ne fait pas partie de la version actuelle.',
        // \x27 is not an escape sequence inside a single-quoted PHP string, so
        // these three sentences rendered a literal "n\x27est pas activé" to
        // every French visitor. Typographic apostrophes need no escaping.
        'body'          => 'Votre compte est actif et votre connexion a réussi. Le portail correspondant à votre rôle n’est pas activé dans cette version d’OpesCare : il n’y a donc rien à ouvrir pour le moment.',
        'next'          => 'Votre compte n’a aucun problème et aucune donnée n’a été perdue. Votre accès reprendra dès l’activation du module.',
        'cta_contact'   => 'Contacter le support',
        'cta_signout'   => 'Se déconnecter',
        'signed_in_as'  => 'Connecté en tant que :email',
    ],
    /* ── /verify/otp — un écran de code sans canal derrière ───── */
    'otp_unavailable' => [
        'page_title'    => 'Vérification indisponible | OpesCare',
        'title'         => 'Cette étape de vérification n’est pas disponible.',
        'body'          => 'Aucun code à usage unique ne vous a été envoyé et aucun ne peut être vérifié ici. Cet écran n’est relié à aucun canal de vérification dans cette version d’OpesCare : rien de ce qui y est saisi ne serait vérifié.',
        'next'          => 'Si vous avez été redirigé ici, reconnectez-vous depuis la page de connexion. Les comptes protégés par un second facteur y sont contrôlés, sur l’écran de connexion lui-même.',
        'cta_signin'    => 'Retour à la connexion',
        'security_note' => 'OpesCare ne vous demandera jamais un code à usage unique sur une page qui ne vous en a pas envoyé. Signalez toute page qui le ferait.',
        'error'         => 'La vérification est indisponible. Aucun code n’a été vérifié et rien n’a été validé.',
    ],
];