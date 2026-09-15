<?php

/*
|--------------------------------------------------------------------------
| Laboratory manager desk (web portal, /facility/lab/dashboard, /requests)
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/lab_manager.php (scripts/i18n-audit.php).
|
| `tat_none` is the honest string: when no released order in the window has
| both a collection and a release stamp there is no turnaround to print, and
| the page must say so rather than show 0.
*/

return [

    'nav_dashboard' => 'Laboratory dashboard',
    'nav_requests'  => 'Patient requests',

    // ── Dashboard ─────────────────────────────────────────────────────────
    'dashboard_title'    => 'Laboratory dashboard',
    'dashboard_subtitle' => 'This facility’s laboratory, right now. Every figure is read from the live worklist.',
    'open_worklist'      => 'Open the worklist',
    'open_collection'    => 'Sample collection',
    'open_requests'      => 'Patient requests',

    'kpi_awaiting_sample'      => 'Awaiting sample',
    'kpi_sample_in'            => 'Sample in, nothing entered',
    'kpi_in_progress'          => 'In progress',
    'kpi_awaiting_validation'  => 'Released, awaiting validation',
    'kpi_released_today'       => 'Released today',
    'kpi_validated_today'      => 'Validated today',
    'kpi_collected_today'      => 'Samples collected today',
    'kpi_rejected_today'       => 'Samples rejected today',
    'kpi_critical_open'        => 'Critical values not yet acknowledged',
    'kpi_requests_pending'     => 'Patient requests waiting',
    'kpi_hint_column'          => 'Worklist column',
    'kpi_hint_today'           => 'Since midnight',
    'kpi_hint_validated'       => 'Orders whose last change today was the validation signature',
    'kpi_hint_critical'        => 'Raised by the bench; closed here once the laboratory records the call-back',
    'kpi_hint_requests'        => 'Sent by patients from the app, waiting for a decision',

    'tat_title'        => 'Turnaround time',
    'tat_subtitle'     => 'Sample collected → results released, over the last :days days.',
    'tat_median'       => 'Median',
    'tat_mean'         => 'Mean',
    'tat_orders'       => 'Released orders measured',
    'tat_none'         => 'Not enough data: no order released in the last :days days carries both a collection and a release time.',
    'tat_minutes'      => ':minutes min',
    'tat_hours'        => ':hours h :minutes min',
    'tat_days'         => ':days d :hours h',

    'critical_title'    => 'Critical values awaiting acknowledgement',
    'critical_subtitle' => 'Oldest first. Telephone the value to the ordering clinician (or the patient, for a patient-requested test) and record the call-back; until then the value shows here.',
    'critical_empty'    => 'No critical value is waiting for acknowledgement.',
    'col_raised'        => 'Raised',
    'col_patient'       => 'Patient',
    'col_test'          => 'Test',
    'col_value'         => 'Value',
    'col_threshold'     => 'Threshold crossed',
    'col_open'          => 'Open',
    'open_order'        => 'Open order',

    // ── Patient requests ──────────────────────────────────────────────────
    'requests_title'    => 'Patient test requests',
    'requests_subtitle' => 'Tests the patients of this facility have asked it for. Approving raises a real order on the worklist; declining tells the patient why.',
    'requests_empty'    => 'No request in this column.',
    'request_status'    => [
        'submitted' => 'Waiting',
        'approved'  => 'Approved',
        'declined'  => 'Declined',
        'cancelled' => 'Withdrawn',
    ],
    'priority'          => [
        'stat'    => 'STAT',
        'urgent'  => 'Urgent',
        'routine' => 'Routine',
    ],
    'col_requested'     => 'Requested',
    'col_priority'      => 'Priority',
    'col_notes'         => 'Patient’s note',
    'col_decision'      => 'Decision',
    'col_actions'       => 'Actions',
    'approve'           => 'Approve',
    'approve_hint'      => 'Creates an order at this facility, ordered by you, at the urgency the patient asked for.',
    'decline'           => 'Decline',
    'decline_reason'    => 'Reason the patient will read',
    'submit_decline'    => 'Decline request',
    'decided_by'        => 'By :name on :at',
    'order_raised'      => 'Order raised',
    'request_approved'  => 'Request approved. The order is on the worklist and the patient has been told.',
    'request_declined'  => 'Request declined. The patient has been told why.',

    // ── Sample rejection ──────────────────────────────────────────────────
    'reject'            => 'Reject sample',
    'reject_hint'       => 'Final. The order closes as rejected, the reason is written on it, and the patient and the ordering clinician are told a new sample is needed. Only possible before any result is entered.',
    'reject_reason'     => 'Reason for rejection',
    'submit_reject'     => 'Reject this sample',
    'rejected'          => 'Sample rejected. The order is closed and the patient and clinician have been told.',
    'rejected_note'     => 'This sample was rejected: :reason',

    // ── Printed report ────────────────────────────────────────────────────
    'print_report'      => 'Print report',
    'print_report_hint' => 'The same OpesCare Secure sheet the patient downloads, generated now with a verification code.',

    // ── Bells ─────────────────────────────────────────────────────────────
    'notify' => [
        'rejected_patient_title'   => 'A new sample is needed for :test',
        'rejected_patient_body'    => ':facility could not use the sample collected. Reason: :reason. Please attend again for a new sample.',
        'rejected_clinician_title' => 'Sample rejected: :test',
        'rejected_clinician_body'  => ':facility rejected the sample for :test. Reason: :reason. A new sample is needed.',
    ],

    // ── First run ─────────────────────────────────────────────────────────
    'first_run_title'          => 'No order or request has reached this laboratory yet',
    'first_run_body'           => 'The figures below are zero because nothing has been sent here, not because the day is quiet. Work reaches a laboratory in three ways:',
    'first_run_step_catalogue' => 'Patients find you on the public lab-test finder once your tests and prices are in the catalogue and published (see below).',
    'first_run_step_requests'  => 'Patients send a test request from the app; it appears under Patient requests for you to approve or decline.',
    'first_run_step_walkin'    => 'Clinicians at this facility order tests from the clinician app; they land on the worklist.',

    // ── Public finder publication ─────────────────────────────────────────
    'pub_title'            => 'On the public lab-test finder',
    'pub_unlinked'         => 'This laboratory is not linked to a public directory listing, so none of its tests can appear on the finder. Claim your listing, or ask an OpesCare administrator to link it.',
    'pub_listing_inactive' => 'The listing “:listing” is not active, so the finder does not show it even though tests are published.',
    'pub_listing'          => 'Public listing',
    'pub_published'        => 'Tests offered on the finder',
    'pub_catalogue_tests'  => 'Active laboratory tests (LOINC) in the catalogue',
    'pub_last'             => 'Last published',
    'pub_never'            => 'Never',
    'pub_no_tests'         => 'The catalogue holds no active LOINC laboratory test, so there is nothing to publish.',
    'pub_open_catalogue'   => 'Open the catalogue',
    'pub_open_finder'      => 'View the public finder',

    // ── Critical value call-back ──────────────────────────────────────────
    'callback'                      => 'Record call-back',
    'callback_informed'             => 'Person informed (name and role)',
    'callback_informed_placeholder' => 'e.g. Dr Mbarga, ordering physician',
    'callback_note'                 => 'Note (optional)',
    'callback_hint'                 => 'Record this only once the value has been read back by the person named. The alert closes and cannot be reopened; the result is not changed.',
    'callback_submit'               => 'Close the alert',
    'callback_recorded'             => 'Call-back recorded. The alert is closed.',

    // ── Public lab-test finder page ───────────────────────────────────────
    'finder' => [
        'title'              => 'Find a lab test',
        'meta_description'   => 'Search the laboratory tests and prices that laboratories have published to the OpesCare network.',
        'badge'              => 'Laboratory tests',
        'intro'              => 'Search tests that laboratories have published, with their own prices. OpesCare does not set prices or check the bench — every result is the laboratory’s own publication.',
        'search_label'       => 'Which test do you need?',
        'search_placeholder' => 'e.g. Haemoglobin, Glucose, 718-7…',
        'results_count'      => '{1} :count laboratory has published this test.|[2,*] :count laboratories have published this test.',
        'published_line'     => 'Published by the laboratory · :time',
        'price_fcfa'         => ':price FCFA',
        'no_price'           => 'No price published',
        'turnaround'         => 'Turnaround: :time',
        'before_you_travel'  => 'Some tests need a clinician’s request. Call the laboratory to confirm requirements and price before travelling.',
        'empty_title'        => 'No laboratory has published this test yet.',
        'empty_body'         => 'That does not mean no laboratory performs it — only that none on the network has published it. Call a nearby laboratory directly.',
        'empty_cta'          => 'Find nearby laboratories',
    ],

    // ── Refusals ──────────────────────────────────────────────────────────
    'err' => [
        'request_not_found'  => 'That request was not sent to this laboratory.',
        'not_released'       => 'Only a released order has a report to print.',
        'reason_required'    => 'Give the reason.',
        'reject_released'    => 'This order has been released and cannot be rejected. Retract it through the clinical record instead.',
        'reject_has_results' => 'A result has already been entered on this order. It can be corrected, not rejected.',
        'alert_not_found'    => 'That critical value was not raised at this laboratory.',
        'informed_required'  => 'Name the person the value was read back to.',
        'callback_already'   => 'This alert is already closed.',
    ],

    // Public lab-test search API disclaimer (CareMapController::searchTests).
    'api_test_disclaimer' => 'Some tests may require a clinician\'s request. Confirm requirements with the lab before booking.',
];
