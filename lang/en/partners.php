<?php

return [
    // Navigation and page furniture
    'nav'              => 'Partner onboarding',
    'breadcrumb_home'  => 'Admin',
    'title'            => 'Partner onboarding',
    'subtitle'         => 'The API clients external systems use to reach OpesCare. Every credential issued here is a key to patient data.',

    // Empty state — zero clients is a real state, not an error
    'none_title'       => 'No partner has been onboarded yet',
    'none_body'        => 'No integration client exists on this platform. Nothing external can authenticate against the API until one is created here.',

    // Listing
    'col_name'         => 'Partner',
    'col_client_id'    => 'Client ID',
    'col_organisation' => 'Organisation',
    'col_environment'  => 'Environment',
    'col_scopes'       => 'Scopes',
    'col_status'       => 'Status',
    'col_last_used'    => 'Last used',
    'never_used'       => 'Never used',
    'no_organisation'  => 'Platform-wide',
    'filter_all'       => 'All statuses',
    'filter_all_envs'  => 'All environments',
    'filter_apply'     => 'Filter',
    'total_clients'    => ':count client(s) onboarded',

    // Statuses
    'status_active'    => 'Active',
    'status_suspended' => 'Suspended',
    'status_revoked'   => 'Revoked',
    'env_sandbox'      => 'Sandbox',
    'env_production'   => 'Production',

    // Create
    'create_nav'       => 'Onboard a partner',
    'create_title'     => 'Onboard a partner',
    'create_subtitle'  => 'Issue credentials to an external system. The secret is shown once, at creation, and cannot be retrieved afterwards.',
    'f_name'           => 'Partner name',
    'f_name_help'      => 'The system or organisation these credentials belong to, as it should appear in the audit trail.',
    'f_description'    => 'Description',
    'f_contact_email'  => 'Technical contact email',
    'f_environment'    => 'Environment',
    'f_organisation'   => 'Organisation represented',
    'f_organisation_help' => 'Optional. Leave empty for a platform-wide partner that acts for no single facility.',
    'f_organisation_none' => 'None — platform-wide',
    'f_scopes'         => 'Scopes granted',
    'f_scopes_help'    => 'A new client is granted nothing by default. Select only what this partner needs; there is no wildcard.',
    'f_submit'         => 'Create client and issue credentials',
    'err_unknown_organisation' => 'That organisation does not exist.',
    'err_scopes_required' => 'Select at least one scope. A client with no scope can authenticate but can read nothing.',

    // Detail
    'detail_title'     => 'Partner client',
    'back_to_list'     => 'Back to partners',
    'section_identity' => 'Identity',
    'section_scopes'   => 'Scopes',
    'section_creds'    => 'Credentials',
    'section_trail'    => 'Audit trail',
    'created_at'       => 'Created',
    'approved_at'      => 'Approved',
    'request_count'    => 'Requests recorded',
    'contact'          => 'Technical contact',

    // The one-time secret
    'secret_once_title' => 'Copy this secret now',
    'secret_once_body'  => 'This is the only time this secret will ever be shown. OpesCare stores an Argon2id hash of it and cannot display it again. If it is lost, rotate the credential.',
    'secret_client_id'  => 'Client ID',
    'secret_value'      => 'Client secret',
    'secret_not_stored' => 'The secret is not stored. Only its Argon2id hash is held, and no screen can read it back.',

    // Actions
    'act_rotate'        => 'Rotate secret',
    'act_rotate_help'   => 'Issues a new secret and invalidates the current one immediately. Any system still using the old secret stops working.',
    'act_suspend'       => 'Suspend',
    'act_suspend_help'  => 'Stops authentication now, including tokens already issued. Intended to be reversible.',
    'act_revoke'        => 'Revoke',
    'act_revoke_help'   => 'Permanently withdraws this partner\'s access. Tokens already issued stop working on their next request.',
    'act_reinstate'     => 'Reinstate',
    'act_reason'        => 'Reason',
    'act_confirm'       => 'Confirm',
    'act_scopes_save'   => 'Save scopes',

    // Audit trail
    'trail_none'        => 'Nothing recorded for this client yet.',
    'trail_col_when'    => 'When',
    'trail_col_action'  => 'Action',
    'trail_col_actor'   => 'Actor',
    'trail_col_reason'  => 'Reason',

    // Flashes
    'flash_created'          => 'Partner client created. The secret below is shown once.',
    'flash_rotated'          => 'Secret rotated. The previous secret no longer works.',
    'flash_scopes_changed'   => 'Scopes updated.',
    'flash_status_active'    => 'Client reinstated.',
    'flash_status_suspended' => 'Client suspended. Tokens already issued stop working immediately.',
    'flash_status_revoked'   => 'Client revoked. Tokens already issued stop working immediately.',
];
