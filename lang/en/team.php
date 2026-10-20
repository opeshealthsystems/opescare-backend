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

    // Invite link handover. The link is the channel of record: email delivery
    // from this host is best-effort, so the copyable link is always shown.
    'invite_link_title' => 'Invitation link',
    'invite_link_help'  => 'Copy this link and send it to :email — this link is the reliable way to deliver the invitation.',

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
     * ── Invitation email ────────────────────────────────────────────────
     *
     * A courtesy on top of the link, never a replacement for it. The two
     * status lines below are worded so that neither of them claims the message
     * was delivered — the most that is ever known is that the mail server
     * accepted it.
     */
    'invite_mail_accepted' => 'The invitation email was accepted by the mail server for :email. Delivery is not guaranteed — if it does not arrive, send the link below yourself.',
    'invite_mail_failed'   => 'The invitation email could not be sent to :email. Copy the link below and share it yourself.',

    'mail' => [
        'subject'       => 'You have been invited to join :facility on OpesCare',
        'greeting'      => 'Hello,',
        'intro'         => 'You have been invited to join :facility on OpesCare as :role.',
        'invited_by'    => 'Invitation issued by: :name',
        'action'        => 'Activate my account',
        'expiry'        => 'This invitation link expires on :date.',
        'link_fallback' => 'If the button does not work, paste this address into your browser: :url',
        'ignore'        => 'If you were not expecting this invitation, you can safely ignore this message — no account is created until the link is used.',
        'salutation'    => 'The OpesCare team',
    ],

    /*
     * ── Join requests ───────────────────────────────────────────────────
     *
     * The mirror image of an invitation: the clinician asks, the facility
     * decides. The requester may describe the role they want; the facility
     * grants the role. Those are two different things and the wording below
     * keeps them apart on screen as well as in the schema.
     */
    'join_nav'            => 'Join a facility',
    'join_page_title'     => 'Join a facility',
    'join_page_subtitle'  => 'Ask a facility to add you to its team. The facility reviews your request and decides which role you are given.',
    'join_role_notice'    => 'You can describe the role you are looking for, but the facility chooses the role you are granted.',

    'join_teams_title'    => 'Facilities you already work at',
    'join_teams_empty'    => 'You are not on any facility team yet.',

    'join_search_label'       => 'Search for a facility',
    'join_search_placeholder' => 'Facility name',
    'join_search_submit'      => 'Search',
    'join_search_prompt'      => 'Search for the facility you work at to send a request.',
    'join_search_empty'       => 'No facility matches that name.',
    'join_search_hint'        => 'Showing at most :limit matches — narrow the name if the facility you want is missing.',

    'join_form_title'              => 'Request to join :facility',
    'join_field_requested_role'    => 'Role you are asking for (optional)',
    'join_field_requested_role_ph' => 'e.g. night-shift midwife',
    'join_field_requested_role_help' => 'This is a note for the reviewer. The facility grants the role, so what you type here does not decide your access.',
    'join_field_message'           => 'Message to the facility (optional)',
    'join_field_message_ph'        => 'Anything that helps them recognise you — department, start date, who referred you.',
    'join_submit'                  => 'Send request',
    'join_ask_button'              => 'Ask to join',

    'join_my_requests_title' => 'My requests',
    'join_my_requests_empty' => 'You have not asked to join any facility yet.',
    'join_col_facility'      => 'Facility',
    'join_col_professional'  => 'Professional',
    'join_col_requested'     => 'Role asked for',
    'join_col_granted'       => 'Role granted',
    'join_col_message'       => 'Message',
    'join_col_submitted'     => 'Submitted',
    'join_col_decision'      => 'Decision',
    'join_action_withdraw'   => 'Withdraw',
    'join_action_approve'    => 'Approve',
    'join_action_decline'    => 'Decline',
    'join_grant_role_label'  => 'Grant this role',
    'join_note_label'        => 'Note (optional)',
    'join_no_message'        => 'No message',
    'join_decided_by'        => 'Decided by :name',

    'join_queue_title' => 'Requests to join this facility',
    'join_queue_help'  => 'Clinicians who asked to be added to this team. Choose the role you are granting — the role they asked for is only a note.',
    'join_queue_empty' => 'No one has asked to join this facility.',
    'join_queue_badge' => ':count awaiting review',

    'join_status_pending'   => 'Awaiting review',
    'join_status_approved'  => 'Approved',
    'join_status_declined'  => 'Declined',
    'join_status_withdrawn' => 'Withdrawn',

    // Flash messages
    'join_submitted'        => 'Your request was sent to :facility. You will see the decision on this page.',
    'join_withdrawn'        => 'Request withdrawn.',
    'join_approved'         => 'Request approved. The account is now on this team as :role.',
    'join_declined'         => 'Request declined.',
    'join_already_pending'  => 'You already have a request awaiting review at that facility.',
    'join_already_member'   => 'You are already on that facility’s team.',
    'join_already_decided'  => 'That request has already been decided and can no longer be changed.',
    'join_facility_unknown' => 'That facility could not be found.',
    'join_cooldown'         => 'That facility declined your last request. You can ask again from :date, or ask them for an invitation link.',
    'join_action_failed'    => 'The request could not be processed. Please try again.',

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
        'imaging_tech'        => 'Imaging technologist',
        'radiologist'         => 'Radiologist',
        'blood_bank_officer'  => 'Blood bank officer',
        'blood_bank_manager'  => 'Blood bank manager',
        'pharmacist'          => 'Pharmacist',
        'pharmacy_technician' => 'Pharmacy technician',
        'pharmacy_manager'    => 'Pharmacy manager',
        'cashier'             => 'Cashier',
        'billing_officer'     => 'Billing officer',
    ],
];
