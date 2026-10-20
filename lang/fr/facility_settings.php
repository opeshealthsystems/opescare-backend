<?php

/*
|--------------------------------------------------------------------------
| Paramètres « Mon établissement » — identité, localisation, horaires, contacts
|--------------------------------------------------------------------------
| La page partagée par tous les types d'établissement sauf les pharmacies
| (qui utilisent pharmacy_settings.php). Doit rester 1:1 avec
| lang/en/facility_settings.php (scripts/i18n-audit.php).
*/

return [

    'title'           => "Mon établissement",
    'subtitle'        => "Ce que les patients voient de cet établissement dans l'annuaire des soins : son nom, sa position sur la carte, ses horaires et ses coordonnées.",
    'nav'             => "Mon établissement",
    'breadcrumb_home' => "Tableau de bord",

    // Titre de la page selon le type public d'établissement ; repli sur `title`.
    'title_by_type' => [
        'hospital'          => "Mon hôpital",
        'clinic'            => "Ma clinique",
        'health_center'     => "Mon centre de santé",
        'laboratory'        => "Mon laboratoire",
        'dental'            => "Mon cabinet dentaire",
        'imaging_center'    => "Mon centre d'imagerie",
        'eye_clinic'        => "Ma clinique ophtalmologique",
        'specialist'        => "Mon cabinet",
        'diagnostic_center' => "Mon centre de diagnostic",
        'maternity'         => "Ma maternité",
        'dispensary'        => "Mon dispensaire",
        'blood_bank'        => "Ma banque de sang",
        'pharmacy'          => "Ma pharmacie",
    ],

    // ── État de la fiche ──────────────────────────────────────────────────
    'badge_linked'         => "Fiche publique liée",
    'badge_claimed'        => "Revendiquée",
    'badge_verified'       => "Vérifiée",
    'badge_self_reported'  => "Déclarée par l'établissement",
    'badge_unverified'     => "Pas encore vérifiée",
    'badge_not_listed'     => "Absente des recherches",
    'badge_no_coordinates' => "Sans position sur la carte",

    'tenant_label' => "Compte",
    'tenant_type'  => "Type",

    // ── Non liée ──────────────────────────────────────────────────────────
    'not_linked_title' => "Votre fiche publique n'est pas encore liée",
    'not_linked_body'  => "Ce compte (:name) n'est relié à aucune fiche de l'annuaire public : les patients qui cherchent des soins ne peuvent pas voir cet établissement et rien sur cette page ne peut encore être publié. La liaison est faite par un administrateur OpesCare, jamais automatiquement : deux établissements différents ne doivent jamais être confondus.",
    'not_linked_what'  => "Que faire : retrouvez votre établissement dans l'annuaire et revendiquez-le. Une fois la demande examinée et la fiche liée, cette page devient modifiable.",
    'not_linked_cta'   => "Retrouver et revendiquer ma fiche",
    'not_linked_forms' => "L'identité et la localisation ne peuvent pas être modifiées tant que la fiche n'est pas liée.",

    // ── Alertes de l'annuaire ─────────────────────────────────────────────
    'warn_no_coordinates' => "Les patients ne peuvent pas vous trouver tant que la position sur la carte n'est pas définie. L'annuaire ne place sur la carte et dans les résultats triés par distance que les établissements positionnés.",
    'warn_not_listed'     => "Cette fiche n'est pas active (statut : :status) ; elle n'apparaît donc pas dans les recherches. Contactez le support OpesCare si vous pensez qu'il s'agit d'une erreur.",
    'review_note'         => "Les modifications du nom, du numéro d'agrément, de l'adresse et de la position sur la carte changent ce que dit l'annuaire public. Elles sont publiées immédiatement et enregistrées pour examen ; une fiche vérifiée repasse en « déclarée par l'établissement » jusqu'à nouvelle vérification.",

    // ── Identité ──────────────────────────────────────────────────────────
    'section_identity'  => "Identité",
    'label_name'        => "Nom de l'établissement",
    'hint_name'         => "Tel qu'il apparaît sur la fiche publique et sur chaque page de ce portail.",
    'label_license'     => "Numéro d'agrément",
    'hint_license'      => "Le numéro d'autorisation MINSANTE, si vous l'avez. Sinon, laissez vide.",
    'label_description' => "Description",
    'hint_description'  => "Quelques lignes que les patients liront : services, spécialités, urgences, stationnement.",
    'btn_save_identity' => "Enregistrer l'identité",

    // ── Contacts ──────────────────────────────────────────────────────────
    'section_contacts'      => "Contacts",
    'label_phone_primary'   => "Téléphone principal",
    'label_phone_secondary' => "Second téléphone",
    'label_email'           => "E-mail",
    'label_website'         => "Site web",
    'hint_phone'            => "Le numéro que les patients appellent. La valeur de remplissage du registre ne leur est jamais montrée.",

    // ── Localisation ──────────────────────────────────────────────────────
    'section_location'  => "Localisation",
    'location_intro'    => "Cliquez sur la carte ou déplacez le repère jusqu'à l'entrée principale. Les coordonnées se remplissent d'elles-mêmes ; vous pouvez aussi les saisir.",
    'label_address'     => "Adresse",
    'label_city'        => "Ville",
    'label_region'      => "Région",
    'label_latitude'    => "Latitude",
    'label_longitude'   => "Longitude",
    'hint_coordinates'  => "Les deux ou aucune. Latitude de −90 à 90, longitude de −180 à 180.",
    'btn_save_location' => "Enregistrer la localisation",
    'map_locate_hint'   => "Vous êtes sur place ? Utilisez le bouton de localisation sur la carte.",

    // ── Horaires ──────────────────────────────────────────────────────────
    'section_hours'  => "Horaires d'ouverture",
    'hours_intro'    => "Un jour sans horaire, ni marqué ouvert 24 h/24, ni marqué fermé, reste non renseigné.",
    'btn_save_hours' => "Enregistrer les horaires",
    // ── Photo / logo ──────────────────────────────────────────────────────
    'section_photo'      => "Photo ou logo",
    'label_photo'        => "Image",
    'hint_photo'         => "JPEG, PNG ou WebP, 2 Mo au plus, au moins 200 × 200 pixels. Elle remplace l'image actuelle.",
    'photo_none'         => "Aucune image pour le moment",
    'photo_alt'          => "Image de l'établissement",
    'btn_save_photo'     => "Téléverser l'image",
    'photo_not_an_image' => "Le fichier n'a pas pu être lu comme une image.",
    'flash_photo_saved'  => "Image mise à jour.",


    // ── Services publics ──────────────────────────────────────────────────
    'services_intro' => "Les services que les patients voient sur la fiche publique de cet établissement dans le Finder — par exemple la vaccination des enfants, la consultation prénatale, le test de diagnostic rapide du paludisme ou la planification familiale. La liste codée et tarifée des services se gère à part, dans Nos services.",

    // ── Messages ──────────────────────────────────────────────────────────
    'flash_saved'       => ":count champ(s) mis à jour.",
    'flash_no_changes'  => "Aucun changement.",
    'flash_hours_saved' => "Horaires enregistrés pour :count jour(s).",
    'services_moved_to_catalogue' => 'Les services se gèrent désormais dans le catalogue des services.',
    'services_from_catalogue' => 'Cette liste est remplie automatiquement à partir de votre catalogue des services : chaque service actif du catalogue apparaît sur votre fiche publique du Finder. Pour ajouter, renommer ou retirer un service, modifiez le catalogue.',
    'services_open_catalogue' => 'Ouvrir le catalogue des services',
];
