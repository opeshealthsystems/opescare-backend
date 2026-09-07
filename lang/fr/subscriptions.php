<?php

/*
|--------------------------------------------------------------------------
| Abonnements — Français
|--------------------------------------------------------------------------
|
| Toutes les chaînes visibles par l'abonné que l'API d'abonnement peut
| renvoyer. Rien dans app/Services/Subscriptions ni dans
| MobileSubscriptionController ne code une phrase en dur : l'abonné lit ces
| messages au moment de décider s'il paie, ce qui est le pire moment pour
| retomber en anglais.
|
| DOIT rester 1:1 avec lang/en/subscriptions.php — mêmes clés, mêmes
| :placeholders. Vérifier avec `php scripts/i18n-audit.php`.
|
| La monnaie s'écrit toujours FCFA, jamais XAF, dans ce que lit un abonné ;
| les montants eux-mêmes sont formatés par
| App\Services\Payments\Domain\Money, qui sait que le XAF a un exposant
| ISO 4217 de 0 et ne divise donc jamais par quoi que ce soit.
|
*/

return [

    /*
     | Les trois cadences de vente possibles d'une offre.
     |
     | Ce n'est PAS ici qu'on décide laquelle est proposée à un abonné donné :
     | cela dépend des colonnes de prix que porte l'offre. Ici, seuls les mots.
     */
    'interval' => [
        'daily'   => 'Journalier',
        'monthly' => 'Mensuel',
        'annual'  => 'Annuel',
    ],

    // Ce qu'un abonnement dit de lui-même. « pending_payment » est l'état d'un
    // abonnement demandé mais non payé ; il n'ouvre aucun droit, et la
    // formulation doit le rendre évident.
    'status' => [
        'pending_payment' => 'En attente de paiement',
        'trialing'        => "Période d'essai",
        'active'          => 'Actif',
        'past_due'        => 'Paiement en retard',
        'paused'          => 'Suspendu',
        'cancelled'       => 'Annulé',
        'expired'         => 'Expiré',
    ],

    // Ce que l'abonné voit sur la demande envoyée au téléphone, et sur la
    // facture.
    'invoice_description' => 'Abonnement :plan (:interval)',

    // Sépare une liste d'intervalles dans une phrase : « mensuel, annuel ».
    'list_separator' => ', ',

    'errors' => [
        'interval_not_offered'    => "Cette offre n'est pas vendue en :interval. Disponible : :offered.",
        'plan_not_available'      => "Cette offre n'est pas disponible.",
        'plan_not_for_subscriber' => "Cette offre n'est pas vendue à votre type de compte.",
        'plan_not_priced'         => "Cette offre n'a pas de prix défini : elle ne peut pas encore être payée.",
        'already_subscribed'      => 'Vous avez déjà cet abonnement, et il est toujours en cours.',
        'subscriber_not_allowed'  => "Vous ne pouvez pas souscrire un abonnement pour quelqu'un d'autre.",
        'not_found'               => 'Abonnement introuvable.',
    ],

];
