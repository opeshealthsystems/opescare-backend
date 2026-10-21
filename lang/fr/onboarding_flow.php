<?php

/*
|--------------------------------------------------------------------------
| Parcours d'inscription des établissements — assistant, sélecteur, revendication
|--------------------------------------------------------------------------
*/

return [
    'rail' => [
        'title'         => 'Inscrivez votre établissement sur OpesCare',
        'lead'          => 'Environ 10 minutes. Vous pouvez vous arrêter à tout moment et reprendre plus tard sur cet appareil.',
        'trust_review'  => 'Vérifié par OpesCare avant activation',
        'trust_private' => 'Documents conservés en privé, jamais publiés',
        'help'          => 'Aide : :contact',
        'help_page'     => 'Contacter le support',
        'steps_label'   => "Étapes d'inscription",
    ],

    'steps' => [
        'type'      => ['title' => "Type d'établissement", 'hint' => 'Hôpital, clinique, pharmacie…'],
        'details'   => ['title' => "Détails de l'établissement", 'hint' => 'Raison sociale, licence, adresse'],
        'person'    => ['title' => 'Personne responsable', 'hint' => 'Qui nous contactons et vérifions'],
        'services'  => ['title' => 'Services', 'hint' => 'Ce que trouvent les patients'],
        'documents' => ['title' => 'Documents', 'hint' => 'Licence et immatriculation'],
        'review'    => ['title' => 'Vérifier et envoyer', 'hint' => 'Contrôler et accepter'],
    ],

    'step_of'  => 'Étape :current sur :total',
    'progress' => "Progression de l'inscription",

    'type' => [
        'eyebrow' => "Type d'établissement",
        'h1'      => "Quel type d'établissement inscrivez-vous ?",
        'sub'     => 'Cela configure les bons postes, rôles et la fiche publique pour votre équipe.',
        'legend'  => "Type d'établissement",
        'cards' => [
            'hospital'      => ['title' => 'Hôpital', 'desc' => 'Services, bloc, urgences, hospitalisation'],
            'clinic'        => ['title' => 'Clinique ou centre de santé', 'desc' => 'Consultations externes, maternité'],
            'pharmacy'      => ['title' => 'Pharmacie', 'desc' => 'Stock, dispensation, ordonnances'],
            'laboratory'    => ['title' => 'Laboratoire', 'desc' => 'Prélèvements, résultats, valeurs critiques'],
            'imaging'       => ['title' => "Centre d'imagerie", 'desc' => 'Liste de travail, comptes rendus, validation'],
            'blood_bank'    => ['title' => 'Banque de sang', 'desc' => 'Stock de sang et demandes'],
            'public_health' => ['title' => 'Santé publique ou recherche', 'desc' => 'Programmes, surveillance, rapports agrégés'],
        ],
        'claim_title' => 'Déjà référencé sur OpesCare ?',
        'claim_desc'  => "Revendiquez plutôt votre fiche existante : retrouvez-la dans l'annuaire et gardez votre position sur la carte.",
        'free_title'  => 'Pas encore de système ?',
        'free_desc'   => 'Les structures communautaires et les ONG peuvent recevoir un système OPES gratuitement.',
    ],

    'details' => [
        'eyebrow'           => "Détails de l'établissement",
        'h1'                => 'Parlez-nous de votre établissement',
        'sub'               => "Utilisez les noms exactement comme sur votre autorisation d'exploitation.",
        'identity'          => 'Identité',
        'identity_help'     => 'Affichée aux patients sur votre fiche publique.',
        'registration'      => 'Immatriculation',
        'registration_help' => 'Vérifiée par notre équipe ; jamais publiée.',
        'license_help'      => "Indiqué en haut de votre autorisation d'exploitation.",
        'contact'           => 'Adresse et contact',
        'contact_help'      => "Où les patients et notre équipe peuvent joindre l'établissement.",
        'address_ph'        => 'ex. Derrière le marché Mvog-Ada, Yaoundé',
        'phone_help'        => 'Numéro camerounais ; nous ajoutons +237 pour vous.',
        'match_note'        => 'Déjà sur la carte OpesCare ? Notre équipe rattache votre demande à votre fiche existante pour que les patients continuent de vous trouver.',
    ],

    'person' => [
        'eyebrow'     => 'Personne responsable',
        'h1'          => 'Qui devons-nous contacter et vérifier ?',
        'sub'         => 'La personne responsable de cet établissement sur OpesCare. Nous la vérifions avant activation.',
        'legend'      => 'Contact principal',
        'legend_help' => "Utilisé uniquement pour cette demande et l'activation du compte.",
        'role_ph'     => 'Directeur médical, administrateur informatique, pharmacien titulaire…',
    ],

    'services' => [
        'eyebrow'            => 'Services',
        'h1'                 => 'Que trouveront les patients ici ?',
        'sub'                => 'Choisissez les services proposés. Vous pourrez les modifier depuis votre tableau de bord.',
        'legend'             => 'Services proposés',
        'legend_help'        => "Cochez tout ce qui s'applique.",
        'systems'            => 'Vos systèmes actuels',
        'systems_help'       => "Nous aide à préparer votre installation. Rien de ceci n'est publié.",
        'software_ph'        => 'ex. une plateforme sur mesure, un tableur, aucun',
        'svc_reporting'      => "Comptes rendus d'imagerie et validation",
        'svc_blood_stock'    => 'Stock de sang par groupe',
        'svc_blood_requests' => 'Demandes de sang des établissements',
        'imaging_msg'        => "Les centres d'imagerie disposent d'une liste de travail, des comptes rendus et d'un poste de validation après approbation.",
        'imaging_notice'     => "Les comptes rendus d'imagerie ne sont transmis aux patients qu'après validation par un lecteur qualifié.",
        'blood_bank_msg'     => 'Les banques de sang publient la disponibilité par groupe pour que patients et hôpitaux trouvent du sang.',
        'blood_bank_notice'  => "La disponibilité du sang provient uniquement de vos stocks réels, jamais d'estimations.",
    ],

    'documents' => [
        'eyebrow'     => 'Documents',
        'h1'          => 'Téléversez votre licence et votre immatriculation',
        'sub'         => 'Notre équipe les vérifie avant la mise en ligne de votre établissement.',
        'legend'      => 'Pièces justificatives',
        'legend_help' => "PDF, JPG ou PNG. Conservés en privé et vus uniquement par l'équipe de vérification.",
        'not_saved'   => "Les fichiers ne sont pas conservés entre deux visites. Si vous quittez, choisissez-les à nouveau avant l'envoi.",
    ],

    'review' => [
        'eyebrow'     => 'Vérifier et envoyer',
        'h1'          => "Vérifiez avant d'envoyer",
        'sub'         => 'Revenez à une étape pour modifier une réponse.',
        'summary'     => 'Vos réponses',
        'agree'       => 'Déclarations',
        'agree_help'  => "Les trois sont obligatoires pour l'envoi.",
        'timing'      => 'Notre équipe examine chaque demande avant activation et vous contacte par e-mail et téléphone.',
        'not_given'   => 'Non renseigné',
        'files_count' => ':count sur 2 choisis',
        'l_type'      => 'Type',
        'l_name'      => 'Raison sociale',
        'l_trade'     => "Nom d'usage",
        'l_reg'       => "Numéro d'immatriculation",
        'l_licence'   => 'Licence',
        'l_address'   => 'Adresse',
        'l_phone'     => 'Téléphone principal',
        'l_email'     => 'E-mail principal',
        'l_person'    => 'Personne responsable',
        'l_contact'   => 'Contact',
        'l_services'  => 'Services',
        'l_documents' => 'Documents',
    ],

    'foot' => [
        'back'      => 'Retour',
        'continue'  => 'Continuer',
        'submit'    => 'Envoyer la demande',
        'saved'     => 'Progression enregistrée sur cet appareil',
        'not_saved' => 'La progression ne peut pas être enregistrée dans ce navigateur',
        'required'  => "Veuillez remplir les champs marqués d'un *.",
        'errors'    => 'Certaines réponses sont à corriger :',
    ],

    'success' => [
        'eyebrow' => 'Demande reçue',
    ],

    'selector' => [
        'eyebrow'         => 'Créer un compte',
        'people'          => 'Pour les personnes',
        'facilities'      => 'Pour les établissements et organisations',
        'facilities_help' => 'Les demandes sont vérifiées par OpesCare avant activation.',
        'partners'        => 'Pour les partenaires',
    ],

    'claim' => [
        'eyebrow'    => 'Revendiquer votre fiche',
        'you'        => 'À propos de vous',
        'you_help'   => 'Nous vérifions la personne qui revendique une fiche.',
        'proof'      => "Preuve d'autorité",
        'proof_help' => 'Un document montrant que vous représentez cet établissement.',
        'after'      => "Après l'envoi, confirmez le code reçu par SMS depuis votre page de revendications.",
    ],
];
