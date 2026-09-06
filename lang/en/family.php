<?php

/*
|--------------------------------------------------------------------------
| Family / dependents — guardian experience on the web portal
|--------------------------------------------------------------------------
| The guardian dashboard, the "viewing as" banner, the invitation flows,
| the age-transition copy and the dependent-aware record pages
| (immunisations, timeline, documents, allergies, conditions, care plans,
| access logs, printable Health ID card). Older family strings keep their
| keys in public.php / flash.php. Must stay 1:1 with lang/fr/family.php.
*/

return [

    // ── "You are viewing …" banner ──────────────────────────────────────
    'banner_viewing'          => 'You are viewing :name’s record',
    'banner_health_id'        => 'Health ID :id',
    'banner_read_only'        => 'Read-only access',
    'banner_full'             => 'Full access',
    'banner_switch_back'      => 'Switch back to my record',
    'banner_own_inbox'        => 'Messages and notifications always belong to your own account — they are not :name’s.',
    'switch_no_longer_active' => 'Guardian access is no longer active for this dependent.',
    'read_only_refused'       => 'Your access to :name’s record is read-only, so this action is not available. Full access can be granted by the account holder.',
    'read_only_qr_note'       => 'A QR code identifies :name to a facility. Only a guardian with full access can generate one.',

    // ── Guardian dashboard ─────────────────────────────────────────────
    'dash_title'              => 'My dependents',
    'dash_subtitle'           => 'The people whose health records you manage. Open a record to see their Health ID card, appointments, immunisations and more.',
    'dash_breadcrumb'         => 'Dependents',
    'dash_empty_title'        => 'No dependents yet',
    'dash_empty_body'         => 'Register a child who has no Health ID yet, or invite an adult who already has one — they accept from their own account.',
    'dash_view_as'            => 'Open record',
    'dash_add_child'          => 'Register a child',
    'dash_invite_adult'       => 'Invite an adult',
    'dash_incoming'           => 'Invitations waiting for you',
    'dash_incoming_count'     => ':count invitation(s) waiting for your answer',
    'dash_next_appointment'   => 'Next appointment',
    'dash_none_scheduled'     => 'None scheduled',
    'dash_immunisations'      => 'Immunisations',
    'dash_identity'           => 'Identity',
    'dash_age'                => ':age yrs',
    'dash_pending_invite'     => 'Waiting for them to accept',
    'dash_currently_viewing'  => 'Currently open',
    'dash_nav_title'          => 'Go to',
    'dash_hidden_dependent'   => 'This record is not visible in the current environment.',
    'dash_quick_links'        => 'Open directly',

    // ── Navigation labels (guardian dashboard, patient quick actions) ──
    'nav_dashboard'           => 'Dashboard',
    'nav_dependents'          => 'Dependents',
    'nav_family'              => 'Family',
    'nav_appointments'        => 'Appointments',
    'nav_prescriptions'       => 'Prescriptions',
    'nav_immunizations'       => 'Immunisations',
    'nav_timeline'            => 'Health timeline',
    'nav_documents'           => 'Documents',
    'nav_allergies'           => 'Allergies',
    'nav_clinical'            => 'Conditions',
    'nav_care_plans'          => 'Care plans',
    'nav_access_logs'         => 'Access logs',
    'nav_print_card'          => 'Print Health ID card',
    'nav_messages'            => 'Messages',
    'nav_settings'            => 'Settings',
    'nav_care_map'            => 'Care Map',
    'nav_invitations'         => 'Invitations for me',
    'nav_profile'             => 'Profile',
    'nav_records'             => 'Records',

    // ── Age transition ─────────────────────────────────────────────────
    'age_turns_majority'      => ':name turns :age on :date. From that day the link needs their own approval, and it ends :grace days later unless they keep it.',
    'age_grace'               => 'In grace period — :name is now an adult. Access ends on :date unless they keep it from their own account.',
    'age_adult_must_invite'   => 'A person aged :age or over is invited, not registered — only they can agree to share their record. The details you entered have been carried over.',
    'age_child_note'          => 'Register only a child under :age who does not yet hold an OpesCare Health ID. An adult, or anyone who already has a Health ID, is invited instead and accepts from their own account.',

    // ── Relationship (the guardian’s role toward the dependent) ────────
    'relationship' => [
        'parent'         => 'Parent',
        'child'          => 'Child (I am their son or daughter)',
        'grandparent'    => 'Grandparent',
        'spouse'         => 'Spouse',
        'sibling'        => 'Sibling',
        'caregiver'      => 'Caregiver',
        'legal_guardian' => 'Legal guardian',
        'other'          => 'Other',
    ],
    'relationship_hint'       => 'Your role toward this person — “Parent” if they are your child, “Child” if they are your mother or father.',

    // ── Access levels (canonical: full | read_only) ───────────────────
    'access' => [
        'full'      => 'Full access',
        'read_only' => 'Read only',
    ],
    'access_full_explained'      => 'Full access: see everything, book and cancel appointments, request refills, update contact details, and present their Health ID QR code.',
    'access_read_only_explained' => 'Read only: see the record, appointments, prescriptions and immunisations, but change nothing and generate no QR code.',
    'access_legacy_note'         => 'This link was created with an older access setting (:value) and currently works as read-only. Save to confirm a level.',

    // ── Invite an adult ────────────────────────────────────────────────
    'invite_contact_label'       => 'Health ID, email or phone number',
    'invite_contact_hint'        => 'Exact match only — the Health ID as printed on their card, or the email / phone number on their OpesCare account. There is no search by name.',
    'invite_contact_placeholder' => 'CM-HID-XXXX-XXXX-XXXX',
    'invite_how_it_works'        => 'They receive the invitation on their own OpesCare account and decide whether to accept. Nothing is shared until they do.',
    'invite_no_match'            => 'No OpesCare account matches that Health ID, email or phone number exactly.',
    'invite_needs_account'       => 'The person must already hold an OpesCare Health ID and sign in to accept. A child without one is registered instead.',

    // ── Invitations addressed to me (the invited adult) ────────────────
    'inv_title'          => 'Invitations for me',
    'inv_subtitle'       => 'People asking to follow your health record. You decide — nothing is shared until you accept.',
    'inv_breadcrumb'     => 'Invitations',
    'inv_empty'          => 'No one is waiting for your answer.',
    'inv_from'           => ':name asks to be linked to your record as your :relationship, with :access.',
    'inv_expires'        => 'Expires :date',
    'inv_accept'         => 'Accept',
    'inv_decline'        => 'Decline',
    'inv_accepted'       => 'Invitation accepted. :name can now see your record.',
    'inv_declined'       => 'Invitation declined. Nothing was shared.',
    'inv_not_found'      => 'That invitation is no longer open.',
    'inv_no_patient'     => 'Invitations are addressed to a Health ID. Complete your patient profile to receive them.',
    'inv_section_title'  => 'Invitations waiting for your answer',
    'inv_view_all'       => 'See all invitations',
    'inv_not_for_you'    => 'This invitation was not addressed to your Health ID.',

    // ── Dependent-aware record pages ───────────────────────────────────
    'record_of'          => 'Record of :name',
    'back_to_dashboard'  => 'Back to dashboard',

    'imm_title'          => 'Immunisations',
    'imm_subtitle'       => 'Vaccines on record, most recent first.',
    'imm_empty'          => 'No immunisations on record yet.',
    'col_vaccine'        => 'Vaccine',
    'col_date'           => 'Date',
    'col_dose'           => 'Dose',
    'col_lot'            => 'Lot',
    'col_facility'       => 'Facility',
    'col_status'         => 'Status',

    'tl_title'           => 'Health timeline',
    'tl_subtitle'        => 'Visits, lab results and prescriptions in one chronological feed.',
    'tl_empty'           => 'Nothing on the timeline yet.',
    'tl_visit'           => 'Visit',
    'tl_lab_result'      => 'Lab result',
    'tl_prescription'    => 'Prescription',
    'tl_items'           => ':count medication(s)',

    'doc_title'          => 'Documents',
    'doc_subtitle'       => 'Official documents issued to this record.',
    'doc_empty'          => 'No documents yet.',
    'doc_download'       => 'Download',
    'doc_not_released'   => 'Not released',
    'col_document'       => 'Document',
    'col_number'         => 'Number',
    'col_issued'         => 'Issued',
    'col_expires'        => 'Expires',

    'alg_title'          => 'Allergies',
    'alg_subtitle'       => 'Recorded allergies and their severity.',
    'alg_empty'          => 'No allergies on record.',
    'col_substance'      => 'Substance',
    'col_severity'       => 'Severity',
    'col_recorded'       => 'Recorded',

    'cl_title'           => 'Conditions',
    'cl_subtitle'        => 'Diagnoses and conditions recorded by the care team.',
    'cl_empty'           => 'No conditions on record.',
    'col_condition'      => 'Condition',
    'col_code'           => 'Code',
    'col_primary'        => 'Primary',
    'yes'                => 'Yes',

    'cp_title'           => 'Care plans',
    'cp_subtitle'        => 'Active care plans, with their goals and interventions.',
    'cp_empty'           => 'No active care plan.',
    'cp_goals'           => 'Goals',
    'cp_interventions'   => 'Interventions',
    'cp_period'          => ':from to :to',
    'cp_ongoing'         => 'ongoing',

    'log_title'          => 'Access logs',
    'log_subtitle'       => 'Every time this Health ID was looked up or scanned.',
    'log_empty'          => 'No access recorded yet.',
    'col_when'           => 'When',
    'col_who'            => 'Who',
    'col_type'           => 'Type',
    'col_purpose'        => 'Purpose',
    'col_result'         => 'Result',

    // ── Printable Health ID card (PDF) ────────────────────────────────
    'card_title'              => 'Health ID',
    'card_print'              => 'Print Health ID card',
    'card_print_hint'         => 'A wallet-size card (PDF) with the Health ID and a verification QR code.',
    'card_dob'                => 'Date of birth',
    'card_sex'                => 'Sex',
    'card_blood'              => 'Blood group',
    'card_issued'             => 'Issued',
    'card_verify'             => 'Scan to verify',
    'card_footer'             => 'Identifies the holder to OpesCare-connected facilities. The Health ID alone opens no record — consent is asked at each facility.',
    'card_status_verified'    => 'Verified identity',
    'card_status_provisional' => 'Provisional identity',
    'card_sex_male'           => 'Male',
    'card_sex_female'         => 'Female',

    // ── Guardian onboarding exit ───────────────────────────────────────
    'pending_next_steps'      => 'You can already set up who you care for:',
    'child_may_exist' => 'Someone with these details may already hold an OpesCare Health ID. Invite them instead, so their existing record is linked with their consent.',
];
