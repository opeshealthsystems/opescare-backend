<?php

/**
 * The facility service catalogue (/facility/catalogue) — "what we offer".
 *
 * Its own namespace so this screen could ship without touching an existing
 * translation file. EN and FR must stay strictly 1:1; scripts/i18n-audit.php
 * is a CI gate and checks the keys, the encoding and the :placeholders.
 *
 * DELIBERATELY ABSENT: the names of the codes themselves. "Hemoglobin
 * [Mass/volume] in Blood" is the published label of LOINC 718-7 in every
 * language, and a translation of it would no longer be that code's label. The
 * dictionary carries its own French titles where the publisher issues them;
 * where it does not, the published English is shown, which is correct rather
 * than merely English.
 */
return [

    'nav'           => 'Our services',
    'page_title'    => 'Our services',
    'page_subtitle' => 'The tests, procedures and services this facility offers, chosen from the institutional code dictionary.',
    'breadcrumb_home' => 'Facility',

    // Tabs
    'tab_active'  => 'Offered (:count)',
    'tab_retired' => 'Retired (:count)',

    // Filters
    'all_systems'  => 'All code systems',
    'filter_apply' => 'Filter',

    // Table
    'col_code'       => 'Code',
    'col_name'       => 'Service',
    'col_price'      => 'Price',
    'col_turnaround' => 'Turnaround',
    'col_actions'    => 'Actions',

    'price_none'      => 'Not published',
    'price_none_hint' => 'This facility has not published a price. That is not the same as free.',
    'unresolved_code' => 'This code is not in the loaded dictionary',
    'retired_on'      => 'Retired :date',

    'empty_active'  => 'Nothing on the list yet. Browse the dictionary to add the services this facility offers.',
    'empty_retired' => 'Nothing has been retired.',

    // Actions
    'action_browse'  => 'Add a service',
    'action_add'     => 'Add',
    'action_retire'  => 'Stop offering',
    'action_restore' => 'Offer again',
    'action_save'    => 'Save',
    'back_to_list'   => 'Back to our services',

    // Browse
    'browse_title'        => 'Add a service',
    'browse_subtitle'     => 'Search the institutional code dictionary and add what this facility performs.',
    'browse_placeholder'  => 'Search by code or name — e.g. malaria, 718-7, E11',
    'browse_search'       => 'Search',
    'browse_only_common'  => 'Common codes only',
    'browse_no_results'   => 'No code matches that search.',
    'browse_never_imported' => 'The code dictionary is empty on this deployment — no vocabulary has been imported yet. This is not a search that found nothing.',
    'browse_already'      => 'Already offered',
    'browse_already_retired' => 'Retired here',
    'browse_total'        => ':count codes in the dictionary',

    // Fields
    'field_local_name'      => 'What you call it',
    'field_local_name_hint' => 'Optional. The published name is always kept alongside it.',
    'field_price'           => 'Price (FCFA)',
    'field_price_hint'      => 'Optional, whole francs. Leave it empty if you do not publish a price — a price is information for patients and never a condition of care.',
    'field_turnaround'      => 'Turnaround',
    'field_turnaround_hint' => 'Optional — e.g. "Same day", "48h", "Tuesdays only".',
    'field_notes'           => 'Notes',

    // Flash
    'flash_added'        => 'Added to your services.',
    'flash_updated'      => 'Saved.',
    'flash_retired'      => 'No longer offered. The entry is kept, not deleted.',
    'flash_restored'     => 'Offered again.',
    'flash_unknown_code' => 'That code is not in the institutional dictionary, so it cannot be added.',

    // Publication to the public lab finder
    'unlinked_title' => 'Your public listing is not linked yet — lab tests on this list are not visible to patients',
    'unlinked_body'  => 'Laboratory tests and their prices are published to the public lab finder through your public directory listing. This account is not connected to one, so nothing on this list reaches a patient yet. Linking is done by an OpesCare administrator, never automatically: find and claim your facility in the directory.',
    // Diagnostic centre: one list, two public finders
    'mix_title'         => 'This list publishes to two public finders',
    'mix_lab'           => 'Laboratory tests: :catalogue on your list, :published published on the lab-test finder.',
    'mix_imaging'       => 'Imaging exams: :catalogue on your list, :published published on the imaging finder.',
    'mix_view_finder'   => 'View the finder',
    'mix_badge_lab'     => 'Lab-test finder',
    'mix_badge_imaging' => 'Imaging finder',
    'flash_stale_edit'  => 'Not saved: someone else at your facility changed this entry after you opened it. Check the new values and save again.',
];
