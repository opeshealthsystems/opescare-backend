<?php

/*
|--------------------------------------------------------------------------
| Console d'administration — passe de complétude (2026-10-18)
|--------------------------------------------------------------------------
|
| Chaînes ajoutées lorsque chaque liste admin a reçu une page de détail et
| chaque action destructrice une confirmation. Fichier propre, 1:1 avec
| lang/en/admin_desk.php, pour que `php scripts/i18n-audit.php` reste lisible.
|
*/

return [

    // ── Partagé par les pages de détail ──
    'back_to_list'     => 'Retour à la liste',
    'details'          => 'Détails',
    'reference'        => 'Référence',
    'created_at'       => 'Créé le',
    'reviewed_by'      => 'Examiné par',
    'reviewed_at'      => 'Examiné le',
    'name'             => 'Nom',
    'email'            => 'Courriel',
    'phone'            => 'Téléphone',
    'role'             => 'Rôle',
    'notes'            => 'Notes',

    // ── Confirmations des actions irréversibles ──
    'confirm_cancel_task'        => 'Annuler cette tâche ? Elle reste enregistrée comme annulée et ne peut pas être rouverte.',
    'confirm_reject_pair'        => 'Déclarer que ces deux dossiers sont des personnes différentes ? La paire quitte la file de revue.',
    'confirm_revoke_claim'       => 'Révoquer cette revendication ? L’établissement perd son lien avec la fiche jusqu’à l’approbation d’une nouvelle revendication.',
    'confirm_merge_import'       => 'Fusionner cet enregistrement importé dans la fiche existante ?',
    'confirm_suspend_facility'   => 'Suspendre cet établissement ? Son personnel perd l’accès au portail jusqu’à sa réactivation.',
    'confirm_suspend_user'       => 'Suspendre cet utilisateur ? Il est déconnecté et ne peut plus se connecter jusqu’à sa réactivation.',
    'confirm_close_breach'       => 'Clore ce dossier de violation ? La clôture est définitive.',
    'confirm_reject_mapping'     => 'Rejeter cette correspondance ? La paire de codes est marquée comme non équivalente.',
    'confirm_reject_credential'  => 'Rejeter cette qualification ? Le professionnel la verra comme suspendue, avec votre note.',

    // ── Tâches ──
    'task_detail'       => 'Tâche',
    'no_description'    => 'Aucune description n’a été fournie.',
    'acknowledged_at'   => 'Prise en compte le',
    'completed_at'      => 'Terminée le',
    'task_closed_hint'  => 'Cette tâche est close. Les tâches closes sont conservées et ne peuvent pas être rouvertes ; créez une nouvelle tâche si le travail reste à faire.',

    // ── Vérification des qualifications ──
    'credential_detail'        => 'Qualification',
    'verified'                 => 'Vérifiée',
    'awaiting_review'          => 'En attente de revue',
    'open_user'                => 'Ouvrir la fiche utilisateur',
    'issued_on'                => 'Délivrée le',
    'expires_on'               => 'Expire le',
    'no_expiry'                => 'Sans expiration',
    'document'                 => 'Document',
    'document_on_file'         => 'Au dossier',
    'document_none'            => 'Aucun document téléversé',
    'decision'                 => 'Décision',
    'verified_at'              => 'Décidée le',
    'credential_decided_hint'  => 'Cette qualification a déjà fait l’objet d’une décision. La décision, son auteur et sa date figurent ci-dessus.',

    // ── Prospects ──
    'lead_detail'        => 'Prospect',
    'lead_message'       => 'Message tel qu’envoyé',
    'lead_no_message'    => 'Le formulaire a été envoyé sans message.',
    'lead_notes'         => 'Notes et historique des statuts',
    'lead_no_notes'      => 'Aucune note pour l’instant. Chaque changement de statut accompagné d’une note est ajouté ici avec sa date.',
    'lead_tenant'        => 'Converti en',
    'lead_converted_at'  => 'Converti le',

    // ── Paires d’identité ──
    'mpi_pair_detail'    => 'Paire d’identité',
    'mpi_pair_subtitle'  => 'Les deux dossiers côte à côte, les indices pour et contre, et la décision prise — ou encore à prendre.',
    'mpi_open_pair'      => 'Ouvrir cette paire',

    // ── Incidents de sécurité ──
    'incident_detail'    => 'Incident',
    'opened_by'          => 'Ouvert par',

];
