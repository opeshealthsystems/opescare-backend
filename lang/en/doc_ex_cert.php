<?php

/*
| doc_ex_cert: strings for the existing OpesCare Secure documents
| (docs/specs/documents/). EN and FR must stay 1:1.
*/

return [
    'nav' => 'Certificates',
    'desk_title' => 'Certificates and sick notes',
    'desk_subtitle' => 'Issue a medical certificate or a sick note for a patient you are treating.',
    'create_subtitle' => 'The certificate is registered, numbered and verifiable by QR code. Only state what you have examined.',
    'new_certificate' => 'New medical certificate',
    'new_sick_note' => 'New sick note',
    'issued_list' => 'Issued by this facility',
    'empty' => 'No certificate issued yet',
    'empty_desc' => 'Certificates you issue appear here and in the patient’s documents.',
    'col_number' => 'Number',
    'col_type' => 'Type',
    'col_patient' => 'Patient',
    'col_issued' => 'Issued',
    'col_pdf' => 'Document',
    'download' => 'PDF',
    'back' => 'Back',
    'cancel' => 'Cancel',
    'issue' => 'Issue and register',
    'find_patient' => 'Find the patient',
    'health_id' => 'Health ID',
    'lookup' => 'Look up',
    'not_found' => 'No patient has this Health ID.',
    'change_patient' => 'Change patient',
    'validity_days' => 'Valid for (days)',
    'statement_hint_cert' => 'Your findings and conclusion, e.g. fit for the stated purpose, with any restriction.',
    'statement_hint_sick' => 'Do not write the diagnosis: this note goes to an employer. State only that rest is medically required.',
    'no_relationship' => 'You have no clinical relationship with this patient at this facility.',
    'issued' => 'Certificate :number issued.',
    'attestation' => 'Attestation',
    'certificate_body' => 'I, the undersigned :issuer, certify that I examined :holder on :date and record the following:',
    'sick_note_body' => 'I, the undersigned :issuer, certify that the condition of :holder requires rest from work for :days day(s), from :from to :to inclusive.',
    'sick_note_privacy' => 'Medical confidentiality: this note does not disclose a diagnosis.',
    'title' => [
        'medical_certificate' => 'Medical certificate',
        'sick_note' => 'Sick note (work stoppage)',
    ],
    'field' => [
        'examined_on' => 'Examined on',
        'purpose' => 'Purpose',
        'recipient' => 'Addressed to',
        'statement' => 'Statement',
        'rest_from' => 'Rest from',
        'rest_to' => 'Rest until',
        'rest_days' => 'Days of rest',
    ],
    'purpose' => [
        'general_fitness' => 'General fitness',
        'school' => 'School',
        'employment' => 'Employment',
        'sport' => 'Sport',
        'travel' => 'Travel',
        'administrative' => 'Administrative file',
        'other' => 'Other',
    ],
];
