# OpesCare — Production Go-Live Runbook

Single source of truth for shipping OpesCare to production. Work top to bottom;
do not skip the pre-deploy gate. Commands run from `apps/api-laravel` unless noted.

---

## 0. Sign-off gate (must all pass before deploy)

```bash
php artisan test                      # full suite — expect all green (800+/all)
php artisan config:assert-production  # exits 0 only if prod config is safe/complete
php artisan payments:smoke            # ✓ for every provider you intend to enable
php scripts/i18n-audit.php            # ✓ ALL FILES HAVE PERFECT 1:1 PARITY
php artisan route:list >/dev/null     # no exceptions = routes resolve
```

If any command fails, **stop** and fix before proceeding.

---

## 1. Secrets checklist (production `.env`, never committed)

| Key | Notes |
|-----|-------|
| `APP_ENV=production`, `APP_DEBUG=false` | `ProductionSafetyServiceProvider` throws if debug/demo on |
| `APP_KEY` | `php artisan key:generate` once; keep stable |
| `DB_*` | Postgres credentials |
| `REDIS_HOST/PORT/PASSWORD` | required: queue, cache, session, idempotency lock |
| `CACHE_STORE=redis` | **must** be redis (gate enforces) |
| `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis` | durable + supervised |
| `MAIL_MAILER` + SMTP creds | transactional mail |
| `SENTRY_LARAVEL_DSN` | error tracking; no-op until set |
| `MTN_MOMO_*` | Collections key — run `payments:smoke` after setting |
| `ORANGE_MONEY_*` | optional second provider |
| `DEPLOY_HEALTHCHECK_SECRET` | lets the health check bypass maintenance mode |

---

## 2. Deploy (automated via `.github/workflows/deploy.yml` on push to `main`)

The workflow runs tests, then on the host: `artisan down` (maintenance) →
`git pull` → `composer install` → tag `SENTRY_RELEASE` → **migrate** →
`config:assert-production` → cache config/routes/views/events → `queue:restart`
→ `horizon:terminate` → health-check → `artisan up`.

Manual equivalent (if deploying by hand): follow the same order. **Always migrate
before serving new code** (the workflow does this inside the maintenance window).

---

## 3. Stand up background workers (one-time per host) — see `deploy/README.md`

```bash
sudo cp deploy/systemd/opescare-*.{service,timer} /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now opescare-horizon.service
sudo systemctl enable --now opescare-scheduler.timer
php artisan horizon:status            # -> "Horizon is running."
systemctl list-timers | grep opescare # scheduler fires every minute
```

---

## 4. Post-deploy smoke test

```bash
curl -fsS https://<host>/up                 # Laravel health -> 200
curl -fsS https://<host>/api/health         # app/db health -> 200
php artisan horizon:status                  # running
```

Then log in to one account per tier and confirm the dashboard renders:
patient, staff (doctor), admin, insurance, lab, pharmacy, healthorg, developer, lite.

Sentry: trigger a test event and confirm it lands —
```bash
php artisan sentry:test
```

---

## 5. Rollback

```bash
# On the host, in maintenance mode:
php artisan down
git checkout <previous-green-tag>
composer install --no-dev --optimize-autoloader
# Roll back migrations ONLY if this release added destructive/incompatible ones:
# php artisan migrate:rollback --step=1
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan horizon:terminate
php artisan up
```

Prefer forward-fix over `migrate:rollback` for additive migrations. Tag every
production release (`v1.0.0-ga`, …) so rollback targets are unambiguous.

---

## 6. Backups & restore drill

```bash
# Nightly Postgres dump (cron or spatie/laravel-backup, already installed):
php artisan backup:run                       # spatie/laravel-backup
# Retention: keep >= 30 days off-host (e.g. object storage).

# Restore drill (staging) — prove backups are usable BEFORE you need them:
pg_restore --clean --no-owner -d opescare_restore_test <latest-dump>
php artisan migrate --force --env=restore_test
```

---

## 7. Definition of "100% production ready"

- [ ] Section 0 gate all green.
- [ ] All secrets set; `config:assert-production` exits 0 against prod `.env`.
- [ ] `payments:smoke` prints ✓ for every enabled provider.
- [ ] Sentry receiving events (test event seen).
- [ ] Horizon running + scheduler timer active.
- [ ] Post-deploy smoke (Section 4) passes for every portal tier.
- [ ] Backup taken + restore drill completed once on staging.
- [ ] Release tagged `v1.0.0-ga`.
