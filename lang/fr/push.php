<?php

/*
|--------------------------------------------------------------------------
| Notifications push patient — textes affichés sur l'écran verrouillé
|--------------------------------------------------------------------------
|
| Chaque texte ici apparaît sur un écran verrouillé, une surface publique.
| Nommer l'établissement, la personne et le TYPE d'événement ; jamais un
| diagnostic, une valeur de résultat, un médicament ni l'issue d'un examen.
| Les clés sont groupées par data.type (config/push_channels.php). Résolues
| dans la langue du PATIENT avec __('push.x.y', [...], $locale) — voir
| App\Support\PatientPush.
|
*/

return [

    'consent_request' => [
        'title' => 'Demande d\'accès',
        'body'  => ':facility demande à consulter votre dossier de santé. Touchez pour approuver ou refuser.',
    ],

    'family_invitation' => [
        'title' => 'Invitation familiale',
        'body'  => ':guardian souhaite être rattaché(e) à votre dossier de santé. Touchez pour examiner.',
    ],

    'lab_result' => [
        'title'    => 'Résultat examiné',
        'body'     => 'Votre clinicien a examiné votre résultat :test. Ouvrez l\'application pour lire sa note.',
        'ready'    => 'Votre résultat :test est disponible dans votre dossier.',
    ],

    'appointment' => [
        'title' => 'Rappel de rendez-vous',
        'body'  => 'Vous avez un rendez-vous à :facility le :when.',
    ],

    'message' => [
        'title' => 'Nouveau message',
        'body'  => 'Vous avez un nouveau message de :sender.',
    ],

    'emergency_access' => [
        'title' => 'Accès d\'urgence à votre dossier',
        'body'  => ':facility a ouvert votre dossier de santé en accès d\'urgence. Touchez pour voir les détails.',
    ],

];
