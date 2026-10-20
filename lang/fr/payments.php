<?php

/*
|--------------------------------------------------------------------------
| Paiements Mobile Money — Français
|--------------------------------------------------------------------------
|
| Toutes les chaînes visibles par le patient que l'API de paiement peut
| renvoyer. Rien dans app/Services/Payments ni dans les contrôleurs de
| paiement ne code une phrase en dur : un patient lit ces messages au moment
| où de l'argent est en jeu, ce qui est le pire moment pour retomber en
| anglais.
|
| DOIT rester 1:1 avec lang/en/payments.php — mêmes clés, mêmes :placeholders.
| Vérifier avec `php scripts/i18n-audit.php`.
|
| La monnaie s'écrit toujours FCFA, jamais XAF, dans ce qu'un patient lit.
| XAF est le code ISO destiné aux réponses d'API ; FCFA est ce que lisent les
| Camerounais.
|
*/

return [

    // Ce qui s'affiche sur une facture ou un paiement.
    'status' => [
        'pending'  => 'En attente de paiement',
        'paid'     => 'Payée',
        'failed'   => 'Paiement échoué',
        'expired'  => 'Expirée',
        'refunded' => 'Remboursée',
    ],

    // Les deux réseaux Mobile Money. Noms de marque, non traduits — mais les
    // clés existent des deux côtés pour que rien n'affiche une valeur brute.
    'method' => [
        'mtn_momo'     => 'MTN Mobile Money',
        'orange_money' => 'Orange Money',
    ],

    /*
     | Pourquoi un paiement n'a pas abouti, en des termes exploitables.
     |
     | Ce sont NOS codes normalisés, jamais le message de l'opérateur. Les
     | messages d'erreur des télécoms sont en anglais uniquement, changent sans
     | préavis et contiennent parfois le numéro du payeur — rien de tout cela
     | n'a sa place sur l'écran d'un patient.
     */
    'failure' => [
        'insufficient_funds' => "Le solde de ce compte Mobile Money est insuffisant.",
        'rejected_by_payer'  => 'Le paiement a été refusé sur le téléphone.',
        'payer_not_found'    => "Ce numéro n'a pas de compte Mobile Money.",
        'payer_limit'        => 'Ce compte a atteint sa limite de transactions.',
        'timeout'            => "La demande de paiement a expiré avant d'être validée sur le téléphone.",
        'provider_error'     => "Le service Mobile Money n'a pas pu finaliser le paiement.",
        'unreachable'        => "Le service Mobile Money est injoignable. Si votre compte a été débité, cela sera confirmé sous peu — ne payez pas une seconde fois pour l'instant.",
    ],

    'errors' => [
        'invoice_not_found'        => 'Facture introuvable.',
        'charge_not_found'         => 'Paiement introuvable.',
        'invoice_not_payable'      => 'Cette facture ne peut pas être payée : elle est :status.',
        'invoice_expired'          => 'Cette facture a expiré et ne peut plus être payée.',
        'invoice_already_paid'     => 'Cette facture a déjà été payée.',
        'payment_in_progress'      => 'Un paiement est déjà en cours pour cette facture. Consultez la demande sur votre téléphone.',
        'idempotency_key_required' => "Un en-tête Idempotency-Key est requis pour démarrer un paiement.",
        'idempotency_conflict'     => "Cette clé Idempotency-Key a déjà servi pour un autre paiement.",
        'provider_unavailable'     => 'Le paiement en ligne n’est pas encore disponible. Veuillez payer à l’établissement.',
        'invalid_msisdn'           => 'Saisissez un numéro de téléphone camerounais valide.',
        'amount_mismatch'          => 'Le montant affiché a changé. Cette facture est de :expected.',
    ],

    // Affiché sur la demande envoyée au téléphone lorsqu'une facture n'a pas
    // de libellé propre.
    'default_description' => 'Paiement OpesCare',

];
