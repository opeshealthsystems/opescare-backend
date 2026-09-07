<?php

/*
|--------------------------------------------------------------------------
| Tarification des abonnements — l'écran qui fixe le prix de la plateforme
|--------------------------------------------------------------------------
|
| Fichier dédié, comme mpi.php et caremap_claim.php : la paire EN/FR reste
| lisible et la parité vérifiée par `php scripts/i18n-audit.php` saute aux yeux.
|
*/

return [
    'nav'      => 'Offres et tarifs',
    'title'    => 'Offres et tarifs',
    'subtitle' => 'Ce que coûte OpesCare. :count offres au catalogue.',

    // Le point à ne surtout pas se tromper.
    'francs_note' => 'Chaque montant ici est un nombre entier de francs. Le XAF n\'a pas de centime : 50 signifie cinquante francs, jamais cinquante centièmes.',

    'col_plan'        => 'Offre',
    'col_audience'    => 'Vendue à',
    'col_daily'       => 'Par jour',
    'col_monthly'     => 'Par mois',
    'col_annual'      => 'Par an',
    'col_status'      => 'Statut',
    'col_last_change' => 'Dernière modification',
    'col_actions'     => 'Actions',

    'interval_daily'   => 'journalier',
    'interval_monthly' => 'mensuel',
    'interval_annual'  => 'annuel',

    'default_interval' => 'facturée en :interval par défaut',

    'audience_patient'   => 'Patients',
    'audience_household' => 'Foyers',
    'audience_facility'  => 'Établissements',
    'audience_insurer'   => 'Assureurs',
    'audience_lab'       => 'Laboratoires',
    'audience_pharmacy'  => 'Pharmacies',
    'audience_healthorg' => 'Organisations de santé',
    'audience_developer' => 'Développeurs',

    'not_sold'      => 'Non vendue ainsi',
    'not_sold_hint' => 'Cette offre n\'est pas facturée sur cet intervalle. Cet écran fixe les prix ; il ne change pas les intervalles sur lesquels une offre est vendue.',
    'blank_means'   => 'Laissez vide pour ne plus vendre cette offre sur cet intervalle.',

    'badge_active'   => 'Active',
    'badge_inactive' => 'Inactive',

    'btn_edit'       => 'Modifier les prix',
    'btn_save'       => 'Enregistrer les prix',
    'btn_activate'   => 'Activer',
    'btn_deactivate' => 'Désactiver',
    'btn_audit'      => 'Historique complet des modifications',

    'last_change_never' => 'Jamais modifiée ici',
    'last_change_by'    => ':name · :when',
    'actor_unknown'     => 'Administrateur inconnu',

    'empty_heading' => 'Aucune offre au catalogue',
    'empty_body'    => 'Rien n\'est encore tarifé. Alimentez le catalogue des frais de plateforme avant de fixer un prix ici.',

    'flash_prices_updated' => 'Tarifs mis à jour pour :plan.',
    'flash_prices_same'    => 'Rien n\'a changé pour :plan — les prix soumis étaient déjà ceux enregistrés.',
    'flash_activated'      => ':plan est active et peut être souscrite.',
    'flash_deactivated'    => ':plan est inactive et ne peut plus être souscrite.',

    'err_whole_francs'       => 'Le prix :interval de :plan doit être un nombre entier de francs — sans décimale ni séparateur. Laissez le champ vide pour ne plus vendre cet intervalle.',
    'err_zero_price'         => 'Un prix :interval de 0 ne peut pas être enregistré pour :plan. Money — la classe qui facture réellement — n\'accepte qu\'un montant strictement positif : un prix de zéro franc casserait tous les écrans qui lisent cette offre. Laissez le champ vide pour ne plus vendre cet intervalle, ou désactivez l\'offre.',
    'err_too_large'          => 'Le prix :interval de :plan dépasse le maximum qu\'un paiement peut porter (:max).',
    'err_interval_not_offered' => ':plan n\'est pas vendue en :interval : elle ne peut donc pas recevoir un prix :interval ici.',
    'err_last_interval'      => ':plan doit conserver au moins un prix. Désactivez l\'offre plutôt que d\'effacer tous les intervalles.',
];
