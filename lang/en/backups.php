<?php

return [
    'nav' => 'Backups',
    'title' => 'Backups',
    'subtitle' => 'Where the platform\'s backups are kept, and how to move copies off this server.',

    // Overall verdict
    'state_off_host' => 'Backups are leaving this server',
    'state_off_host_body' => 'At least one backup is confirmed on off-site storage. A failure of this server\'s disk would no longer take every copy with it.',
    'state_configured' => 'Off-site storage is configured, but no backup has reached it yet',
    'state_configured_body' => 'The keys are in place. Run a test backup (step 4 below) to confirm a copy actually arrives.',
    'state_misconfigured' => 'Off-site backups are switched on but not working',
    'state_misconfigured_body' => 'Something required is missing, or the storage could not be read. Check the table below and the steps, then run a test backup.',
    'state_on_host_only' => 'Backups exist only on this server',
    'state_on_host_only_body' => 'This server has a single disk. The database and every backup of it sit on that same disk, so a disk failure would lose both. Add off-site storage keys using the steps below.',

    // Configuration table
    'section_config' => 'Configuration',
    'config_hint' => 'Read from the running configuration. No key or secret is ever shown here, even partly.',
    'row_s3_enabled' => 'Off-site backups switched on (BACKUP_S3_ENABLED)',
    'row_access_key' => 'Access key (AWS_ACCESS_KEY_ID)',
    'row_secret' => 'Secret key (AWS_SECRET_ACCESS_KEY)',
    'row_bucket' => 'Bucket name (AWS_BUCKET)',
    'row_region' => 'Region (AWS_DEFAULT_REGION)',
    'row_endpoint' => 'Endpoint (AWS_ENDPOINT)',
    'row_encryption' => 'Backup encryption password (BACKUP_ENCRYPTION_PASSWORD)',
    'yes' => 'Yes',
    'no' => 'No',
    'set' => 'Set',
    'missing' => 'Missing',
    'optional_aws' => 'Not needed for Amazon S3',

    // Archives
    'section_archives' => 'Backup copies',
    'on_this_server' => 'On this server',
    'off_site' => 'Off-site storage',
    'copies' => 'Copies',
    'latest' => 'Most recent',
    'size' => 'Size',
    'none_yet' => 'None yet',
    'not_configured' => 'Not configured',
    'read_error' => 'Could not be read (:error). Details are in the application log.',
    'stale' => 'Older than :hours hours. Backups run daily at 01:00; check that the scheduler is running.',

    // Instructions
    'section_steps' => 'How to add off-site backup keys',
    'steps_intro' => 'Keys are added on the server, not on this page. A web form that could write secrets into the server\'s configuration would itself be a way to take over the platform, so none exists.',
    'step0_title' => 'Install the S3 storage driver on the server',
    'step0_body' => 'The application ships without the S3 driver package — it is listed only as a suggestion, and a backup attempt on 5 September failed on exactly this ("PortableVisibilityConverter not found" in the log). Install it once, as the site user, before adding keys:',
    'step1_title' => 'Create a bucket and a dedicated key',
    'step1_body' => 'Use Amazon S3 or any S3-compatible provider (Backblaze B2, Cloudflare R2, Wasabi). Create a private bucket, then a key that can reach that one bucket only, with permission to list, read, write and delete objects. Delete is needed so old backups can be cleaned up. Do not reuse a key that can reach anything else.',
    'step2_title' => 'Put the keys in the server\'s .env file',
    'step2_body' => 'Connect to the server over SSH and open the Finder environment file:',
    'step2_after' => 'Set these lines, replacing the placeholder values. Keep everything else in the file as it is.',
    'step2_other_providers' => 'For Backblaze B2, Cloudflare R2 or Wasabi, also set AWS_ENDPOINT to the provider\'s S3 endpoint URL and AWS_USE_PATH_STYLE_ENDPOINT=true. For Amazon S3, leave AWS_ENDPOINT empty.',
    'step3_title' => 'Reload the configuration',
    'step3_body' => 'The application caches its configuration, so it will not see the new keys until this runs:',
    'step4_title' => 'Run a test backup',
    'step4_body' => 'This makes one backup now and uploads it. The list command then shows the copies on each storage location.',
    'step5_title' => 'Come back to this page',
    'step5_body' => 'Refresh it. The status at the top should read that backups are leaving this server, and the off-site storage column should show at least one copy.',

    'password_title' => 'Keep the encryption password somewhere safe, away from this server',
    'password_body' => 'Backups are encrypted with BACKUP_ENCRYPTION_PASSWORD. Without that password a backup cannot be restored, so copying backups off-site does not help if the only copy of the password is on this server. Store it in a password manager.',

    'refresh' => 'Refresh',
];
