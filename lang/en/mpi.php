<?php

/*
|--------------------------------------------------------------------------
| Master Patient Index — the identity review desk
|--------------------------------------------------------------------------
|
| Own file, same as caremap_claim.php and access_log.php: the EN/FR pair stays
| readable and `php scripts/i18n-audit.php` parity is obvious at a glance.
|
*/

return [
    'nav'      => 'Patient identity',
    'title'    => 'Patient identity',
    'subtitle' => 'Two records for one person are two half-histories. :count patients on the register.',

    'btn_detect' => 'Scan for duplicates now',

    'tab_pending'  => 'To review',
    'tab_merged'   => 'Merged',
    'tab_rejected' => 'Not the same person',

    'tier_all'           => 'All',
    'tier_deterministic' => 'Deterministic',
    'tier_probable'      => 'Probable',
    'tier_possible'      => 'Possible',

    'merge_warning' => 'A merge moves every record — prescriptions, appointments, visits, logins — from one Health ID onto the other, and turns the retired Health ID into an alias that still opens the surviving record. Nothing is deleted and every merge can be undone from the Merged tab. Nothing here merges on its own: a person chooses the survivor and writes down why.',

    'detected_scan'                 => 'Found by scan',
    'detected_identifier_collision' => 'Same identifier linked to both',
    'detected_manual'               => 'Raised by hand',
    'reviewed_by'                   => 'reviewed by :name',

    // Evidence chips — positive
    'reason_name_match'          => 'Same name',
    'reason_phone_match'         => 'Same phone',
    'reason_dob_match'           => 'Same date of birth',
    'reason_dob_match_estimated' => 'Same date of birth (estimated)',
    'reason_sex_match'           => 'Same sex',
    'reason_national_id_match'   => 'Same national ID',
    'reason_identifier_match'    => 'Shared identifier',
    'reason_identifier_collision' => 'Identifier held by both',
    // Evidence chips — negative
    'reason_phone_differs'       => 'Phones differ',
    'reason_dob_differs'         => 'Dates of birth differ',
    'reason_sex_differs'         => 'Sexes differ',
    'reason_national_id_differs' => 'National IDs differ',

    'col_record'     => 'Record',
    'col_survivor'   => 'Kept',
    'col_retired'    => 'Retired',
    'record_missing' => 'This record no longer exists.',

    'f_health_id'      => 'Health ID',
    'f_name'           => 'Name',
    'f_dob'            => 'Date of birth',
    'f_estimated'      => 'estimated',
    'f_sex'            => 'Sex',
    'f_phone'          => 'Phone',
    'f_national_id'    => 'National ID',
    'f_logins'         => 'Logins',
    'f_history'        => 'History',
    'f_history_counts' => ':rx prescriptions · :appt appointments · :enc visits',
    'f_created'        => 'Registered',
    'f_status'         => 'Status',
    'f_on_file'        => 'on file',
    'f_missing'        => 'not recorded',
    'f_none'           => 'none',

    'sex_male'   => 'Male',
    'sex_female' => 'Female',

    'survivor_label'     => 'Which record survives?',
    'survivor_hint'      => ':n clinical records',
    'reason_label'       => 'Why are these the same person?',
    'reason_placeholder' => 'What you checked — the card, the phone number, the patient in front of you.',
    'btn_merge'          => 'Merge into the chosen record',
    'confirm_merge'      => 'Merge these two records? Every record on the other Health ID will move to the one you chose. This can be undone.',
    'reject_placeholder' => 'Why not (optional)',
    'btn_reject'         => 'Not the same person',

    'merge_summary'         => ':moved rows moved, :kept kept on the retired record, :filled fields filled on the survivor.',
    'btn_unmerge'           => 'Undo this merge',
    'unmerge_reason_label'  => 'Why was this merge wrong?',
    'btn_unmerge_confirm'   => 'Put the records back',
    'confirm_unmerge'       => 'Undo this merge? Every row it moved goes back to the retired Health ID, which becomes a live record again.',

    'empty_pending'  => 'Nothing waiting. Run a scan, or come back after tonight’s.',
    'empty_merged'   => 'No merges yet.',
    'empty_rejected' => 'No pairs have been declared different people yet.',

    'flash_detected' => 'Scanned :scanned patients; :created pair(s) queued for review.',
    'flash_merged'   => 'Merged. :moved rows moved to the surviving record; :kept kept on the retired one.',
    'flash_rejected' => 'Recorded as two different people.',
    'flash_unmerged' => 'Merge undone. Both records are live again.',

    'error_not_pending'          => 'That pair has already been decided.',
    'error_survivor_not_in_pair' => 'The survivor must be one of the two records in the pair.',
    'error_already_merged'       => 'One of these records has already been merged into another.',
    'error_merge_not_active'     => 'That merge has already been undone.',
    'error_same_patient'         => 'A record cannot be merged with itself.',
    'error_generic'              => 'Something went wrong and nothing was changed.',
];
