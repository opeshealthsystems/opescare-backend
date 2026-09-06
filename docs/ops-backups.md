# Backups — finder.opescare.cloud

*Checked against the live host on 2026-09-06. If the code disagrees, trust the code and fix this page.*

The Finder uses [spatie/laravel-backup](https://spatie.be/docs/laravel-backup) (v10). Configuration lives in
`config/backup.php`; the schedule in `routes/console.php`; the runner is the systemd unit
`finder-scheduler.timer` (every minute, user `finder`, `artisan schedule:run`).

## What was broken (history)

Until 2026-09-06 the destination list was `['s3']` only. The S3 Flysystem adapter
(`league/flysystem-aws-s3-v3`) was never installed and the host never received AWS credentials,
so every scheduled `backup:run` (01:00), `backup:clean` (00:00) and `backup:monitor` (09:00) died
at disk resolution with `Class "League\Flysystem\AwsS3V3\PortableVisibilityConverter" not found`
— before a single byte was dumped. The Finder had **no backups at all**.

Behind that sat a second, masked failure: the host `.env` carried
`BACKUP_ENCRYPTION_PASSWORD=` (present but empty). spatie only skips encryption when the
password is `null`; with `""` it AES-encrypts every entry with an empty password and libzip rejects
the archive at `close()` ("Invalid argument"). `config/backup.php` now maps an empty value to
`null`, i.e. **an empty `BACKUP_ENCRYPTION_PASSWORD` means "no encryption"**, never "encrypt
with nothing".

The rule now: **the `local` disk is always a destination**; `s3` is added only when
`BACKUP_S3_ENABLED=true`. `backup:clean` and `backup:monitor` use the same computed list, and
`verify_backup` re-opens every finished archive and counts its entries before it is stored.

## Where backups land

| | |
|---|---|
| Host path | `/home/finder/htdocs/finder.opescare.cloud/storage/app/private/OpesCare Finder/` |
| Filename | `opescare_backup_YYYY-MM-DD-HH-MM-SS.zip` (host clock is UTC) |
| Owner / mode | folder `drwx------ finder:finder` (0700, private-visibility disk); archives inside are `0664` but unreachable by any other user because of the folder mode |
| Disk | `local` = `storage/app/private` (`config/filesystems.php`); the folder name is `APP_NAME` |
| Contents | `db-dumps/postgresql-<database>.sql.gz` (plain `pg_dump`, gzip) + the application tree |
| Excluded from the file part | `vendor/`, `node_modules/`, `.env*`, `bootstrap/cache/`, `storage/logs/`, `storage/framework/`, the backup folder itself and `storage/app/backup-tmp` |

Secrets are deliberately **not** in the archive (`.env`, `.env.bak-*`, and the cached
`bootstrap/cache/config.php`, which contains every env value in plain text). The archive is a plain
zip unless `BACKUP_ENCRYPTION_PASSWORD` is set. Keep the production `.env` in the ops password manager
— a restore needs it from there.

## Schedule (UTC)

| Time | Command | Purpose |
|---|---|---|
| 00:00 | `backup:clean` | apply the retention policy below |
| 01:00 | `backup:run` | database dump + application files |
| 09:00 | `backup:monitor` | health check: newest archive < 1 day old, disk usage < 5000 MB |

All three mail `BACKUP_ALERT_EMAIL` (success and failure). Check `systemctl list-timers | grep finder-scheduler`
before trusting any of them.

## Retention

`DefaultStrategy` in `config/backup.php` (`cleanup.default_strategy`):

- every archive is kept for **7 days** (7 nightly points plus any manual runs),
- then **one per day** for a further 7 days,
- then **one per week** for 4 weeks,
- then **one per month** for 3 months,
- no yearly archives,
- and never more than **5000 MB** in total on a disk (oldest removed first).

The newest archive is never deleted. The `cleanup.defaultStrategy` (camelCase) block is a mirror
read by `tests/Feature/Infrastructure/BackupConfigTest.php` — keep the two identical.

## Day-to-day commands (on the host, as `finder`)

```bash
cd /home/finder/htdocs/finder.opescare.cloud
A="sudo -u finder /usr/bin/php8.3 -d memory_limit=512M artisan"
$A backup:list                    # every destination, count, newest, size, health
$A backup:run --only-db           # ad-hoc DB-only archive (fast; do this before risky migrations)
$A backup:run                     # full archive (what 01:00 runs)
$A backup:clean                   # apply retention now
$A backup:monitor                 # health check now (mails on unhealthy)
ls -la "storage/app/private/OpesCare Finder/"
```

Log lines go to `storage/logs/laravel.log` (channel `single`). A healthy night shows no
`Scheduled command [...] backup:*` failure lines; `grep -n "backup" storage/logs/laravel.log | tail`.

## Restoring the database from a dump

1. Copy the archive off the host (as root: `scp root@187.124.48.135:"/home/finder/htdocs/finder.opescare.cloud/storage/app/private/OpesCare Finder/opescare_backup_<stamp>.zip" .`).
2. Unpack the dump:
   ```bash
   unzip -j opescare_backup_<stamp>.zip 'db-dumps/*' -d restore/
   gunzip restore/postgresql-*.sql.gz            # -> restore/postgresql-<database>.sql
   ```
   If `BACKUP_ENCRYPTION_PASSWORD` was set when the archive was made, `unzip` will prompt for it
   (`7z x` also works).
3. The dump is plain SQL with `CREATE TABLE` statements: restore it into an **empty** database,
   never over a populated one.
   ```bash
   sudo -u postgres createdb -O finder_app opescare_finder_restore
   sudo -u postgres psql -d opescare_finder_restore -f restore/postgresql-<database>.sql
   ```
4. To swap it in for the live database: `php artisan down`, stop `opescare-horizon` and the
   scheduler timer, rename the databases (`ALTER DATABASE ... RENAME TO ...`), `php artisan migrate --force`
   (brings the restore up to the current schema if the dump is older than the code),
   `php artisan up`. Test on the `_restore` copy first; the DB credentials are in the host `.env`
   (`DB_*`), not in the archive.

Uploaded files (patient photos, exports) are in the same zip under
`home/finder/htdocs/finder.opescare.cloud/storage/app/private/...` — extract and copy back into
`storage/app/private/` with `chown -R finder:finder`.

## Offsite copies (S3) — what the owner must supply

Local backups protect against application mistakes, not against losing the host. To add an
offsite copy the owner provides:

1. **A bucket** — private, "Block all public access" on, versioning on, in a region close to
   Cameroon/Europe (e.g. `eu-west-3` Paris or `eu-central-1`). An S3-compatible provider
   (Backblaze B2, Wasabi, Scaleway, OVH) works too via `AWS_ENDPOINT`.
2. **An IAM user for backups only**, with an access key, limited to that bucket:
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       { "Effect": "Allow", "Action": ["s3:ListBucket"], "Resource": "arn:aws:s3:::<bucket>" },
       { "Effect": "Allow", "Action": ["s3:PutObject", "s3:GetObject", "s3:DeleteObject"],
         "Resource": "arn:aws:s3:::<bucket>/*" }
     ]
   }
   ```
   (`DeleteObject` is what lets `backup:clean` apply retention offsite; drop it and set a bucket
   lifecycle rule instead if you prefer an append-only bucket.)
3. **An archive password** for `BACKUP_ENCRYPTION_PASSWORD`, stored in the ops password manager.
   Do this before the first offsite copy — the zip carries patient data.

### Env keys (host `.env`, edited as root after `cp -p .env .env.bak-$(date +%s)`)

```
AWS_ACCESS_KEY_ID=...          # from the IAM user
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=eu-west-3   # the bucket's region
AWS_BUCKET=<bucket>
AWS_ENDPOINT=                  # only for S3-compatible providers
AWS_USE_PATH_STYLE_ENDPOINT=false
BACKUP_ENCRYPTION_PASSWORD=... # recommended
BACKUP_S3_ENABLED=true
```

### Re-enabling `BACKUP_S3_ENABLED`

The `s3` disk also needs the Flysystem adapter, which the repo does not ship today.

1. In the repo (Flysystem is 3.x, so the adapter must be 3.x):
   ```bash
   composer require league/flysystem-aws-s3-v3:^3.0
   ```
   commit `composer.json` + `composer.lock`, and update
   `tests/Feature/Infrastructure/BackupConfigTest.php` / `phpunit.xml` so the test that expects
   `s3` in the destination list runs with `BACKUP_S3_ENABLED=true`.
2. On the host, as `finder`, from the app directory:
   `composer install --no-dev --no-interaction --prefer-dist` (composer 2 is at `/usr/local/bin/composer`).
3. Set the env keys above, then `sudo -u finder /usr/bin/php8.3 artisan config:cache && systemctl reload php8.3-fpm`.
4. Prove it: `backup:run --only-db --only-to-disk=s3`, then `backup:list` must show the `s3`
   row reachable with one archive. From then on `backup:run` writes to both disks; if S3 fails the
   local copy still lands (`continue_on_failure`) and a failure mail goes out.

To switch S3 off again: `BACKUP_S3_ENABLED=false`, `config:cache`. Existing offsite archives are
untouched until the bucket lifecycle removes them.
