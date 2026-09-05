<?php

/*
|--------------------------------------------------------------------------
| Accès d'urgence (« bris de glace ») au dossier d'un patient
|--------------------------------------------------------------------------
|
| Messages renvoyés par MobileEmergencyAccessController (clinicien) et
| MobilePatientEmergencyAccessController (patient). Ils sont lus au chevet du
| patient, souvent sur un téléphone, parfois pendant une réanimation : chacun
| dit donc ce qui s'est passé ET ce qu'il faut faire ensuite. Un refus qui
| laisse un clinicien dans le flou est un refus que l'on contourne.
|
| Ce fichier doit rester 1:1 avec lang/en/emergency_access.php : mêmes clés,
| mêmes :placeholders (php scripts/i18n-audit.php).
|
*/

return [

    // ── Refus ────────────────────────────────────────────────────────────
    'actor_unresolved' => 'Votre session n’identifie pas un clinicien et un établissement. Reconnectez-vous depuis l’établissement où vous exercez.',
    'patient_not_found' => 'Aucun dossier ne correspond à cet identifiant santé. Vérifiez-le auprès du patient ou de son accompagnant, ou auprès de l’équipe qui l’adresse.',
    'patient_required' => 'Saisissez l’identifiant santé du patient. L’accès d’urgence ouvre un dossier nommé, jamais une liste.',

    // ── Le motif et la confirmation ──────────────────────────────────────
    'reason_required' => 'Indiquez pourquoi un accès d’urgence est nécessaire. Ce motif est enregistré et communiqué au patient.',
    'reason_too_short' => 'Décrivez l’urgence avec vos propres mots — au moins :min caractères. Ce motif est enregistré, communiqué au patient et examiné.',
    'acknowledgement_required' => 'L’accès d’urgence ne peut être ouvert qu’après avoir confirmé qu’il est journalisé, que le patient en est informé et qu’il fera l’objet d’un examen.',

    // ── La durée de vie de l’autorisation ────────────────────────────────
    'grant_not_found' => 'Cette autorisation d’accès d’urgence n’existe pas, ou elle n’a pas été ouverte par vous.',
    'grant_expired' => 'Cet accès d’urgence a expiré. Si les soins sont toujours en cours, ouvrez-en un nouveau en indiquant le motif actuel.',
    'grant_revoked' => 'Le patient a fermé cet accès d’urgence.',
    'grant_already_ended' => 'Cet accès d’urgence a déjà pris fin : il n’y a rien à fermer.',
    'already_revoked' => 'Vous avez déjà fermé cet accès d’urgence.',
    'revoked' => 'Accès d’urgence fermé. Le clinicien ne peut plus consulter votre dossier à ce titre, et la fermeture a été enregistrée.',

    // ── Limites et contrôles d’abus ──────────────────────────────────────
    'rate_limited' => 'Trop d’accès d’urgence en peu de temps. Celui-ci a été transmis à l’équipe de gouvernance de votre établissement — contactez-la ou le médecin de garde.',
    'revoked_by_patient' => 'Le patient a fermé un accès d’urgence antérieur sur ce dossier et celui-ci ne peut pas encore être rouvert. Escaladez auprès de l’équipe de gouvernance de votre établissement ou du médecin de garde.',

    // ── Infrastructure ───────────────────────────────────────────────────
    'audit_unavailable' => 'L’accès d’urgence n’a pas pu être enregistré : rien n’a donc été ouvert. Un accès qui ne laisse aucune trace n’est jamais accordé. Réessayez et, si le problème persiste, utilisez la procédure d’urgence papier.',

];
