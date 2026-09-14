<?php

/*
|--------------------------------------------------------------------------
| Facility administration desk (/facility/team/overview, /facility/team/members)
|--------------------------------------------------------------------------
| Must stay 1:1 with lang/fr/facility_admin.php (scripts/i18n-audit.php):
| same keys, same :placeholders.
|
| Every number on the overview is a count over this facility's own rows.
| The wording below never promises a figure the table cannot supply — an
| empty day says "none", not "0 minutes".
*/

return [

    'nav_overview'  => 'Facility overview',
    'nav_members'   => 'Team member',

    // ── Overview ──────────────────────────────────────────────────────────
    'overview_title'    => 'Facility overview',
    'overview_subtitle' => 'Today at :facility — every figure is measured from this facility\'s own records.',
    'overview_day'      => 'Figures for :date',
    'breadcrumb_home'   => 'My Facility',

    'section_appointments' => 'Appointments',
    'section_queue'        => 'Waiting room',
    'section_lab'          => 'Laboratory',
    'section_beds'         => 'Beds',
    'section_billing'      => 'Billing',
    'section_team'         => 'Team',

    'kpi_appointments_today'  => 'Booked today',
    'kpi_checked_in'          => 'Checked in',
    'kpi_no_show'             => 'No-shows',
    'kpi_cancelled'           => 'Cancelled',

    'kpi_queue_checked_in'    => 'Queue check-ins',
    'kpi_queue_waiting'       => 'Waiting now',
    'kpi_queue_seen'          => 'Seen',
    'kpi_median_wait'         => 'Median wait',
    'kpi_longest_wait'        => 'Longest wait',
    'kpi_no_measure'          => 'Not measured yet',

    'kpi_lab_open'            => 'Open orders',
    'kpi_lab_resulted_today'  => 'Released today',
    'kpi_lab_turnaround'      => 'Median turnaround',

    'kpi_wards'               => 'Active wards',
    'kpi_beds'                => 'Beds',
    'kpi_beds_occupied'       => 'Occupied',
    'kpi_beds_available'      => 'Available',
    'kpi_occupancy'           => 'Occupancy',
    'kpi_admissions'          => 'Current admissions',
    'beds_none'               => 'This facility has no wards on record, so no occupancy is shown.',

    'kpi_collected_today'     => 'Collected today',
    'kpi_outstanding'         => 'Outstanding balance',
    'kpi_issued_today'        => 'Invoices issued today',
    'billing_note'            => 'Totals from this facility\'s invoices. Nothing on this page gates care on payment.',

    'kpi_team_active'         => 'Active staff',
    'kpi_open_invites'        => 'Open invitations',
    'kpi_pending_joins'       => 'Join requests waiting',

    'action_open_queue'     => 'Open the queue',
    'action_open_team'      => 'Manage the team',
    'action_open_catalogue' => 'Service catalogue',

    'duration_minutes' => ':n min',
    'duration_hours'   => ':h h :m min',
    'amount_xaf'       => ':amount FCFA',

    // ── Team page column ─────────────────────────────────────────────────
    'col_manage'   => 'Manage',
    'action_open'  => 'Open',

    // ── Member page ───────────────────────────────────────────────────────
    'member_title'        => 'Team member',
    'member_subtitle'     => 'What :name holds at this facility, and what this desk may change.',
    'back_to_team'        => 'Back to the team',

    'section_identity'    => 'Account',
    'section_posting'     => 'Posting at this facility',
    'section_postings'    => 'All postings',
    'section_credentials' => 'Credentials',
    'section_profile'     => 'Staff record',
    'section_actions'     => 'Actions',

    'lbl_name'            => 'Name',
    'lbl_email'           => 'Email',
    'lbl_account_status'  => 'Account status',
    'lbl_role_here'       => 'Role at this facility',
    'lbl_posting_status'  => 'Posting',
    'lbl_since'           => 'Since',
    'lbl_home_facility'   => 'Home facility',
    'home_facility_here'  => 'This facility',
    'home_facility_other' => 'Another facility',

    'posting_active'      => 'Active',
    'posting_ended'       => 'Ended',
    'posting_none'        => 'No posting recorded here — this person is listed through their home facility only.',

    'col_facility'        => 'Facility',
    'col_role'            => 'Role',
    'col_status'          => 'Status',
    'col_assigned'        => 'Assigned',
    'postings_empty'      => 'No postings recorded.',

    'col_credential'      => 'Credential',
    'col_number'          => 'Number',
    'col_issuer'          => 'Issuing body',
    'col_expiry'          => 'Expires',
    'col_verified'        => 'Verified',
    'credential_verified'   => 'Verified',
    'credential_unverified' => 'Not verified',
    'credential_expired'    => 'Expired',
    'credentials_empty'     => 'No credentials recorded. The professional adds their own from their profile page.',

    'lbl_job_title'       => 'Job title',
    'lbl_department'      => 'Department',
    'lbl_employee_number' => 'Employee number',
    'lbl_employment_type' => 'Employment type',
    'lbl_phone'           => 'Phone',
    'profile_empty'       => 'No staff record yet. It is created the first time the professional opens their profile.',

    // ── Actions ───────────────────────────────────────────────────────────
    'change_role_title'   => 'Change role at this facility',
    'change_role_help'    => 'Only clinical and operational roles can be assigned here — the same list as the invitation form. The change takes effect on the next request.',
    'change_role_submit'  => 'Change role',
    'deactivate_title'    => 'End this posting',
    'deactivate_help'     => 'The person loses access to this facility. If this is their home facility and they work nowhere else on OpesCare, their account is suspended as well. Reversible from this page.',
    'deactivate_submit'   => 'Deactivate at this facility',
    'reactivate_title'    => 'Reopen this posting',
    'reactivate_help'     => 'Restores the role they last held here and lifts a suspension this desk applied.',
    'reactivate_submit'   => 'Reactivate',
    'not_manageable_self' => 'You cannot change your own role or posting.',
    'not_manageable_role' => 'This role is outside what a facility desk may assign, so it cannot be changed or ended here.',

    // ── Flash messages ────────────────────────────────────────────────────
    'role_changed'               => 'Role at this facility changed to :role.',
    'deactivated'                => 'Posting ended. The person no longer has access to this facility.',
    'deactivated_and_suspended'  => 'Posting ended and the account suspended — this was their only facility.',
    'reactivated'                => 'Posting reopened as :role.',
    'already_inactive'           => 'That posting is already ended.',
    'already_active'             => 'That posting is already active.',
    'member_not_manageable'      => 'This person\'s role cannot be changed from the facility desk.',

];
