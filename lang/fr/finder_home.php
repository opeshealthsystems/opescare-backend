<?php

/*
|--------------------------------------------------------------------------
| Page d'accueil du Finder — miroir 1:1 de lang/en/finder_home.php
|--------------------------------------------------------------------------
| Ton : service public — calme, direct, sans discours commercial.
| Mêmes règles que la version anglaise : jamais « vérifié » (le fait exact
| est « inscrit au registre national des formations sanitaires ») et les
| limites honnêtes restent visibles (« appelez avant de vous déplacer »).
*/

return [

    // Le layout ajoute « — OpesCare » à chaque titre ; pas de marque ici.
    'page_title' => 'Trouvez médicaments, sang et rendez-vous près de chez vous',
    'page_desc'  => 'Recherchez parmi :count établissements de santé dans les 10 régions du Cameroun — pharmacies, hôpitaux, cliniques et laboratoires. Gratuit, en français et en anglais.',

    // ── 01 · Héros — la tâche, pas un slogan ───────────────────────────────
    'hero' => [
        'eyebrow'           => "L'annuaire des établissements de santé du Cameroun",
        'title'             => 'Trouvez des soins près de chez vous',
        'lead'              => 'Médicaments, sang et rendez-vous — cherchez un établissement de santé près de chez vous et appelez avant de vous déplacer.',
        'nav_label'         => 'Trouver des soins',
        'medicine_label'    => 'Trouver un médicament',
        'medicine_sub'      => 'Recherchez les pharmacies près de chez vous',
        'blood_label'       => 'Trouver du sang',
        'blood_sub'         => "Hôpitaux avec service de transfusion — appelez d'abord",
        'appointment_label' => 'Prendre rendez-vous',
        'appointment_sub'   => 'Hôpitaux et cliniques à réserver ou à appeler',
    ],

    // ── 02 · Bandeau de confiance — des faits, rien d'autre ────────────────
    'trust' => [
        'facilities' => ':count établissements inscrits au registre national des formations sanitaires du Cameroun',
        'regions'    => 'Les :count régions du Cameroun couvertes',
        'bilingual'  => 'En français et en anglais',
        'free'       => 'Gratuit',
    ],

    // ── 03 · Comment ça marche — les trois étapes du patient ───────────────
    'how' => [
        'eyebrow'     => 'Comment ça marche',
        'title'       => 'Trois étapes, et vous êtes en route',
        'step1_title' => 'Recherchez',
        'step1_desc'  => "Dites-nous ce qu'il vous faut — un médicament, du sang ou un rendez-vous — et voyez les établissements près de chez vous.",
        'step2_title' => 'Appelez ou réservez',
        'step2_desc'  => 'Chaque fiche affiche un numéro de téléphone. Appelez pour confirmer, ou réservez lorsque la réservation en ligne est proposée.',
        'step3_title' => 'Déplacez-vous',
        'step3_desc'  => "Récupérez l'adresse et l'itinéraire, puis déplacez-vous une fois la confirmation obtenue.",
    ],

    // ── 04 · Les trois recherches — ce qu'elles font, et leurs limites ─────
    'finders' => [
        'eyebrow'          => 'Ce que vous pouvez trouver',
        'title'            => 'Trois façons de trouver des soins',
        'medicine_title'   => 'Médicaments',
        'medicine_desc'    => 'Recherchez les pharmacies partout au Cameroun, par nom ou par ville, avec numéros de téléphone et horaires.',
        'medicine_limit'   => 'Les stocks sont déclarés par les pharmacies et évoluent dans la journée — appelez toujours avant de vous déplacer.',
        'medicine_cta'     => 'Rechercher une pharmacie',
        'blood_title'      => 'Sang',
        'blood_desc'       => 'Repérez les hôpitaux près de chez vous qui assurent la transfusion et le don de sang.',
        'blood_limit'      => "La disponibilité est déclarée par les établissements. En cas d'urgence, appelez l'hôpital d'abord — ne vous déplacez jamais sur la seule foi de la fiche.",
        'blood_cta'        => 'Voir les hôpitaux',
        'appointment_title' => 'Rendez-vous',
        'appointment_desc' => 'Parcourez les hôpitaux et les cliniques, avec leurs services, horaires et numéros de téléphone.',
        'appointment_limit' => "Tous les établissements ne proposent pas encore la réservation en ligne — à défaut, la fiche vous donne un numéro à appeler.",
        'appointment_cta'  => 'Parcourir les établissements',
    ],

    // ── 05 · Établissements — revendiquez votre fiche ──────────────────────
    'claim' => [
        'title' => 'Vous gérez une pharmacie ou une clinique ?',
        'desc'  => 'Revendiquez votre fiche pour tenir à jour votre numéro de téléphone, vos horaires et vos stocks — afin que les patients vous joignent avec des informations exactes.',
        'hint'  => 'Recherchez votre établissement, ouvrez sa fiche et lancez la revendication depuis celle-ci.',
        'cta'   => 'Trouver ma fiche',
    ],

    // ── 06 · Couverture — les dix régions ──────────────────────────────────
    'coverage' => [
        'title'   => 'Disponible dans les :count régions',
        'desc'    => 'Des établissements sont référencés dans chaque région du Cameroun.',
        'regions' => [
            'Adamaoua',
            'Centre',
            'Est',
            'Extrême-Nord',
            'Littoral',
            'Nord',
            'Nord-Ouest',
            'Ouest',
            'Sud',
            'Sud-Ouest',
        ],
    ],

];
