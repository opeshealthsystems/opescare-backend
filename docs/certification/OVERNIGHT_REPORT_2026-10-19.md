# Overnight report — 18→19 October 2026

Everything below is **live on https://opescare.com** (last deploy `1763f748`). Each
deploy went through `scripts/ops/deploy.sh` (live page checks + auto-rollback); each
change set passed its tests first.

## Shipped tonight

| Deploy | What |
|---|---|
| `e499da47` | Demo reset endpoint locked; anonymous Health ID check masked + every lookup logged; radiology ingest needs consent; blood writes need a client scope |
| `c166620e` | Facility scope fails closed |
| (env) | **opescare.com switched to production**, demo mode off; your records (VFLP + merged FWNP, two gmail accounts) kept as real |
| `590788b3` | **Wave 1:** public pages on the shared layout, search loading states, honest booking link, phone-stacking tables (≈175 staff tables), dead chrome removed, translated aria labels, 403 page with reason; auth hardening (mobile login status + MFA, single-use refresh, strict JWT, OTP before PIN, masked SMS logs); scale (bounding-box geo search, trigram + hot-path indexes, queued pushes, payment core tests); **provenance is now a real allow-list** |
| `66c1fb73` | **Wave 3:** patient + guardian portals to WCAG AA (464 checks, 0 findings after); no third-party fonts anywhere and CSP tightened; French number formatting; emergency buttons fit at 360 px; **measured scale** at 20k facilities / 100k patients (all hot paths index-backed, 0.04–4.3 ms DB time — see SCALE_READINESS.md); nearby medicine 13.7 → 4.1 ms; @capability memoised; query-count budgets on 14 key pages/endpoints |
| `1763f748` | **Wave 2:** contrast raised to WCAG AA across all staff desks (≈760 failures in a 1,112-view crawl → 0), public contrast fixes, privacy policy 2.1 naming real processors, unknown non-GET paths → 404, verify pages load nothing third-party |

Reports and evidence: `docs/certification/` (UI audit, pilot walkthrough, wave2-portal,
wave2-public screenshots, SCALE_READINESS.md), `docs/design/PLAY_STORE_READINESS.md`,
`MOBILE_AUTH_BRIEF.md`, `MOBILE_LOGO_BRIEF.md`, `ASSETLINKS_SETUP.md`.

## Only you can do (blocking the pilot / Play Store)

1. **Create the real platform admin** on opescare.com (MFA on first login) — nobody can approve hospital applications without it.
2. **Create a Play reviewer account** (verified patient, email login, no MFA) and put it in Play Console → App access. The old demo reviewer no longer works.
3. **Play Console forms:** Health apps declaration, Data safety (draft in the mobile repo `playstore/listing/data-safety.md`), content rating, financial features, audience 18+, Play App Signing.
4. **Store listing wording:** "identifies you at any facility" / "national" directory implies government backing — reword unless MINSANTE authorises it.
5. **Namecheap:** add the 3 mail records (SPF edit, DKIM, DMARC) so mail sends as noreply@opescare.com.
6. **Play App Signing SHA-256** → then `public/.well-known/assetlinks.json` per `ASSETLINKS_SETUP.md`.
7. **Hand the app session** `MOBILE_AUTH_BRIEF.md`, `MOBILE_LOGO_BRIEF.md`, `PLAY_STORE_READINESS.md` (push permission vs. disabled push is a Play blocker).
8. **Decide one owner for git + deploys** — another session rewrote history mid-deploy tonight.

## Honest limits

- **Not certified for 100,000 hospitals / 1B people.** Database query plans were measured at 20k facilities / 100k patients and are index-backed; no HTTP load test ran (k6 is not installed here; never point it at production). `SCALE_READINESS.md` lists what that scale needs (multiple app nodes, managed Postgres + replicas, Redis cluster, object storage, CDN, k6 script provided).
- **Phone app** not edited (another session owns it) — briefs written instead.
- **Off-site backups** still wait on S3 keys; nightly on-server backups + weekly restore test run.
- **Local Postgres** crashed tonight: this machine holds ~300 test databases from other projects (opesinsure, timberhub, fecapme, meritra), making restarts take ~1 hour. Clearing them is your call.
- Your decisions still open: pending-patient login (web blocks, app allows); OpenStreetMap tiles are the one remaining third-party request (self-host or keep); nginx access logs keep search coordinates (drop `$args` from the log format to claim "never logged").
