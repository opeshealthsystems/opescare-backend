<?php

/*
|--------------------------------------------------------------------------
| Clinician Results Inbox — API messages
|--------------------------------------------------------------------------
|
| Every string the mobile results inbox can receive from the server. Keep this
| file 1:1 with lang/fr/doctor_results.php — enforced by
| `php scripts/i18n-audit.php`, which checks keys, UTF-8 encoding AND
| :placeholder parity.
|
| Wording rule for this file: never claim more than the record says. "The
| laboratory did not record a flag" and "the laboratory recorded a normal
| result" are different sentences and must stay different.
|
*/

return [

    // Errors. "Not found" and "not yours" are deliberately the same answer:
    // telling a clinician a result exists but belongs to another facility
    // confirms the record's existence to someone with no right to it.
    'not_found'          => 'That result is not in your inbox.',
    'order_not_found'    => 'That order is not in your inbox.',
    'acknowledge_failed' => 'The acknowledgement could not be recorded. Please try again, or acknowledge it on a workstation.',

    // Row categories.
    'category'           => [
        'lab_result' => 'Lab result',
        'radiology'  => 'Imaging report',
    ],

    'radiology_report'   => 'Imaging report',

    // Imaging modality, as radiology_reports records it. Never the raw enum.
    'modality'           => [
        'xray'       => 'X-ray',
        'ct'         => 'CT',
        'mri'        => 'MRI',
        'ultrasound' => 'Ultrasound',
        'echo'       => 'Echocardiogram',
        'nuclear'    => 'Nuclear medicine',
        'pet'        => 'PET',
        'other'      => 'Imaging',
    ],

    // The one-line summary under a row title. Counted forms, so a single
    // analyte never reads "1 values".
    'body'               => [
        'no_values' => 'The laboratory has released this order without individual values.',
        // Counted on :abnormal, not :total — French inflects the flagged-value
        // noun and its participle on the number that is flagged, and one
        // trans_choice call carries one count, so both locales use that one.
        'abnormal'  => '{1} :abnormal value out of :total flagged by the laboratory.|[2,*] :abnormal values out of :total flagged by the laboratory.',
        'normal'    => '{1} :total value, recorded as normal by the laboratory.|[2,*] :total values, all recorded as normal by the laboratory.',
        'unflagged' => '{1} :total value released. The laboratory recorded no flag.|[2,*] :total values released. The laboratory recorded no flags.',
    ],

];
