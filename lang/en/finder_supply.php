<?php

/*
|--------------------------------------------------------------------------
| Finder — supply-side screens (EN)
|--------------------------------------------------------------------------
| Copy for the screens facilities and pharmacies use to FEED the public
| finders: pharmacy stock reporting, blood-bank entry, the appointment
| desk, and the claim / listing-edit flow. Presentation-layer strings
| only — validation messages and flash messages live where they always
| have. Keep 1:1 with lang/fr/finder_supply.php.
*/

return [

    /* Pharmacy — public availability (stock reporting) */
    'cov_title'            => 'Visible to patients',
    'cov_hint'             => 'Only medicines you report here appear in the public Medicine Finder.',
    'row_reported'         => 'Reported',
    'quick_status_label'   => 'Set availability',

    /* Blood bank entry */
    'blood_group_pick'     => 'Tap the blood group',
    'blood_publish_note'   => 'Published to the public Blood Finder when you save.',
    'blood_screening_clear'=> 'Screening clear',
    'blood_units_unit'     => 'units',
    'stepper_minus'        => 'Fewer units',
    'stepper_plus'         => 'More units',

    /* Appointment desk */
    'appt_today'           => 'Today',
    'appt_today_empty'     => 'Nothing scheduled for today.',
    'appt_other'           => 'Other days',
    'btn_confirm'          => 'Confirm',
    'btn_no_show'          => 'No-show',
    'btn_cancel'           => 'Cancel',
    'call_patient'         => 'Call patient',
    'cancel_title'         => 'Cancel this appointment?',
    'cancel_reason_label'  => 'Reason for cancelling',
    'cancel_reason_ph'     => 'e.g. the provider is unavailable that day',
    'btn_keep'             => 'Keep appointment',
    'btn_cancel_confirm'   => 'Cancel appointment',

    /* Facility claim flow */
    'claim_how_title'      => 'How claiming works',
    'claim_step_submit'    => 'Tell us who you are',
    'claim_step_review'    => 'OpesCare reviews your claim',
    'claim_step_edit'      => 'You manage your listing',

    /* Listing edit */
    'phone_placeholder'    => 'Add your phone number — patients call this',
    'phone_missing_title'  => 'Patients cannot call you yet',
    'phone_missing_body'   => 'This listing has no working phone number on record. A dialable number is the first thing patients look for — add one below and save.',

];
