<?php

/**
 * Facility team management — inviting staff into your own facility.
 *
 * EN and FR must stay 1:1 (enforced by scripts/i18n-audit.php).
 */
return [
    // Page chrome
    'page_title'     => 'My Team',
    'page_subtitle'  => 'Invite clinicians and staff to work at this facility.',
    'breadcrumb_home' => 'My Facility',

    // Invite form
    'invite_form_title'      => 'Invite a staff member',
    'invite_form_help'       => 'The invitation is issued for this facility only, and the role list is limited to clinical and operational roles.',
    'field_email'            => 'Work email',
    'field_name'             => 'Full name (optional)',
    'field_role'             => 'Role',
    'field_role_placeholder' => 'Select a role',
    'invite_submit'          => 'Create invitation',

    // Invite link handover (no SMTP in production — the link is shown here)
    'invite_link_title' => 'Invitation link',
    'invite_link_help'  => 'Copy this link and send it to :email yourself — invitations are not emailed.',

    // Invite list
    'invites_title'   => 'Pending invitations',
    'invites_empty'   => 'No invitations have been issued for this facility yet.',
    'col_email'       => 'Email',
    'col_name'        => 'Name',
    'col_role'        => 'Role',
    'col_status'      => 'Status',
    'col_expires'     => 'Expires',
    'col_invited_by'  => 'Invited by',
    'col_actions'     => 'Actions',
    'action_reissue'  => 'New link',
    'action_revoke'   => 'Revoke',

    'status_pending'  => 'Awaiting acceptance',
    'status_used'     => 'Accepted',
    'status_revoked'  => 'Revoked',
    'status_expired'  => 'Expired',

    // Staff list
    'staff_title' => 'Staff at this facility',
    'staff_empty' => 'No staff accounts are linked to this facility yet.',

    'user_status_active'    => 'Active',
    'user_status_suspended' => 'Suspended',
    'user_status_pending'   => 'Pending',

    // Flash messages
    'invite_created'       => 'Invitation created. Copy the link below and send it to the invitee.',
    'invite_reissued'      => 'A new invitation link has been generated. The previous link no longer works.',
    'invite_revoked'       => 'Invitation revoked.',
    'invite_already_used'  => 'That invitation has already been accepted and can no longer be changed.',
    'invite_already_open'  => 'An invitation for that email address is already open at this facility.',
    'email_taken'          => 'An account with that email address already exists.',
    'role_unknown'         => 'That role is not available at this facility.',
    'accept_failed'        => 'The account could not be created. Please ask your administrator for a new invitation link.',

    // Public invitation page
    'invite_email_lbl'   => 'Invited email address',
    'invite_error_help'  => 'If you believe this is a mistake, ask your facility administrator to issue a new OpesCare invitation.',
    'password_hint'      => 'At least 8 characters',

    /*
     * The roles a facility administrator may issue, as the invitee will see
     * them. This list mirrors FacilityStaffInvite::INVITABLE_ROLES exactly —
     * if a role is added there it needs a label here and in lang/fr/team.php.
     */
    'roles' => [
        'doctor'              => 'Doctor',
        'specialist'          => 'Specialist doctor',
        'consultant'          => 'Consultant',
        'resident'            => 'Resident doctor',
        'visiting_doctor'     => 'Visiting doctor',
        'nurse'               => 'Nurse',
        'triage_nurse'        => 'Triage nurse',
        'ward_nurse'          => 'Ward nurse',
        'midwife'             => 'Midwife',
        'nurse_supervisor'    => 'Nurse supervisor',
        'receptionist'        => 'Receptionist',
        'front_desk'          => 'Front desk officer',
        'records_officer'     => 'Records officer',
        'labtech'             => 'Lab technician',
        'lab_scientist'       => 'Lab scientist',
        'lab_manager'         => 'Lab manager',
        'sample_collection'   => 'Sample collection officer',
        'pharmacist'          => 'Pharmacist',
        'pharmacy_technician' => 'Pharmacy technician',
        'pharmacy_manager'    => 'Pharmacy manager',
        'cashier'             => 'Cashier',
        'billing_officer'     => 'Billing officer',
    ],
];
