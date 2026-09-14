<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * What the backup system is ACTUALLY doing, for the admin Backups page.
 *
 * ── Why this exists ──────────────────────────────────────────────────────
 * Production has one disk. Until off-host storage is configured, every backup
 * lives on the same volume as the database it protects, so a disk failure
 * takes both. The S3 wiring in config/backup.php already exists; what is
 * missing is credentials, and only the owner can supply those. This class
 * lets the admin screen say, plainly, whether that has happened yet.
 *
 * ── What it never does ───────────────────────────────────────────────────
 * It never returns a credential, and never returns a fragment of one. Key
 * presence is reported as a boolean. Screenshots of admin pages get shared,
 * and a "last four characters" is still a piece of a secret.
 *
 * It never writes. Keys are set in `.env` on the server, not through a web
 * form: a page that can write secrets into the environment file is itself a
 * way to take over the application.
 */
final class BackupStatus
{
    /** A local backup older than this is reported as stale. Backups run daily at 01:00. */
    public const STALE_AFTER_HOURS = 36;

    /**
     * @return array{
     *   s3_enabled: bool, key_present: bool, secret_present: bool,
     *   bucket_present: bool, region_present: bool, endpoint_present: bool,
     *   encryption_password_present: bool, disks: array<int,string>,
     *   off_host: bool, state: string,
     *   local: array{count:int, latest_at: ?int, latest_size: ?int, error: ?string},
     *   remote: array{count:int, latest_at: ?int, error: ?string}|null
     * }
     */
    public static function read(): array
    {
        $disks = (array) config('backup.backup.destination.disks', []);

        $status = [
            // Config-cached values, deliberately — the same values the running
            // backup command reads. Reading .env directly would report keys
            // that the application cannot yet see until config:cache is rerun.
            's3_enabled'                  => in_array('s3', $disks, true),
            'key_present'                 => filled(config('filesystems.disks.s3.key')),
            'secret_present'              => filled(config('filesystems.disks.s3.secret')),
            'bucket_present'              => filled(config('filesystems.disks.s3.bucket')),
            'region_present'              => filled(config('filesystems.disks.s3.region')),
            'endpoint_present'            => filled(config('filesystems.disks.s3.endpoint')),
            'encryption_password_present' => filled(config('backup.backup.password')),
            'disks'                       => array_values($disks),
            'local'                       => self::inspect('local'),
            'remote'                      => null,
        ];

        if ($status['s3_enabled'] && $status['key_present'] && $status['secret_present']) {
            $status['remote'] = self::inspect('s3');
        }

        $status['off_host'] = $status['remote'] !== null
            && $status['remote']['error'] === null
            && $status['remote']['count'] > 0;

        $status['state'] = self::state($status);

        return $status;
    }

    /**
     * Cheap, config-only: is off-host storage even switched on and keyed?
     *
     * For the sidebar badge, which renders on every admin page. `read()` lists
     * archives on disk and, once S3 is configured, objects in the bucket — a
     * remote API call on every page load of the whole admin console. This
     * answers the only question the badge asks without touching storage.
     */
    public static function offHostConfigured(): bool
    {
        return in_array('s3', (array) config('backup.backup.destination.disks', []), true)
            && filled(config('filesystems.disks.s3.key'))
            && filled(config('filesystems.disks.s3.secret'))
            && filled(config('filesystems.disks.s3.bucket'));
    }

    /**
     * One overall verdict, so the page can lead with it.
     *
     * `off_host`       at least one backup is confirmed on remote storage
     * `configured`     keys are in place but no remote backup exists yet
     * `misconfigured`  S3 is switched on but something required is missing,
     *                  or the remote could not be read
     * `on_host_only`   the current state: backups exist only on this server
     */
    private static function state(array $s): string
    {
        if ($s['off_host']) {
            return 'off_host';
        }

        if ($s['s3_enabled']) {
            $complete = $s['key_present'] && $s['secret_present'] && $s['bucket_present'] && $s['region_present'];

            if (! $complete || ($s['remote']['error'] ?? null) !== null) {
                return 'misconfigured';
            }

            return 'configured';
        }

        return 'on_host_only';
    }

    /** Count and date the backup archives on one disk. Never throws. */
    private static function inspect(string $disk): array
    {
        $result = ['count' => 0, 'latest_at' => null, 'latest_size' => null, 'error' => null];

        try {
            $storage = Storage::disk($disk);
            $folder = (string) config('backup.backup.name');

            $zips = array_values(array_filter(
                $storage->files($folder),
                static fn (string $f) => str_ends_with(strtolower($f), '.zip'),
            ));

            $result['count'] = count($zips);

            foreach ($zips as $file) {
                $modified = $storage->lastModified($file);

                if ($result['latest_at'] === null || $modified > $result['latest_at']) {
                    $result['latest_at'] = $modified;
                    $result['latest_size'] = $storage->size($file);
                }
            }
        } catch (Throwable $e) {
            // The message can name a bucket or an endpoint; the class name is
            // enough to tell an admin it failed, and the log has the rest.
            report($e);
            $result['error'] = class_basename($e);
        }

        return $result;
    }
}
