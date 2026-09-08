<?php

/**
 * Catalogue des services de l'établissement (/facility/catalogue) — « Nos services ».
 *
 * Espace de noms dédié afin d'ajouter cet écran sans modifier un fichier de
 * traduction existant. EN et FR doivent rester strictement 1:1 ;
 * scripts/i18n-audit.php est une barrière CI et vérifie les clés, l'encodage
 * et les :variables.
 *
 * VOLONTAIREMENT ABSENTS : les libellés des codes eux-mêmes. « Hemoglobin
 * [Mass/volume] in Blood » est le libellé publié du code LOINC 718-7 dans
 * toutes les langues ; le traduire produirait une chaîne qui ne serait plus le
 * libellé de ce code. Le dictionnaire porte ses propres titres français
 * lorsque l'éditeur en publie ; sinon l'anglais publié est affiché, ce qui est
 * exact et non pas simplement anglophone.
 */
return [

    'nav'           => 'Nos services',
    'page_title'    => 'Nos services',
    'page_subtitle' => 'Les examens, actes et prestations proposés par cet établissement, choisis dans le dictionnaire institutionnel des codes.',
    'breadcrumb_home' => 'Établissement',

    // Onglets
    'tab_active'  => 'Proposés (:count)',
    'tab_retired' => 'Retirés (:count)',

    // Filtres
    'all_systems'  => 'Tous les systèmes de codes',
    'filter_apply' => 'Filtrer',

    // Tableau
    'col_code'       => 'Code',
    'col_name'       => 'Prestation',
    'col_price'      => 'Tarif',
    'col_turnaround' => 'Délai',
    'col_actions'    => 'Actions',

    'price_none'      => 'Non publié',
    'price_none_hint' => 'Cet établissement n\'a pas publié de tarif. Cela ne signifie pas que la prestation est gratuite.',
    'unresolved_code' => 'Ce code ne figure pas dans le dictionnaire chargé',
    'retired_on'      => 'Retiré le :date',

    'empty_active'  => 'Aucune prestation pour l\'instant. Parcourez le dictionnaire pour ajouter les services proposés par cet établissement.',
    'empty_retired' => 'Aucune prestation n\'a été retirée.',

    // Actions
    'action_browse'  => 'Ajouter une prestation',
    'action_add'     => 'Ajouter',
    'action_retire'  => 'Ne plus proposer',
    'action_restore' => 'Proposer à nouveau',
    'action_save'    => 'Enregistrer',
    'back_to_list'   => 'Retour à nos services',

    // Parcourir
    'browse_title'        => 'Ajouter une prestation',
    'browse_subtitle'     => 'Recherchez dans le dictionnaire institutionnel des codes et ajoutez ce que réalise cet établissement.',
    'browse_placeholder'  => 'Rechercher par code ou par nom — par ex. paludisme, 718-7, E11',
    'browse_search'       => 'Rechercher',
    'browse_only_common'  => 'Codes courants uniquement',
    'browse_no_results'   => 'Aucun code ne correspond à cette recherche.',
    'browse_never_imported' => 'Le dictionnaire des codes est vide sur cette installation : aucune terminologie n\'a encore été importée. Il ne s\'agit pas d\'une recherche restée sans résultat.',
    'browse_already'      => 'Déjà proposé',
    'browse_already_retired' => 'Retiré ici',
    'browse_total'        => ':count codes dans le dictionnaire',

    // Champs
    'field_local_name'      => 'Votre appellation',
    'field_local_name_hint' => 'Facultatif. Le libellé publié reste toujours affiché à côté.',
    'field_price'           => 'Tarif (FCFA)',
    'field_price_hint'      => 'Facultatif, en francs entiers. Laissez vide si vous ne publiez pas de tarif — un tarif est une information destinée aux patients, jamais une condition d\'accès aux soins.',
    'field_turnaround'      => 'Délai',
    'field_turnaround_hint' => 'Facultatif — par ex. « Le jour même », « 48 h », « Les mardis uniquement ».',
    'field_notes'           => 'Remarques',

    // Messages
    'flash_added'        => 'Ajouté à vos prestations.',
    'flash_updated'      => 'Enregistré.',
    'flash_retired'      => 'N\'est plus proposé. L\'entrée est conservée, pas supprimée.',
    'flash_restored'     => 'De nouveau proposé.',
    'flash_unknown_code' => 'Ce code ne figure pas dans le dictionnaire institutionnel : il ne peut pas être ajouté.',
];
