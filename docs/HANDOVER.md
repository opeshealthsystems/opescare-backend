# OpesCare Finder — Handover

**State of finder.opescare.cloud.** What is live, how to ship safely, what is waiting
on the owner, and where every decision is written down.

| | |
|---|---|
| Repo | `C:\laragon\www\opescare-finder` |
| Branch | `master` (no git remote — this machine is the only copy) |
| Head | `5d499021` |
| Commits | 164 |
| Routes | 912 |
| Tests | 2,385 |
| Memory | `~/.claude/projects/C--laragon-www-opescare-finder/memory/MEMORY.md` (47 facts) |

Prepared 15 September 2026, revised **12 October 2026** after the workspace cleanup.
Every "live" statement was verified on finder.opescare.cloud at the time it shipped.
`docs/HANDOVER.html` is the same content, styled for reading.

---

## What the Finder is — and is not

A Cameroon-first, bilingual (EN/FR) **discovery, identity and coordination** product:
patients find care; facilities stay findable and run their day-to-day interactions
with Health ID holders. **It is not a hospital information system or an EMR.**

The yardstick every change was held to is `docs/DASHBOARD_SCOPE.md` — findable,
reachable, counter service with consent, the team, aggregate numbers, money at the
counter. Clinical documentation, admission/discharge, procurement, HR and insurance
are **out of scope**, deliberately.

- **Stack:** Laravel 13, PHP 8.3, PostgreSQL. Production runs `APP_ENV=demo`, so the
  public demo logins are on.
- **Non-negotiables:** facility id only from the session/auth context; cross-facility
  access is a 404, not a 403; no patient name search (exact Health ID only); clinical
  rows are never deleted; **care is never gated on payment or the queue** (two tests
  enforce this); EN/FR strings stay 1:1.

## The three folders

| Folder | What it is |
|---|---|
| `C:\laragon\www\opescare-finder` | **this** — the Finder web app, the project you open |
| `C:\laragon\www\opescare-finder-mobile` | the Finder's Expo app, shipped via EAS. Add it to a session with `/add-dir` when you need it |
| `C:\laragon\www\opescare` | the platform monorepo (opescare.cloud). **Read-only from here** — code was imported from it, never edited in it |

They stay separate. This repo deploys by `git archive` of its own diff and carries its
own `APP_KEY` and JWT keypair; folding it into the monorepo would break both deploys
and cross secrets between two estates. `opescare-legacy`, a fourth folder, was a
duplicate platform checkout with no unique code and was deleted on 2026-10-12.

## What shipped

| Area | Now live |
|---|---|
| **Pharmacy** | Truthful dashboard, patient orders desk, whole-franc price list with history, settings with map and photo, team management. Seams fixed: dispense routing, stock decrement on collection |
| **Laboratory** | Dashboard with turnaround, patient requests, sample rejection, printable report, critical-value read-back, test menu published to the public lab-test finder |
| **Hospital / clinic / health centre** | Admin overview, staff roster, invoices, departments, reports with CSV export, wards and beds register, consistent facility resolution |
| **All facility types** | "My facility" settings, web referral desk, web immunisation desk, tasks and help desk, broadcasts, go-live readiness checklist |
| **Public** | Lab-test and imaging-exam finders; type-word search (*dentiste*, *maternité*, *ophtalmologue*…); dental prices on profiles |
| **Developer** | Fixed a production 500; webhooks (signed, replayable, internal addresses refused), activity log, quickstart, real analytics, consent view |
| **Imported from the platform** | Patient file attachments + radiology release, drug-interaction warnings, antenatal presets, KPI dashboard, security operations, DHIS2 aggregate reporting — file by file in `docs/PLATFORM_IMPORTS.md` |

Ten facility types have working dashboards and demo logins (`docs/DEMO_ACCOUNTS.md`,
password `demo1234`).

## Security

From the scan of 15 September. Evidence and per-item detail: `docs/SECURITY_REMEDIATION.md`.

| # | Item | State |
|---|---|---|
| 1 | Demo login could reach the platform admin tier | **Live** — demo accounts can never reach it; 403 verified on prod |
| 2 | Stored XSS through JSON-LD on public profiles | **Live** — all structured-data blocks hex-encode `<` `>` `&`; facility fields refuse angle brackets |
| 3 | MFA not required for platform admins | **Live** — `super_admin`, `platform_admin`, `system_admin` always require MFA |
| 4 | JWT keys committed to git | **Live, one owner step left** — keys untracked, paths configurable, history rewritten. See below |
| 5 | Webhook could reach internal addresses (SSRF) | **Live** — public addresses only, pinned connection, redirects off |
| 6 | Headers / session cookie / CSP | **Live, one owner step left** — duplicate headers and jsDelivr removed; `script-src` is now nonce-only with **no `'unsafe-inline'`** (2026-10-12: 141 inline handlers → `data-*` + `public/js/op-actions.js`, every inline `<script>` carries `@cspNonce`, tests enforce it). Host-only cookie still needs the prod `.env` change below |
| 7 | 31 known vulnerabilities in PHP packages (commonmark, guzzle, dompdf, flysystem, laravel) | **Live** — `composer audit` = 0 after the 2026-10-12 update (Laravel 13.35, Symfony 8). `vendor/` is tracked, so the deploy carries it |
| 8 | Stored XSS through legal-document HTML (rendered unescaped on the public legal pages) | **Live** — sanitised with HTMLPurifier at publish time *and* at render; `tests/Unit/LegalHtmlSanitizerTest.php` |

> **Commit hashes changed.** The history was rewritten to remove the keys, so hashes
> quoted in older docs do not resolve. **Find commits by message:**
> `git log --oneline --grep="demo accounts can never reach"`.

## Waiting on the owner

**1. Delete the null MX record at Hostinger.** It blocks *every* outbound email —
staff invites, lead-conversion invites, notifications. SPF, DKIM and DMARC are already
correct, so this one record is the whole problem.

**2. Finish the git history scrub.** `master` already carries zero `.pem` blobs
(`git rev-list master --objects | grep -c .pem` → 0). One leftover ref still pins the
old objects. Run in `C:\laragon\www\opescare-finder`:

```bash
git for-each-ref --format='%(refname)' refs/original | xargs -r -n1 git update-ref -d
git reflog expire --expire=now --all && git gc --prune=now
```

Safety net: `C:\laragon\www\_archive\opescare-finder-pre-scrub-2026-10-12\` holds a
verified complete bundle of both histories. **That bundle contains the old keys — do
not share it.**

**3. Make the session cookie host-only.** This logs everyone out once.

```bash
ssh -i ~/.ssh/opescare_deploy_ed25519 root@finder.opescare.cloud "cd /home/finder/htdocs/finder.opescare.cloud && cp -a .env /root/finder.env.bak && sed -i 's/^SESSION_DOMAIN=.*/SESSION_DOMAIN=/' .env && (grep -q '^SESSION_COOKIE=' .env || echo 'SESSION_COOKIE=__Host-finder_session' >> .env) && sudo -u finder /usr/bin/php8.3 artisan config:cache"
```

**4. Off-site backups.** Install the S3 driver and add keys — step-by-step instructions
are on the Backups page in the admin control center. Today backups exist on the server
only, which is not a backup.

**5. DHIS2 access from MINSANTE.** Needs an account on the national instance, each
facility's org unit id, the dataset id and the data element ids. Drafts already generate
on the 1st of each month; nothing is sent until these exist and a draft is approved.

## Decisions still open

- **New roles:** dentist, radiographer. Whether `lab_validator` is invitable, and
  whether lab roles may open patient files.
- **Prescribing:** nurse and midwife prescribing under national protocols;
  nurse-recorded malaria rapid tests.
- **Pharmacy:** whole francs on `/pharmacy/stock` (changes an existing test).
- **New surfaces:** diagnostic packages (two new tables); an imaging request desk over
  `imaging_orders`.
- **Data:** a real drug-interaction dataset — the imported one has only 4 pairs.
- **Platform:** USSD in v1 or v2; a `data_steward` tier; whether the same JWT keys are
  committed in the main platform repo (worth checking — that repo is read-only here).

Per facility type, with in/out-of-scope reasoning: `docs/FACILITY_READINESS.md` and
`docs/audits/`.

## Engineering backlog

- **CSP `'unsafe-inline'`** — externalise 141 inline handlers and 72 inline scripts,
  then switch to nonces. Do not rush this; done carelessly it breaks dashboard buttons.
- **Imaging and eye clinics** — a request desk; publish ICHI procedures (only LOINC
  publishes today).
- **Smaller:** the public facility profile has no photo slot although uploads work;
  directory search is not paginated in SQL; mobile search ignores type words.

## How to ship safely

There is **no CI deploy and no git remote**. This recipe shipped every release:

1. **Test on your own database** — another local project uses the default test DB.
   Stream results so a crash cannot hide:
   ```bash
   DB_DATABASE=opescare_finder_test /c/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe -d memory_limit=2G vendor/bin/phpunit --teamcity > suite.log 2>&1
   ```
   The full suite takes 15–35 minutes. `artisan test` buffers output, so a 0-byte log
   tells you nothing — that is why `phpunit --teamcity` is used.
2. **Checks:** `php scripts/i18n-audit.php` and `php artisan view:cache`.
3. **Commit by explicit path** and confirm the staged list before committing.
   **After any `composer update`/`require`, run `git add -f vendor`.** `/vendor` is
   git-ignored even though it is tracked, so `git add -A` stages modified and deleted
   vendor files but silently skips every *new* one — on 2026-10-12 that shipped a
   framework missing `Illuminate/Image/*` and the site 500'd until the files were added.
   Also keep `composer.json` → `config.platform.php` at prod's PHP (8.3) so the lock
   never resolves to packages prod cannot run.
4. **Ship changed files only:** `git archive` the diff → `scp` → compare md5 → extract
   in `/home/finder/htdocs/finder.opescare.cloud` → `chown -R finder:finder` →
   `rm -f bootstrap/cache/*.php` → `migrate --force` → `config:cache && route:cache &&
   view:cache`. Never ship `.env` or `storage/`; remove deleted files on the server by hand.
5. **Restart the queue worker** after job changes: `systemctl restart finder-queue.service`.
6. **Verify live** as a demo account. Login is throttled to 5/min; platform-admin pages
   need MFA and cannot be walked with curl.

**Traps that cost real time here:** a stale `config:cache` can point tests at the dev
database; six or more concurrent test runs crash Postgres, and an unclean stop triggers
a ~30-minute recovery; Windows PHP cannot read Git Bash's `/tmp`; `IsDemoRecord` hides
real rows on the demo host, which makes working features look broken.

## Where things are written down

| File | What it holds |
|---|---|
| `docs/DASHBOARD_SCOPE.md` | What the dashboards are and are not |
| `docs/FACILITY_READINESS.md` | Readiness per facility type |
| `docs/audits/*_AUDIT_2.md` | Ten per-type audits with plans |
| `docs/SECURITY_REMEDIATION.md` | Scan findings and fix status |
| `docs/PLATFORM_IMPORTS.md` | Every file imported from the platform |
| `docs/DEMO_ACCOUNTS.md` | All demo logins |
| `docs/LAUNCH_AUDIT_TRACKER.md` | Running launch tracker |
| `docs/ops-backups.md` | Backup operations |
| `docs/EXTRACTION.md` | How this repo was split from the platform |
| `routes/console.php` | Schedules: waitlist expiry, lab catalogue publish, monthly DHIS2 drafts |
| `finder-queue.service` | Queue worker (default, imports, webhooks) — a systemd unit **on the server**, not a file in this repo: `systemctl cat finder-queue` to read it |
