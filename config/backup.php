<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
|--------------------------------------------------------------------------
| OpesCare Finder — backup destinations
|--------------------------------------------------------------------------
|
| Ops history (2026-09-06): the destination list used to be `['s3']` only.
| The S3 Flysystem adapter (league/flysystem-aws-s3-v3) was never installed
| and the host never received AWS credentials, so `backup:run`, `backup:clean`
| and `backup:monitor` all died at disk resolution with
| `Class "League\Flysystem\AwsS3V3\PortableVisibilityConverter" not found`
| — every night, before a single byte was dumped.
|
| The rule now: the `local` disk is ALWAYS a destination, so a backup exists
| on the host no matter what; `s3` is added only when BACKUP_S3_ENABLED=true.
| Turning S3 on requires the adapter package AND credentials — see
| docs/ops-backups.md before flipping the flag.
|
| `backup:clean` and `backup:monitor` use the same computed list, so the
| three commands can never disagree about where backups live.
*/
$backupS3Enabled = filter_var(env('BACKUP_S3_ENABLED', false), FILTER_VALIDATE_BOOLEAN);

$backupDisks = $backupS3Enabled ? ['local', 's3'] : ['local'];

return [

    'backup' => [
        /*
         * The name of this application. You can use this name to monitor
         * the backups.
         *
         * On the `local` disk (root storage/app/private) this is also the
         * folder the archives land in: storage/app/private/<name>/.
         */
        'name' => env('APP_NAME', 'OpesCare'),

        'source' => [
            'files' => [
                /*
                 * The list of directories and files that will be included in the backup.
                 */
                'include' => [
                    base_path(),
                ],

                /*
                 * These directories and files will be excluded from the backup.
                 *
                 * Directories used by the backup process (the temporary directory and
                 * the local destination folder) are automatically excluded.
                 *
                 * Secrets are deliberately kept OUT of the archive: `.env` (and any
                 * `.env.bak-*` copy) and `bootstrap/cache` (the cached config.php
                 * holds every env value in plain text). The archive is not encrypted
                 * unless BACKUP_ENCRYPTION_PASSWORD is set, and it may one day be
                 * copied offsite. Keep the .env in the ops password manager instead;
                 * a restore needs it from there, not from the archive.
                 *
                 * `storage/framework` is regenerated cache/session/view data — noise.
                 */
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('.env*'),
                    base_path('bootstrap/cache'),
                    storage_path('logs'),
                    storage_path('framework'),
                ],

                /*
                 * Determines if symlinks should be followed.
                 */
                'follow_links' => false,

                /*
                 * Determines if it should avoid unreadable folders.
                 */
                'ignore_unreadable_directories' => false,

                /*
                 * This path is used to make directories in resulting zip-file relative
                 * Set to `null` to include complete absolute path
                 * Example: base_path()
                 */
                'relative_path' => null,
            ],

            /*
             * The names of the connections to the databases that should be backed up
             * MySQL, PostgreSQL, SQLite and Mongo databases are supported.
             *
             * The content of the database dump may be customized for each connection
             * by adding a 'dump' key to the connection settings in config/database.php.
             * E.g.
             * 'mysql' => [
             *       ...
             *      'dump' => [
             *           'exclude_tables' => [
             *                'table_to_exclude_from_backup',
             *                'another_table_to_exclude'
             *            ]
             *       ],
             * ],
             *
             * If you are using only InnoDB tables on a MySQL server, you can
             * also supply the useSingleTransaction option to avoid table locking.
             *
             * E.g.
             * 'mysql' => [
             *       ...
             *      'dump' => [
             *           'useSingleTransaction' => true,
             *       ],
             * ],
             *
             * For a complete list of available customization options, see https://github.com/spatie/db-dumper
             */
            'databases' => [
                'pgsql',
            ],
        ],

        /*
         * The database dump can be compressed to decrease disk space usage.
         *
         * Out of the box Laravel-backup supplies
         * Spatie\DbDumper\Compressors\GzipCompressor::class.
         *
         * You can also create custom compressor. More info on that here:
         * https://github.com/spatie/db-dumper#using-compression
         *
         * If you do not want any compressor at all, set it to null.
         */
        'database_dump_compressor' => \Spatie\DbDumper\Compressors\GzipCompressor::class,

        /*
         * If specified, the database dumped file name will contain a timestamp (e.g.: 'Y-m-d-H-i-s').
         */
        'database_dump_file_timestamp_format' => null,

        /*
         * The base of the dump filename, either 'database' or 'connection'
         *
         * If 'database' (default), the dumped filename will contain the database name.
         * If 'connection', the dumped filename will contain the connection name.
         */
        'database_dump_filename_base' => 'database',

        /*
         * The file extension used for the database dump files.
         *
         * If not specified, the file extension will be .archive for MongoDB and .sql for all other databases
         * The file extension should be specified without a leading .
         */
        'database_dump_file_extension' => '',

        'destination' => [
            /*
             * The compression algorithm to be used for creating the zip archive.
             *
             * If backing up only database, you may choose gzip compression for db dump and no compression at zip.
             *
             * Some common algorithms are listed below:
             * ZipArchive::CM_STORE (no compression at all; set 0 as compression level)
             * ZipArchive::CM_DEFAULT
             * ZipArchive::CM_DEFLATE
             * ZipArchive::CM_BZIP2
             * ZipArchive::CM_XZ
             *
             * For more check https://www.php.net/manual/zip.constants.php and confirm it's supported by your system.
             */
            'compression_method' => ZipArchive::CM_DEFAULT,

            /*
             * The compression level corresponding to the used algorithm; an integer between 0 and 9.
             *
             * Check supported levels for the chosen algorithm, usually 1 means the fastest and weakest compression,
             * while 9 the slowest and strongest one.
             *
             * Setting of 0 for some algorithms may switch to the strongest compression.
             */
            'compression_level' => 9,

            /*
             * The filename prefix used for the backup zip file.
             */
            'filename_prefix' => 'opescare_backup_',

            /*
             * The disk names on which the backups will be stored.
             *
             * Computed at the top of this file: `local` always, `s3` only when
             * BACKUP_S3_ENABLED=true. Never hardcode a remote-only list here —
             * that is exactly what left the Finder with no backups at all.
             */
            'disks' => $backupDisks,

            /*
             * Determines whether to allow backups to continue when some targets fail instead of failing completely.
             *
             * `true` so that an offsite (s3) hiccup never costs us the local copy:
             * the local write still lands, the failing disk raises
             * BackupHasFailed (mail alert), and only "failed on every disk"
             * makes the command itself exit non-zero.
             */
            'continue_on_failure' => true,
        ],

        /*
         * The directory where the temporary files will be stored.
         */
        'temporary_directory' => storage_path('app/backup-tmp'),

        /*
         * The password to be used for archive encryption.
         * Set to `null` to disable encryption.
         *
         * Empty on the Finder host today (archives are plain zips inside a
         * 0700 folder owned by `finder`). Set it BEFORE enabling an offsite
         * disk — and record it in the ops password manager, because an
         * encrypted archive whose password was lost is not a backup.
         *
         * `?: null` matters: an env line that is present but EMPTY
         * (`BACKUP_ENCRYPTION_PASSWORD=`) reaches here as "", and spatie only
         * skips encryption for null — with "" it AES-encrypts every entry with
         * an empty password and libzip then fails the archive at close() with
         * "Invalid argument". That silently killed every run on the Finder host
         * (found 2026-09-06, right behind the missing-S3-adapter error).
         */
        'password' => env('BACKUP_ENCRYPTION_PASSWORD') ?: null,

        /*
         * The encryption algorithm to be used for archive encryption.
         * Set to 'none' to disable encryption.
         *
         * Supported: 'none', 'default', 'aes128', 'aes192', 'aes256'
         *
         * When set to 'default', we'll use AES-256 if available on your system.
         */
        'encryption' => 'default',

        /*
         * After creating the zip, verify it can be opened and contains files.
         * Recommended for critical backups but adds a small overhead.
         *
         * On: re-opens the finished archive and counts its entries before it
         * is copied anywhere, so an unreadable zip fails the run loudly (mail)
         * instead of being stored as if it were a backup.
         */
        'verify_backup' => true,

        /*
         * The number of attempts, in case the backup command encounters an exception
         */
        'tries' => 1,

        /*
         * The number of seconds to wait before attempting a new backup if the previous try failed
         * Set to `0` for none
         */
        'retry_delay' => 0,
    ],

    /*
     * You can get notified when specific events occur. Out of the box you can use 'mail' and 'slack'.
     * For Slack you need to install laravel/slack-notification-channel.
     *
     * You can also use your own notification classes, just make sure the class is named after one of
     * the `Spatie\Backup\Notifications\Notifications` classes.
     */
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        /*
         * Here you can specify the notifiable to which the notifications should be sent. The default
         * notifiable will use the variables specified in this config file.
         */
        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_ALERT_EMAIL', 'devops@opescare.com'),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',

            /*
             * If this is set to null the default channel of the webhook will be used.
             */
            'channel' => null,

            'username' => null,

            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',

            /*
             * If this is an empty string, the name field on the webhook will be used.
             */
            'username' => '',

            /*
             * If this is an empty string, the avatar on the webhook will be used.
             */
            'avatar_url' => '',
        ],

        /*
         * A generic webhook channel that POSTs JSON to a URL.
         * Useful for Mattermost, Microsoft Teams, or custom integrations.
         */
        'webhook' => [
            'url' => '',
        ],
    ],

    /*
     * The log channel used for backup activity messages.
     *
     * Set to a channel name defined in config/logging.php to use that channel.
     * Set to false to disable backup logging entirely.
     * Set to null to use the default log channel.
     */
    'log_channel' => null,

    /*
     * Here you can specify which backups should be monitored.
     * If a backup does not meet the specified requirements the
     * UnHealthyBackupWasFound event will be fired.
     *
     * Same disk list as the destination, so `backup:monitor` (09:00) checks
     * exactly the places `backup:run` (01:00) wrote to.
     */
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'OpesCare'),
            'disks' => $backupDisks,
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        /*
         * The strategy that will be used to cleanup old backups. The default strategy
         * will keep all backups for a certain amount of days. After that period only
         * a daily backup will be kept. After that period only weekly backups will
         * be kept and so on.
         *
         * No matter how you configure it the default strategy will never
         * delete the newest backup.
         *
         * Retention policy (one scheduled backup per night at 01:00):
         *   - every archive for 7 days (7 nightly points, plus any manual runs),
         *   - then one per day for a further 7 days,
         *   - then one per week for 4 weeks,
         *   - then one per month for 3 months,
         *   - no yearly archives,
         *   - and never more than 5000 MB in total on a disk (oldest go first).
         * `backup:clean` runs daily at 00:00 against the same disk list.
         */
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            /*
             * The number of days for which backups must be kept.
             */
            'keep_all_backups_for_days' => 7,

            /*
             * After the "keep_all_backups_for_days" period is over, the most recent backup
             * of that day will be kept. Older backups within the same day will be removed.
             * If you create backups only once a day, no backups will be removed yet.
             */
            'keep_daily_backups_for_days' => 7,

            /*
             * After the "keep_daily_backups_for_days" period is over, the most recent backup
             * of that week will be kept. Older backups within the same week will be removed.
             */
            'keep_weekly_backups_for_weeks' => 4,

            /*
             * After the "keep_weekly_backups_for_weeks" period is over, the most recent backup
             * of that month will be kept. Older backups within the same month will be removed.
             */
            'keep_monthly_backups_for_months' => 3,

            /*
             * After the "keep_monthly_backups_for_months" period is over, the most recent backup
             * of that year will be kept. Older backups within the same year will be removed.
             */
            'keep_yearly_backups_for_years' => 0,

            /*
             * After cleaning up the backups remove the oldest backup until
             * this amount of megabytes has been reached.
             * Set null for unlimited size.
             */
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],

        /*
         * OpesCare: camelCase alias for test compatibility and documentation clarity.
         * Keep it identical to `default_strategy` above.
         */
        'defaultStrategy' => [
            'keepAllBackupsForDays' => 7,
            'keepDailyBackupsForDays' => 7,
            'keepWeeklyBackupsForWeeks' => 4,
            'keepMonthlyBackupsForMonths' => 3,
            'keepYearlyBackupsForYears' => 0,
            'deleteOldestBackupsWhenUsingMoreMegabytesThan' => 5000,
        ],

        /*
         * The number of attempts, in case the cleanup command encounters an exception
         */
        'tries' => 1,

        /*
         * The number of seconds to wait before attempting a new cleanup if the previous try failed
         * Set to `0` for none
         */
        'retry_delay' => 0,
    ],

];
