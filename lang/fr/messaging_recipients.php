<?php

/*
|--------------------------------------------------------------------------
| Messagerie patient — à qui un patient peut écrire
|--------------------------------------------------------------------------
|
| Toutes les chaînes affichées par /api/mobile/messages/recipients et par les
| trois chemins de POST /api/mobile/messages/threads (rendez-vous / structure /
| clinicien). À garder 1:1 avec lang/en/messaging_recipients.php —
| `php scripts/i18n-audit.php` est un contrôle CI.
|
| Règle de ton pour les refus : dire ce qui est vrai et ce que le patient peut
| faire à la place. Aucun ne doit se lire comme « vous avez fait une erreur ».
|
*/

return [

    // ── L'avertissement permanent ───────────────────────────────────────
    'not_emergency'       => "Ce canal n'est pas destiné aux urgences. Les messages sont lus pendant les heures d'ouverture. En cas d'urgence, appelez la structure ou rendez-vous sur place.",

    // ── Types de destinataires ──────────────────────────────────────────
    'facility_desk'       => "Accueil",
    'facility_desk_hint'  => "Heures d'ouverture, aide au rendez-vous, résultats, disponibilité d'un médicament.",
    'clinician_hint'      => "Uniquement les cliniciens qui vous suivent déjà.",

    // ── Pourquoi ce clinicien est joignable — la base de la relation ────
    'basis' => [
        'appointment'        => "Vous avez un rendez-vous avec ce clinicien",
        'care_team'          => "Membre de votre équipe soignante",
        'treating_clinician' => "Vous a déjà soigné",
        'facility_episode'   => "Intervient dans vos soins dans cette structure",
        'consent_grant'      => "Vous avez accordé à cette structure l'accès à votre dossier",
    ],

    // Les mêmes cinq idées appliquées à un LIEU, pas à une personne.
    'facility_basis' => [
        'appointment'      => "Vous avez un rendez-vous ici",
        'facility_episode' => "Vous y avez déjà été soigné",
        'consent_grant'    => "Vous avez accordé à cette structure l'accès à votre dossier",
    ],

    // ── Refus ───────────────────────────────────────────────────────────
    'appointment_not_found'   => "Rendez-vous introuvable.",
    'appointment_no_provider' => "Aucun praticien n'est encore affecté à ce rendez-vous.",

    'recipient_required'      => "Choisissez à qui écrire : une structure, un clinicien qui vous suit déjà, ou l'un de vos rendez-vous.",
    'recipient_ambiguous'     => "Choisissez un seul destinataire — une structure, un clinicien ou un rendez-vous, pas plusieurs.",

    'facility_not_found'      => "Structure introuvable.",
    'facility_unreachable'    => ":facility n'a pas encore de service de messagerie ; un message n'y atteindrait personne. Appelez-la plutôt.",

    'clinician_not_found'     => "Clinicien introuvable.",
    'no_relationship'         => "Vous ne pouvez écrire qu'à un clinicien qui vous suit déjà. Écrivez plutôt à la structure, ou prenez rendez-vous.",
    'clinician_unreachable'   => ":clinician ne peut pas encore recevoir de messages directement. Écrivez plutôt à :facility — l'accueil transmettra.",

    'thread_not_created'      => "La conversation n'a pas pu être ouverte. Réessayez dans un instant.",

];
