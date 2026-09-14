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
    'nav_invoices'    => 'Invoices',
    'nav_departments' => 'Departments',

    // ── Invoices (read only) ──────────────────────────────────────────────
    'action_open_invoices'  => 'See the invoices',
    'invoices_title'        => 'Invoices',
    'invoices_subtitle'     => 'Every invoice raised at this facility, newest first. This page is read-only: raising, paying, voiding and refunding stay with the billing desk.',
    'invoices_empty'        => 'No invoice has been raised at this facility yet.',
    'col_invoice_number'    => 'Number',
    'col_invoice_patient'   => 'Patient',
    'col_invoice_status'    => 'Status',
    'col_invoice_total'     => 'Patient share',
    'col_invoice_paid'      => 'Paid',
    'col_invoice_balance'   => 'Balance',
    'col_invoice_date'      => 'Raised',
    'invoice_patient_unknown' => 'Patient not on record',

    // ── Departments (read only) ───────────────────────────────────────────
    'action_open_departments' => 'See the departments',
    'departments_title'       => 'Departments',
    'departments_subtitle'    => 'This facility\'s active staff grouped by the department written on their staff record.',
    'departments_free_text'   => 'Departments are free text today: each name is grouped exactly as it was typed on the staff record, so two spellings are two rows. There is no department register yet.',
    'departments_empty'       => 'No active staff record at this facility carries a department yet.',
    'department_unassigned'   => 'No department recorded',
    'department_members'      => ':count staff',
    'col_department'          => 'Department',
    'col_department_count'    => 'Staff',
    'col_department_roles'    => 'Roles',
    'col_department_members'  => 'Members',

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

    // ── Reports & exports (H-6) ───────────────────────────────────────────
    'nav_reports'             => 'Reports',
    'action_open_reports'     => 'Reports and exports',
    'reports_title'           => 'Reports',
    'reports_subtitle'        => 'Per-day figures from this facility\'s own records over the chosen range. Counts, medians and whole-franc totals only — no patient appears in any table or export.',
    'reports_range_from'      => 'From',
    'reports_range_to'        => 'To',
    'reports_range_apply'     => 'Show',
    'reports_range_shown'     => 'Showing :from to :to (:days days).',
    'reports_range_order'     => 'The end date must not be before the start date.',
    'reports_range_too_long'  => 'A report may span at most :days days.',
    'reports_export_csv'      => 'Export CSV',
    'reports_empty'           => 'No rows in this range.',
    'reports_table_appointments' => 'Appointments by status per day',
    'reports_table_queue'        => 'Waiting room per day',
    'reports_table_lab'          => 'Laboratory per day',
    'reports_table_billing'      => 'Billing per day',
    'reports_table_staff'        => 'Staff headcount by role',
    'reports_queue_note'         => 'Only days with at least one check-in are listed. Median wait runs from check-in to the first call.',
    'reports_lab_note'           => 'Turnaround runs from order to result release, median over the panels released that day.',
    'reports_billing_note'       => 'Issued by the day the invoice was raised (patient share); collected by the day it was paid. Whole francs. Nothing here gates care on payment.',
    'reports_no_show_rate'       => 'No-show rate over the range',
    'reports_no_show_rate_value' => ':pct % (:no_show of :total booked)',
    'col_day'                    => 'Day',
    'col_total'                  => 'Total',
    'col_checked_in'             => 'Checked in',
    'col_seen'                   => 'Seen',
    'col_left'                   => 'Left / no-show',
    'col_median_wait'            => 'Median wait',
    'col_released'               => 'Released',
    'col_median_turnaround'      => 'Median turnaround',
    'col_issued'                 => 'Invoices issued',
    'col_issued_amount'          => 'Issued (FCFA)',
    'col_collected'              => 'Payments',
    'col_collected_amount'       => 'Collected (FCFA)',
    'col_headcount'              => 'Headcount',

    // ── Wards & beds (H-5) ────────────────────────────────────────────────
    'nav_wards'               => 'Wards & beds',
    'action_open_wards'       => 'Wards and beds',
    'wards_title'             => 'Wards & beds',
    'wards_subtitle'          => 'The wards of this facility and the beds in them. Occupancy is read from recorded admissions.',
    'wards_scope_note'        => 'This desk keeps the structure only: wards, beds, and whether a bed is in service. Admitting and discharging a patient are clinical acts recorded elsewhere; occupancy appears here once admissions are recorded. Beds are never deleted — they go out of service and come back.',
    'wards_empty'             => 'No ward on record yet. Create the first one below.',
    'wards_active_admissions' => 'Current admissions',
    'ward_create_title'       => 'Create a ward',
    'ward_name'               => 'Ward name',
    'ward_type'               => 'Ward type',
    'ward_capacity'           => 'Bed capacity',
    'ward_capacity_help'      => 'Planned capacity. It grows automatically when more beds are added.',
    'ward_create_submit'      => 'Create ward',
    'ward_types'              => [
        'general'   => 'General',
        'icu'       => 'ICU / critical care',
        'maternity' => 'Maternity',
        'pediatric' => 'Paediatric',
        'surgical'  => 'Surgical',
        'emergency' => 'Emergency',
        'isolation' => 'Isolation',
    ],
    'col_ward'                => 'Ward',
    'col_ward_type'           => 'Type',
    'col_ward_capacity'       => 'Capacity',
    'col_beds'                => 'Beds',
    'col_occupied'            => 'Occupied',
    'col_available'           => 'Available',
    'col_out_of_service'      => 'Out of service',
    'col_occupancy'           => 'Occupancy',
    'ward_inactive'           => 'Inactive',
    'beds_add_title'          => 'Add beds',
    'beds_add_count'          => 'Number of beds',
    'beds_add_prefix'         => 'Label prefix',
    'beds_add_prefix_help'    => 'Beds are labelled PREFIX-01, PREFIX-02… continuing after the labels already in the ward.',
    'beds_add_submit'         => 'Add beds',
    'beds_none_in_ward'       => 'No bed in this ward yet.',
    'bed_status_available'    => 'In service',
    'bed_status_occupied'     => 'Occupied',
    'bed_status_maintenance'  => 'Out of service',
    'bed_status_reserved'     => 'Reserved',
    'bed_set_out_of_service'  => 'Take out of service',
    'bed_set_in_service'      => 'Return to service',
    'ward_created'            => 'Ward :name created.',
    'beds_added'              => ':count bed(s) added to :ward.',
    'bed_out_of_service'      => 'Bed :bed is out of service.',
    'bed_back_in_service'     => 'Bed :bed is back in service.',
    'bed_status_unchanged'    => 'Bed :bed was not changed: it is occupied or already in that state.',

    "no_facility_context"    => "No facility context for this account. Select a facility first.",

];
