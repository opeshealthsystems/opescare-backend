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
    'kpi_hint_critical'        => 'Raised by the bench; the ordering clinician acknowledges from their inbox',
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
    'critical_subtitle' => 'Oldest first. The clinician who ordered the test acknowledges from their results inbox; until then the value shows here.',
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

    // ── Refusals ──────────────────────────────────────────────────────────
    'err' => [
        'request_not_found'  => 'That request was not sent to this laboratory.',
        'not_released'       => 'Only a released order has a report to print.',
        'reason_required'    => 'Give the reason.',
        'reject_released'    => 'This order has been released and cannot be rejected. Retract it through the clinical record instead.',
        'reject_has_results' => 'A result has already been entered on this order. It can be corrected, not rejected.',
    ],
];
