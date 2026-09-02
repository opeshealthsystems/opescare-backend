<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpesCare Lite — Shared chrome (layout, nav, status) — English
    |--------------------------------------------------------------------------
    */

    // Side navigation labels
    'nav_dashboard'    => 'Dashboard',
    'nav_lookup'       => 'Health ID Lookup',
    'nav_checkin'      => 'Check-In',
    'nav_consultation' => 'Consultation',
    'nav_billing'      => 'Billing',
    'nav_admin'        => 'Admin',
    'nav_devices'      => 'Devices',
    'nav_conflicts'    => 'Conflicts',
    'nav_full_portal'  => 'Full Portal',

    // Online/offline status indicator
    'status_online'  => 'Online — Synced',
    'status_offline' => 'Offline — Changes will sync when reconnected',

    // Bottom navigation labels
    'bottom_home'    => 'Home',
    'bottom_lookup'  => 'Lookup',
    'bottom_checkin' => 'Check-In',
    'bottom_consult' => 'Consult',
    'bottom_admin'   => 'Admin',

    // Devices view
    'devices_empty'        => 'No Lite devices registered yet. Devices register via the API endpoint',
    'devices_modules'      => 'modules',
    'devices_never'        => 'Never',
    'devices_confirm_activate' => 'Activate this device?',
    'devices_confirm_revoke'   => 'Revoke this device? This cannot be undone.',
    'devices_revoke_reason'    => 'Revoked via Lite portal.',

    // Conflicts view
    'conflicts_resolve_note' => 'Resolved via Lite portal.',

    // Controller flash messages
    'flash_patient_registered' => 'Patient :name registered. Health ID: :health_id',
    'flash_device_activated'   => "Device ':name' activated.",
    'flash_device_revoked'     => "Device ':name' revoked.",
];
