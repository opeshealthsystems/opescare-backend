<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Page d'accueil
    |--------------------------------------------------------------------------
    |
    | La page d'accueil répond à cinq questions, pas davantage : ce qu'est
    | OpesCare, pourquoi cela compte, comment cela fonctionne, ce que l'on peut
    | connecter, et quoi faire ensuite. Tout ce qui enseigne un flux de travail,
    | documente un module ou s'adresse à un seul acteur appartient à sa propre
    | page — voir Solutions, Réseau, Interopérabilité et Confiance.
    |
    */

    'hero' => [
        'badge'          => 'Identité de santé et interopérabilité',
        'title'          => 'Votre identifiant de santé. Votre dossier. Connectés à travers le système de soins.',
        'subtitle'       => 'Le patient porte une identité vérifiée et un dossier longitudinal unique. Les établissements échangent ce qu\'ils sont autorisés à échanger — depuis les systèmes qu\'ils utilisent déjà.',
        'positioning'    => 'OpesCare relie les systèmes de santé. Il n\'exige pas que chaque établissement utilise le même logiciel.',
        'cta_primary'    => 'Connecter un système',
        'cta_secondary'  => 'Obtenir un identifiant de santé',
        'fact_bilingual' => 'Anglais et français partout',
        'fact_standards' => 'HL7 FHIR R4',
        'fact_consent'   => 'Consentement à chaque échange',
    ],

    'problem' => [
        'title'    => 'Les soins de santé sont fragmentés.',
        'subtitle' => 'Un patient circule entre hôpitaux, cliniques, laboratoires et pharmacies. Ses informations médicales, elles, ne le suivent pas.',

        'identity_title'   => 'Identité patient fragmentée',
        'identity_desc'    => 'La même personne est un patient différent dans chaque établissement. Rien ne relie ces dossiers à un seul être humain.',
        'records_title'    => 'Dossiers de santé déconnectés',
        'records_desc'     => 'Chaque système détient un fragment. Personne ne détient l\'historique : les soins se décident sur une vue partielle.',
        'delay_title'      => 'Accès tardif aux informations critiques',
        'delay_desc'       => 'Allergies, traitements en cours et résultats récents arrivent après la décision qui en dépendait — ou n\'arrivent jamais.',
        'visibility_title' => 'Peu de visibilité, peu de continuité',
        'visibility_desc'  => 'Le patient ne sait pas qui a ouvert son dossier ni pourquoi, et le clinicien suivant repart de zéro.',
    ],

    'answer' => [
        'title'    => 'Une identité, un dossier, échangés sous consentement.',
        'subtitle' => 'Cinq couches, dans cet ordre. Chacune n\'existe que grâce à la précédente.',

        'identity_title' => 'Identifiant de santé',
        'identity_desc'  => 'Une identité vérifiée et portable, qui appartient au patient et le suit d\'un établissement à l\'autre.',
        'index_title'    => 'Index patient',
        'index_desc'     => 'La même personne ramenée à un seul dossier entre les systèmes — rapprochée délibérément, jamais fusionnée au jugé.',
        'trust_title'    => 'Confiance & Accès',
        'trust_desc'     => 'Qui peut voir quoi, dans quel but, pour combien de temps — décidé par le patient et consigné à chaque fois.',
        'interop_title'  => 'Interopérabilité',
        'interop_desc'   => 'Un échange normalisé avec les systèmes déjà en place. Aucun remplacement, aucune table rase.',
        'care_title'     => 'Soins connectés',
        'care_desc'      => 'Le clinicien face au patient dispose de l\'historique dont dépendent ses soins.',
    ],

    'exchange' => [
        'title'    => 'Comment circule l\'information',
        'subtitle' => 'D\'un système hospitalier existant, via OpesCare, vers un autre système autorisé — et retour.',

        'source_title' => 'Système hospitalier existant',
        'source_desc'  => 'Le SIH, le DPI ou le registre déjà utilisé par l\'établissement',
        'core_title'   => 'OpesCare',
        'core_desc'    => 'Identité, consentement et échange',
        'target_title' => 'Un autre système autorisé',
        'target_desc'  => 'Un laboratoire, une pharmacie, un assureur ou une autorité sanitaire',

        'step_identify_title'  => 'Identifier',
        'step_identify_desc'   => 'Le patient est rattaché à son identifiant de santé — par code QR, identifiant ou recherche vérifiée.',
        'step_match_title'     => 'Rapprocher',
        'step_match_desc'      => 'Cette identité est rapprochée de l\'index patient. Un rapprochement incertain part en revue humaine, jamais en fusion silencieuse.',
        'step_authorize_title' => 'Autoriser',
        'step_authorize_desc'  => 'L\'établissement demandeur sollicite la portée dont il a besoin. Le patient approuve, refuse, ou révoque plus tard.',
        'step_exchange_title'  => 'Échanger',
        'step_exchange_desc'   => 'Seule la portée approuvée circule, dans un format standard que le système destinataire sait déjà lire.',
        'step_record_title'    => 'Consigner',
        'step_record_desc'     => 'L\'échange est inscrit dans la chronologie du patient et dans une piste d\'audit qu\'aucune des deux parties ne peut modifier discrètement.',

        'transports_label' => 'Connectez-vous comme vous travaillez déjà',
        'cta'              => 'Découvrir l\'interopérabilité',
    ],

    'pillars' => [
        'title'    => 'Ce qu\'OpesCare apporte à l\'écosystème',
        'subtitle' => 'Cinq capacités. Tout le reste de la plateforme en découle.',

        'identity_title' => 'Identité de santé',
        'identity_desc'  => 'Un identifiant de santé vérifié par patient, portable dans tous les établissements du réseau.',
        'index_title'    => 'Index patient',
        'index_desc'     => 'Une personne, un dossier — rapproché entre les systèmes sans fusion automatique probabiliste.',
        'record_title'   => 'Dossier longitudinal',
        'record_desc'    => 'Consultations, résultats, ordonnances et orientations dans une chronologie qui survit à chaque établissement.',
        'trust_title'    => 'Confiance & Accès',
        'trust_desc'     => 'Consentement, finalité, portée et durée à chaque échange, avec une piste d\'audit derrière chacun.',
        'interop_title'  => 'Interopérabilité',
        'interop_desc'   => 'HL7 FHIR R4 et une surface Connect qui rejoint l\'établissement sur le système qu\'il utilise déjà.',

        'also_label'     => 'Également connectés',
        'also_referrals' => 'Orientations interopérables — le prestataire suivant reçoit les bonnes informations cliniques, à la bonne portée.',
        'also_labs'      => 'Demandes d\'analyses et résultats vérifiés, rattachés au dossier du patient.',
        'also_pharmacy'  => 'Ordonnances et dispensation, reliées à l\'historique médicamenteux du patient.',
        'also_insurance' => 'La couverture d\'assurance comme attribut de l\'identifiant de santé — en lecture seule, et elle suit le patient.',
        'also_more'      => 'En savoir plus',
    ],

    'network' => [
        'title'    => 'Services du réseau',
        'subtitle' => 'Deux choses qu\'un patient doit trouver dans l\'urgence, résolues par le réseau plutôt qu\'au téléphone.',

        'medicine_title' => 'Recherche de médicaments',
        'medicine_desc'  => 'Voyez quelles pharmacies du réseau détiennent un médicament, et quand elles l\'ont déclaré.',
        'medicine_cta'   => 'Comment fonctionne la recherche de médicaments',
        'blood_title'    => 'Recherche de sang',
        'blood_desc'     => 'Trouvez du sang disponible par groupe et composant dans les hôpitaux et banques de sang connectés.',
        'blood_cta'      => 'Comment fonctionne la recherche de sang',

        'note' => 'La disponibilité est publiée par l\'établissement qui la détient, et horodatée. C\'est une information pour agir, pas une réservation.',
    ],

    'ecosystem' => [
        'title'    => 'Qui se connecte à OpesCare',
        'subtitle' => 'Tous les acteurs du parcours du patient, chacun par la surface qui lui convient.',

        'chip_patients'      => 'Patients',
        'chip_providers'     => 'Prestataires',
        'chip_hospitals'     => 'Hôpitaux',
        'chip_labs'          => 'Laboratoires',
        'chip_pharmacies'    => 'Pharmacies',
        'chip_insurers'      => 'Assureurs',
        'chip_public_health' => 'Santé publique',
        'chip_developers'    => 'Développeurs',

        'card_patients_title'   => 'Pour les patients',
        'card_patients_desc'    => 'Portez un seul identifiant de santé, décidez qui voit votre dossier, et trouvez des soins plus vite.',
        'card_facilities_title' => 'Pour les établissements de santé',
        'card_facilities_desc'  => 'Enregistrez vos patients, documentez les soins et échangez les dossiers sans remplacer votre système.',
        'card_orgs_title'       => 'Pour les organisations',
        'card_orgs_desc'        => 'Assureurs et autorités sanitaires, connectés à une identité et à une couverture vérifiées.',
        'card_devs_title'       => 'Pour les développeurs',
        'card_devs_desc'        => 'FHIR R4, une API Connect, des SDK, un widget intégrable et un sandbox pour développer.',
    ],

    'trust' => [
        'title' => 'Le patient garde le contrôle.',
        'desc'  => 'L\'accès est une décision, pas un réglage par défaut. Avant qu\'un établissement ne voie quoi que ce soit, le patient sait exactement ce qui est demandé — et peut refuser, ou changer d\'avis ensuite.',

        'q_who'       => 'Qui demande ?',
        'q_why'       => 'Pourquoi en ont-ils besoin ?',
        'q_what'      => 'Quelles données, précisément ?',
        'q_how_long'  => 'Pour combien de temps ?',
        'q_control'   => 'Approuver, refuser, ou révoquer.',
        'consent_cta' => 'Comment fonctionne le consentement',

        'emergency_title' => 'Quand l\'accès normal est impossible',
        'emergency_desc'  => 'L\'accès exceptionnel ouvre un profil d\'urgence restreint — identité, groupe sanguin, allergies, pathologies actives et contact d\'urgence. Il exige un motif déclaré, alerte le patient, et fait l\'objet d\'une revue.',
        'emergency_cta'   => 'À propos de l\'accès d\'urgence',

        'pillar_private_title'   => 'Confidentiel par conception',
        'pillar_private_desc'    => 'Accès au strict nécessaire, appliqué par rôle et par finalité.',
        'pillar_audit_title'     => 'Auditable',
        'pillar_audit_desc'      => 'Chaque accès est consigné, et visible par le patient concerné.',
        'pillar_standards_title' => 'Fondé sur les standards',
        'pillar_standards_desc'  => 'HL7 FHIR R4 et OAuth 2.0. Aucun verrouillage propriétaire.',
        'pillar_local_title'     => 'Conçu pour les systèmes de santé africains',
        'pillar_local_desc'      => 'Priorité au Cameroun, aligné sur le MINSANTE, en anglais et en français.',

        'security_cta' => 'Centre de sécurité et de confiance',
    ],

    'footer_cta' => [
        'title'         => 'Connectez votre système de santé au réseau OpesCare.',
        'subtitle'      => 'Donnez à chaque patient un identifiant de santé, et au clinicien face à lui l\'historique dont dépendent ses soins.',
        'cta_primary'   => 'Connecter un système',
        'cta_secondary' => 'Obtenir un identifiant de santé',
        'faq_prompt'    => 'D\'autres questions ?',
        'faq_cta'       => 'Consulter la FAQ',
    ],
    'nav' => [
        'security'          => 'Sécurité',
        'contact'           => 'Contact',
        'how_it_works'      => 'Comment ça marche',
        'demo'              => 'Demander une démo',
        'product'           => 'Produit',
        'solutions'         => 'Solutions',
        'interop'           => 'Interopérabilité',
        'resources'         => 'Ressources',
        'sign_in'           => 'Se connecter',
        'get_started'       => 'Créer mon Health ID',
        // Étiquettes de liens (tiroir mobile + menus déroulants)
        'company'           => 'Entreprise',
        'about'             => "À propos d'Opes Health Systems",
        'security_page'     => 'Normes de sécurité',
        'privacy'           => 'Politique de confidentialité',
        'privacy_short'     => 'Confidentialité',
        'terms'             => "Conditions d'utilisation",
        'terms_short'       => 'Conditions',
        'contact_support'   => 'Contacter le support',
        'faq'               => 'FAQ',
        'help_center'       => "Centre d'aide",
        'system_status'     => 'Statut du système',
        'how_it_works_link' => 'Comment fonctionne OpesCare',
        'health_id'         => 'ID Santé',
        'consent_access'    => 'Consentement & Accès',
        'care_map'          => 'Carte de soins',
        'emergency_access'  => "Accès d'urgence",
        'for_patients'      => 'Pour les patients',
        'for_hospitals'     => 'Pour les hôpitaux et cliniques',
        'for_pharmacies'    => 'Pour les pharmacies',
        'for_labs'          => 'Pour les laboratoires',
        'for_insurers'      => 'Pour les assureurs',
        'for_public_health' => 'Pour la santé publique',
        'interop_overview'  => "Vue d'ensemble",
        'api_sdk'           => 'Connect API & SDK',
        'partnerships'      => 'Partenariats',
        // Groupes Plateforme / Réseau (navigation restructurée)
        'platform'             => 'Plateforme',
        'health_record'        => 'Dossier de santé',
        'trust_access'         => 'Confiance & Accès',
        'network'              => 'Réseau',
        'medicine_finder'      => 'Recherche de médicaments',
        'blood_finder'         => 'Recherche de sang',
        'connected_facilities' => 'Établissements connectés',
        'developers'           => 'Développeurs',
    ],

    'footer' => [
        'desc'          => "OpesCare est une plateforme d'identité de santé numérique et d'interopérabilité créée par Opes Health Systems Sarl.",
        'col_product'   => 'Produit',
        'col_orgs'      => 'Pour les Organisations',
        'col_devs'      => 'Développeurs',
        'col_company'   => 'Entreprise',
        'copyright'     => '© ' . date('Y') . " OpesCare. Une plateforme d'identité de santé et d'interopérabilité créée par Opes Health Systems Sarl. Tous droits réservés.",
        'product_links' => ["ID Santé", 'Chronologie Patient', 'Contrôle du Consentement', "Accès d'Urgence", 'Disponibilité Médicaments', 'Réseau Sanguin'],
        'org_links'     => ['Hôpitaux & Cliniques', 'Laboratoires', 'Pharmacies', 'Assureurs', 'Organisations de Santé Publique'],
        'dev_links'     => ['API Connect', 'SDK Connect', 'Widget Connect', 'Agent de liaison Bridge', 'Webhooks & Alertes'],
        'company_links' => ["À propos d'Opes Health Systems", 'Normes de Sécurité', 'Politique de Confidentialité', "Conditions d'Utilisation", 'Partenariats'],
        // Étiquettes de liens individuels pour les colonnes du pied de page
        'link_how_it_works'  => 'Comment fonctionne OpesCare',
        'link_health_id'     => 'ID Santé',
        'link_timeline'      => 'Chronologie du patient',
        'link_consent'       => 'Contrôle du consentement',
        'link_emergency'     => "Accès d'urgence",
        'link_medication'    => 'Recherche de médicaments',
        'link_blood'         => 'Recherche de sang',
        'link_hospitals'     => 'Hôpitaux et cliniques',
        'link_labs'          => 'Laboratoires',
        'link_pharmacies'    => 'Pharmacies',
        'link_insurers'      => 'Assureurs',
        'link_public_health' => 'Organisations de santé publique',
        'link_api'           => 'Connect API',
        'link_sdk'           => 'Connect SDK',
        'link_widget'        => 'Connect Widget',
        'link_bridge'        => 'Bridge Agent',
        'link_webhooks'      => 'Webhooks & Alertes',
        'link_interop'       => "Vue d'ensemble de l'interopérabilité",
        'link_about'         => "À propos d'Opes Health Systems",
        'link_security'      => 'Normes de sécurité',
        'link_privacy'       => 'Politique de confidentialité',
        'link_terms'         => "Conditions d'utilisation",
        'link_faq'           => 'FAQ',
        'link_partnerships'  => 'Partenariats',
        'link_status'        => 'Statut du système',
    ],

    /* ── Objet d'identité du hero ───────────────────── */
    'hero_card' => [
        'demo_id'         => 'CM-HID-7KQ9-MP42-X8D1',
        'label_health_id' => 'Identifiant de Santé',
        'label_verified'  => 'Vérifié',
    ],

    /* ── Métadonnées de page ────────────────────────── */
    'page_title_home' => 'OpesCare | Identité de santé et interopérabilité pour des soins connectés',
    'page_desc_home'  => 'OpesCare est une plateforme d\'identité de santé et d\'interopérabilité. Le patient porte un identifiant de santé vérifié et un dossier longitudinal ; les établissements échangent les informations autorisées depuis les systèmes qu\'ils utilisent déjà.',
];
