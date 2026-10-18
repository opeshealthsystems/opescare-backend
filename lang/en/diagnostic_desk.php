<?php

/*
|--------------------------------------------------------------------------
| Diagnostic centre landing (web portal, /facility/diagnostic)
|--------------------------------------------------------------------------
| Lab + imaging under one roof. Must stay 1:1 with
| lang/fr/diagnostic_desk.php (scripts/i18n-audit.php).
*/

return [

    'nav'            => 'Centre dashboard',
    'title'          => 'Diagnostic centre',
    'subtitle'       => 'Laboratory and imaging, today, at :facility.',
    'type_diagnostic'=> 'Diagnostic centre',
    'type_imaging'   => 'Imaging centre',
    'not_a_centre'   => 'This facility is a laboratory without an imaging service, so its own dashboard is shown.',

    // ── Lab half ──────────────────────────────────────────────────────────
    'lab_title'            => 'Laboratory',
    'lab_open'             => 'Open orders',
    'lab_open_hint'        => 'Awaiting sample, sample in, or in progress',
    'lab_resulted_today'   => 'Resulted today',
    'lab_resulted_hint'    => 'Released since midnight',
    'lab_turnaround'       => 'Median turnaround',
    'lab_turnaround_hint'  => 'Collected → released, last :days days',
    'lab_turnaround_none'  => 'Not enough data',
    'lab_awaiting_validation' => 'Awaiting validation',
    'lab_critical_open'    => 'Critical values open',
    'lab_requests_pending' => 'Patient requests waiting',
    'lab_open_dashboard'   => 'Lab dashboard',
    'lab_open_worklist'    => 'Lab worklist',
    'lab_empty_title'      => 'No laboratory order yet',
    'lab_empty_body'       => 'Nothing has reached the laboratory. Work arrives when a clinician orders a test, a patient sends a request from the app, or a walk-in is registered at the collection desk.',
    'lab_empty_action'     => 'Open the collection desk',

    // ── Imaging half ──────────────────────────────────────────────────────
    'imaging_title'           => 'Imaging',
    'imaging_pending'         => 'Pending',
    'imaging_pending_hint'    => 'Requested, not yet scheduled',
    'imaging_scheduled'       => 'Scheduled',
    'imaging_in_progress'     => 'In progress',
    'imaging_today'           => 'Requested today',
    'imaging_today_hint'      => 'Orders taken since midnight',
    'imaging_awaiting_report' => 'Awaiting report',
    'imaging_awaiting_hint'   => 'Examination done, no signed report',
    'imaging_reported'        => 'Reported',
    'imaging_reported_hint'   => 'A signed report exists',
    'imaging_open_worklist'   => 'Imaging worklist',
    'imaging_open_new'        => 'New imaging request',
    'imaging_empty_title'     => 'No imaging request yet',
    'imaging_empty_body'      => 'No examination has been requested at this centre. Register the first walk-in or referral from the imaging desk; it appears on the worklist as soon as it is saved.',
    'imaging_empty_action'    => 'Register an imaging request',

    // ── Publication ───────────────────────────────────────────────────────
    'pub_title'            => 'On the public finders',
    'pub_subtitle'         => 'What patients can find from this centre, read from the published rows themselves.',
    'pub_unlinked'         => 'This centre is not linked to a public directory listing, so neither its tests nor its exams can appear on a finder. Claim your listing, or ask an OpesCare administrator to link it.',
    'pub_listing_inactive' => 'The listing “:listing” is not active, so the finders do not show it even though services are published.',
    'pub_listing'          => 'Public listing',
    'pub_lab'              => 'Lab-test finder',
    'pub_imaging'          => 'Imaging finder',
    'pub_published'        => 'Published',
    'pub_in_catalogue'     => 'In the catalogue',
    'pub_last'             => 'Last published',
    'pub_never'            => 'Never',
    'pub_lab_none'         => 'No active laboratory test (LOINC) in the catalogue. Add one and it is published at once.',
    'pub_imaging_none'     => 'No active imaging exam in the catalogue. Add a LOINC imaging term or an ICHI diagnostic-imaging procedure and it is published at once.',
    'pub_open_catalogue'   => 'Open the catalogue',
    'pub_open_lab_finder'  => 'View the lab-test finder',
    'pub_open_imaging_finder' => 'View the imaging finder',

    // ── Catalogue badge ───────────────────────────────────────────────────
    'badge_not_published'  => 'Not on a finder',
];
