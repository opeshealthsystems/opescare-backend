<?php

/*
| doc_ex_cert: strings for the existing OpesCare Secure documents
| (docs/specs/documents/). EN and FR must stay 1:1.
*/

return [
    'nav' => 'Certificats',
    'desk_title' => 'Certificats et arrêts de travail',
    'desk_subtitle' => 'Délivrez un certificat médical ou un arrêt de travail à un patient que vous suivez.',
    'create_subtitle' => 'Le certificat est enregistré, numéroté et vérifiable par QR code. N’attestez que ce que vous avez constaté.',
    'new_certificate' => 'Nouveau certificat médical',
    'new_sick_note' => 'Nouvel arrêt de travail',
    'issued_list' => 'Délivrés par cet établissement',
    'empty' => 'Aucun certificat délivré',
    'empty_desc' => 'Les certificats délivrés apparaissent ici et dans les documents du patient.',
    'col_number' => 'Numéro',
    'col_type' => 'Type',
    'col_patient' => 'Patient',
    'col_issued' => 'Délivré le',
    'col_pdf' => 'Document',
    'download' => 'PDF',
    'back' => 'Retour',
    'cancel' => 'Annuler',
    'issue' => 'Délivrer et enregistrer',
    'find_patient' => 'Rechercher le patient',
    'health_id' => 'Health ID',
    'lookup' => 'Rechercher',
    'not_found' => 'Aucun patient ne porte ce Health ID.',
    'change_patient' => 'Changer de patient',
    'validity_days' => 'Validité (jours)',
    'statement_hint_cert' => 'Vos constatations et votre conclusion, ex. apte à l’usage indiqué, avec les éventuelles restrictions.',
    'statement_hint_sick' => 'N’écrivez pas le diagnostic : cet arrêt est remis à un employeur. Indiquez seulement que le repos est médicalement nécessaire.',
    'no_relationship' => 'Vous n’avez pas de relation de soins avec ce patient dans cet établissement.',
    'issued' => 'Certificat :number délivré.',
    'attestation' => 'Attestation',
    'certificate_body' => 'Je soussigné(e) :issuer certifie avoir examiné :holder le :date et atteste ce qui suit :',
    'sick_note_body' => 'Je soussigné(e) :issuer certifie que l’état de santé de :holder nécessite un arrêt de travail de :days jour(s), du :from au :to inclus.',
    'sick_note_privacy' => 'Secret médical : cet arrêt ne mentionne pas de diagnostic.',
    'title' => [
        'medical_certificate' => 'Certificat médical',
        'sick_note' => 'Arrêt de travail',
    ],
    'field' => [
        'examined_on' => 'Examiné le',
        'purpose' => 'Objet',
        'recipient' => 'Destinataire',
        'statement' => 'Constatations',
        'rest_from' => 'Repos du',
        'rest_to' => 'Repos jusqu’au',
        'rest_days' => 'Jours de repos',
    ],
    'purpose' => [
        'general_fitness' => 'Aptitude générale',
        'school' => 'Scolarité',
        'employment' => 'Emploi',
        'sport' => 'Sport',
        'travel' => 'Voyage',
        'administrative' => 'Dossier administratif',
        'other' => 'Autre',
    ],
];
