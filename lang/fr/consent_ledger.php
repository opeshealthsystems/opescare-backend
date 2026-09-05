<?php

/*
|--------------------------------------------------------------------------
| Registre des consentements — « qui peut voir mon dossier en ce moment ? »
|--------------------------------------------------------------------------
|
| Toutes les chaînes que MobileConsentLedgerController envoie au client. Le
| contrôleur respecte ?lang= et l'en-tête Accept-Language : un patient
| francophone lit sa propre liste de consentements en français.
|
| lang/en/consent_ledger.php et lang/fr/consent_ledger.php doivent rester
| strictement 1:1 — mêmes clés, UTF-8 valide, mêmes :placeholders des deux
| côtés. Vérification : php scripts/i18n-audit.php
|
| Les phrases ci-dessous sont les phrases honnêtes. Deux d'entre elles
| portent l'essentiel :
|
|   state_detail.expired      — une autorisation peut rester marquée « active »
|                               en base longtemps après son échéance, car rien
|                               ne met la colonne à jour. On dit au patient
|                               qu'elle a pris fin.
|   relationship_note         — retirer toutes les autorisations ne coupe PAS
|                               l'accès d'un clinicien qui vous soigne. Le taire
|                               laisserait le patient croire qu'il a fermé une
|                               porte restée ouverte.
|
*/

return [

    // ── L'état d'une autorisation ────────────────────────────────────────

    'state' => [
        'active'  => 'Active',
        'expired' => 'Expirée',
        'revoked' => 'Retirée',
        'ended'   => 'Terminée',
    ],

    'state_detail' => [
        'active'  => 'Cet accès est actif en ce moment. Cet établissement peut ouvrir votre dossier jusqu\'à son expiration, ou jusqu\'à ce que vous le retiriez.',
        'expired' => 'Cet accès est arrivé à échéance. Il a pris fin de lui-même à la date indiquée et personne ne peut s\'en servir pour ouvrir votre dossier.',
        'revoked' => 'Vous avez retiré cet accès. Il a pris fin à la date indiquée et ne peut plus être utilisé.',
        'ended'   => 'Cet accès n\'est plus actif. Il est conservé ici pour que vous puissiez voir qu\'il a existé.',
    ],

    // ── Sur quelle autorité l'autorisation existe ────────────────────────

    'basis' => [
        'patient'         => 'Vous avez accordé cet accès',
        'guardian'        => 'Votre tuteur a accordé cet accès',
        'facility_policy' => 'Accordé au titre de la politique de l\'établissement',
        'unknown'         => 'Autorité non enregistrée',
    ],

    'basis_detail' => [
        'patient'         => 'Vous avez vous-même approuvé cet accès.',
        'guardian'        => 'La personne légalement responsable de vous a approuvé cet accès en votre nom.',
        'facility_policy' => 'Vous n\'avez pas approuvé celui-ci. Il a été créé au titre de la politique propre à l\'établissement et non par votre décision — demandez-leur pourquoi il existe si vous ne vous y attendiez pas.',
        'unknown'         => 'Le dossier n\'indique pas qui a autorisé cet accès. C\'est une lacune du dossier, et non une décision de votre part.',
    ],

    'source' => [
        'consent_request' => 'Issu d\'une demande qu\'on vous a soumise pour approbation',
        'direct'          => 'Créé directement, sans qu\'aucune demande ne vous soit envoyée',
    ],

    // ── Retirer un accès ─────────────────────────────────────────────────

    'revoke_available' => 'Vous pouvez retirer cet accès maintenant. Il cesse immédiatement.',

    'revoke_unavailable' => [
        'not_active'     => 'Cet accès a déjà pris fin : il n\'y a plus rien à retirer.',
        'no_linked_user' => 'Cet accès ne peut pas encore être retiré depuis l\'application, car votre dossier patient n\'est lié à aucun compte de connexion. Demandez à votre établissement de les relier, ou demandez-leur de le retirer pour vous.',
    ],

    // ── Un accès qui n'est PAS une autorisation de consentement ──────────

    'relationship' => [
        'appointment'        => 'Vous avez un rendez-vous avec ce clinicien',
        'care_team'          => 'Ce clinicien fait partie de votre équipe soignante',
        'treating_clinician' => 'Ce clinicien vous a soigné',
        'facility_episode'   => 'Vous êtes pris en charge dans cet établissement',
    ],

    'relationship_detail' => [
        'appointment'        => 'Un clinicien que vous devez consulter peut ouvrir votre dossier pour préparer et assurer ce rendez-vous.',
        'care_team'          => 'Un clinicien enregistré comme membre actuel de votre équipe soignante peut ouvrir votre dossier tant qu\'il en fait partie.',
        'treating_clinician' => 'Un clinicien qui vous a personnellement soigné — qui vous a reçu, vous a prescrit un traitement, ou a demandé une analyse ou un examen pour vous — peut ouvrir le dossier des soins qu\'il vous a donnés.',
        'facility_episode'   => 'Tant que vous avez un épisode de soins ouvert ou récent dans un établissement, les cliniciens qui y travaillent peuvent ouvrir votre dossier. Celui-ci ne désigne aucune personne en particulier.',
    ],

    'relationship_note' => 'Ce ne sont pas des autorisations de consentement, et retirer un consentement n\'y met pas fin. Un clinicien qui vous soigne peut ouvrir votre dossier en raison de ces soins eux-mêmes. Pour mettre fin à l\'un d\'eux, il faut que les soins prennent fin : annulez le rendez-vous, demandez à être retiré de l\'équipe soignante, ou parlez-en à l\'établissement. Chaque fois que quelqu\'un ouvre votre dossier, quelle qu\'en soit la raison, cela est inscrit dans votre journal d\'accès.',

];
