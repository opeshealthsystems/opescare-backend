<?php

/*
|--------------------------------------------------------------------------
| Pages de recherche — médicaments, sang, urgences
|--------------------------------------------------------------------------
| Miroir 1:1 de lang/en/finder_search.php (php scripts/i18n-audit.php doit
| rapporter 0 écart). Même règle d'honnêteté : la plateforme relaie ce que
| les établissements déclarent — elle ne vérifie aucun rayon.
*/

return [

    'common' => [
        'brand_home'        => 'OpesCare',
        'directory_link'    => 'Tous les établissements',
        'emergency_link'    => 'Urgences',
        'call'              => 'Appeler',
        'directions'        => 'Itinéraire',
        'view_profile'      => 'Voir la fiche',
        'no_phone'          => 'Aucun numéro de téléphone enregistré',
        'distance_km'       => ':distance km',
        'use_my_location'   => 'Utiliser ma position',
        'locating'          => 'Recherche de votre position…',
        'location_failed'   => 'Position indisponible — les résultats ne sont pas triés par distance.',
        'location_active'   => 'Résultats triés par distance depuis votre position.',
        'info_may_change'   => 'Les informations peuvent changer. Appelez l’établissement avant de vous déplacer.',
        'showing_first'     => 'Affichage des :count premiers résultats.',
        'search'            => 'Rechercher',
    ],

    'medicine' => [
        'intro'             => 'Recherchez le stock que les pharmacies ont déclaré au réseau. OpesCare ne vérifie aucun rayon — chaque résultat est la déclaration de la pharmacie elle-même.',
        'search_label'      => 'De quel médicament avez-vous besoin ?',
        'search_placeholder' => 'ex. Paracétamol, Amoxicilline…',
        'results_count'     => '{1} :count pharmacie a déclaré ce médicament.|[2,*] :count pharmacies ont déclaré ce médicament.',
        'reported_line'     => 'Déclaré par la pharmacie · :time',
        'status_in_stock'   => 'Déclaré en stock',
        'status_low'        => 'Stock faible déclaré',
        'status_out'        => 'Déclaré en rupture',
        'status_unknown'    => 'Aucun statut déclaré',
        'price_fcfa'        => ':price FCFA',
        'pack_of'           => 'Boîte de :size',
        'quantity_range'    => 'Environ :range boîtes déclarées',
        'before_you_travel' => 'Une déclaration n’est pas une réservation — le stock évolue. Vérifiez l’heure de la déclaration et appelez la pharmacie avant de vous déplacer.',
        'empty_title'       => 'Aucune pharmacie n’a encore déclaré de stock pour ce médicament.',
        'empty_body'        => 'Cela ne veut pas dire qu’il est introuvable — seulement qu’aucune pharmacie du réseau ne l’a déclaré. Appelez directement une pharmacie proche.',
        'empty_cta'         => 'Trouver des pharmacies proches',
    ],

    'blood' => [
        'intro'             => 'Les hôpitaux et banques de sang publient leurs disponibilités sur le réseau. OpesCare relaie leurs déclarations — il ne compte pas les poches lui-même.',
        'group_label'       => 'Choisissez un groupe sanguin',
        'component_label'   => 'Composant',
        'component_whole_blood' => 'Sang total',
        'component_red_cells'   => 'Concentré de globules rouges',
        'component_plasma'      => 'Plasma',
        'component_platelets'   => 'Plaquettes',
        'search_button'     => 'Rechercher la disponibilité',
        'results_count'     => '{1} :count établissement a publié une disponibilité pour :group.|[2,*] :count établissements ont publié une disponibilité pour :group.',
        'units_reported'    => 'Poches déclarées : :range',
        'reported_line'     => 'Déclaré par l’établissement · :time',
        'before_you_travel' => 'La disponibilité peut changer en quelques heures. Appelez l’établissement avant de vous déplacer — ne vous fiez pas à cette page seule.',
        'empty_title'       => 'Aucune banque de sang n’a publié de disponibilité pour :group actuellement.',
        'empty_body'        => 'Une absence ici ne signifie pas une absence partout — seulement qu’aucun établissement du réseau ne l’a publiée. Appelez directement les hôpitaux et banques de sang proches.',
        'empty_emergency_cta' => 'Conduite à tenir en urgence',
        'request_title'     => 'Besoin de sang en urgence ?',
        'request_body'      => 'Une demande de sang s’adresse à un établissement depuis l’application patient OpesCare, ou en appelant directement la banque de sang. Si le pronostic vital est engagé, passez par la page urgences.',
        'request_app_cta'   => 'À propos de l’application patient',
        'clinical_note'     => 'OpesCare ne réalise aucune compatibilité croisée et ne valide aucune poche pour transfusion. Les tests de compatibilité restent de la responsabilité de professionnels qualifiés.',
    ],

    'emergency' => [
        'page_title'        => 'Urgences — appelez d’abord | OpesCare',
        'call_now_title'    => 'Si le pronostic vital est engagé, appelez maintenant',
        'call_now_sub'      => 'Ne vous fiez pas à cette page seule — un appel est plus rapide que n’importe quelle liste.',
        'dial_112'          => 'Appeler le 112',
        'dial_119'          => 'Appeler le 119',
        'dial_hint'         => 'Numéros d’urgence nationaux',
        'nearest_title'     => 'Hôpitaux près de vous',
        'nearest_sub'       => 'Triés par distance lorsque votre position est activée',
        'call_before'       => 'Appelez avant de vous déplacer',
        'back_to_directory' => 'Tous les établissements',
    ],

];
