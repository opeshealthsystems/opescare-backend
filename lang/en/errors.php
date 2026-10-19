<?php

return [
    'generic'             => 'Something went wrong. Please try again.',
    'unauthorized'        => 'You are not authorized to perform this action.',
    'forbidden'           => 'Access denied.',
    'not_found'           => 'The requested resource was not found.',
    'validation'          => 'Please check the form for errors.',
    'session_expired'     => 'Your session has expired. Please sign in again.',
    'consent_required'    => 'Patient consent is required before accessing this record.',
    'facility_mismatch'   => 'This record belongs to a different facility.',
    'limit_exceeded'      => 'You have reached the usage limit for this feature.',
    'feature_disabled'    => 'This feature is not available for your subscription plan.',
    'phi_blocked'         => 'Access to this information requires additional authorization.',
    'offline'             => 'You appear to be offline. Some features may be unavailable.',
    'sync_conflict'       => 'A data conflict was detected during synchronization. Please review.',
    'availability_not_guaranteed' => 'Medicine, blood, and service availability shown is indicative only and not guaranteed.',

    // Full-page HTTP error screens (resources/views/errors/_page.blade.php).
    'actions' => [
        'my_dashboard'    => 'Back to my dashboard',
        'home'            => 'Back to Home',
        'sign_in'         => 'Sign In',
        'help_centre'     => 'Help Centre',
        'contact_support' => 'Contact Support',
        'status_page'     => 'System Status page',
        'try_again'       => 'Try again',
    ],
    'pages' => [
        '401' => ['title' => 'Sign in required',    'body' => 'You need to sign in to view this page.'],
        '402' => ['title' => 'Payment required',    'body' => 'This action needs a completed payment before it can continue. Care itself is never withheld — contact support if you believe this is a mistake.'],
        '403' => ['title' => 'Access denied',       'body' => "You don't have permission to view this page. If you believe this is a mistake, please sign in with the correct account or contact your administrator."],
        '404' => ['title' => 'Page not found',      'body' => "The page you're looking for doesn't exist or may have been moved. Check the URL, or use one of the links below to find your way back."],
        '419' => ['title' => 'Page expired',        'body' => 'Your session timed out while this page was open. Go back and try again, or sign in afresh.'],
        '429' => ['title' => 'Too many requests',   'body' => 'You have made too many requests in a short time. Please wait a minute and try again.'],
        '500' => ['title' => 'Server error',        'body' => 'Something went wrong on our end. Our team has been notified and is working to fix this as quickly as possible.', 'note' => 'Your data is safe. If this is urgent, please contact support directly.', 'footer_prefix' => 'Check our', 'footer_suffix' => 'for live updates.'],
        '503' => ['title' => 'Service unavailable', 'body' => 'OpesCare is briefly unavailable, usually for scheduled maintenance. Please try again in a few minutes.'],
    ],
    'reason_label' => 'Reason:',
    'abort' => [
        'no_facility_role'   => 'Your account has no role assigned at this facility. Contact your administrator.',
        'demo_disabled'      => 'Demo mode disabled.',
        'demo_ip_denied'     => 'Access denied from your IP address.',
        'demo_account_unavailable' => 'This demo account is not available.',
        'platform_admin_only' => 'This area is restricted to OpesCare platform administrators.',
    ],
];
