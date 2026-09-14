<?php

/*
|--------------------------------------------------------------------------
| Liste des prix de la pharmacie — un prix de vente par médicament, en francs entiers
|--------------------------------------------------------------------------
| Doit rester 1:1 avec lang/en/pharmacy_prices.php (scripts/i18n-audit.php).
*/

return [

    'nav_prices'          => "Liste des prix",
    'no_facility_context' => "Aucune pharmacie active n'est sélectionnée pour cette session.",

    // ── Index ─────────────────────────────────────────────────────────────
    'index_title'         => "Liste des prix",
    'index_subtitle'      => "Le prix de vente de chaque médicament en rayon, en francs entiers. Enregistrer un prix le publie immédiatement dans le Chercheur de médicaments.",
    'index_empty'         => "Aucun article dans le registre pour l'instant. Enregistrez une réception de marchandises et le médicament apparaît ici.",
    'search_placeholder'  => "Rechercher un médicament par nom",
    'search_empty'        => "Aucun médicament ne correspond à cette recherche.",
    'btn_search'          => "Rechercher",
    'btn_clear'           => "Effacer",
    'btn_save'            => "Enregistrer",
    'btn_history'         => "Historique",
    'btn_back'            => "Retour à la liste des prix",
    'sum_items'           => "Articles en rayon",
    'sum_unpriced'        => "Sans prix de vente",
    'sum_unpriced_hint'   => "Publiés dans le chercheur sans prix tant qu'aucun n'est défini ici.",
    'whole_francs'        => "Francs entiers, sans décimales.",
    'whole_francs_hint'   => "Les prix sont en francs entiers (FCFA). Le XAF n'a pas de sous-unité : 1500 signifie mille cinq cents francs, jamais 15,00.",
    'no_listing'          => "Cette pharmacie n'a pas de fiche publique liée : les prix sont conservés ici mais pas encore montrés aux patients.",

    'col_medicine'        => "Médicament",
    'col_price'           => "Prix de vente",
    'col_published'       => "Montré aux patients",
    'col_last_changed'    => "Dernière modification",
    'col_history'         => "Historique",
    'lbl_reason'          => "Motif (facultatif)",

    'no_price_yet'        => "Pas encore de prix",
    'not_published'       => "Pas encore publié",
    'published_no_price'  => "Publié sans prix",
    'not_a_medicine'      => "Hors catalogue des médicaments",
    'differs'             => "Diffère de la liste des prix",
    'in_sync'             => "À jour",
    'actor_unknown'       => "Utilisateur inconnu",

    'flash_saved'         => "Prix enregistré pour :name et publié dans le chercheur.",
    'err_price'           => "Un prix de vente est un montant en francs entiers compris entre 0 et 99 999 999.",

    // ── Historique ────────────────────────────────────────────────────────
    'history_title'       => "Historique des prix",
    'history_subtitle'    => "Chaque modification du prix de vente de :name, la plus ancienne en bas.",
    'history_append_only' => "Une modification de prix est enregistrée, jamais modifiée ni supprimée. Un prix erroné se corrige en enregistrant un nouveau prix.",
    'history_empty'       => "Aucun prix n'a encore été défini pour cet article.",
    'sum_changes'         => "Modifications enregistrées",
    'col_when'            => "Quand",
    'col_old'             => "Ancien prix",
    'col_new'             => "Nouveau prix",
    'col_by'              => "Modifié par",

];
