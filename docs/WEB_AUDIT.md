# Web platform audit — 2026-10-19

Server-rendered Laravel/Blade web app, crawled headless (puppeteer-core + Chrome)
against the local dev DB on `php artisan serve` (port 8011).

## Method
- **Routes:** `php artisan route:list --json --method=GET` gave 521 GET routes. Skipped
  `api/`, `fhir/`, `sanctum`, signed routes, logout, exports/downloads and resets,
  which left 320. Of those, the 238 without parameters were crawled once per role.
  Parametrised detail pages were reached through the link check below.
- **Roles (6):** anonymous, patient (`demo.patient`), pharmacist (`demo.pharmacist`),
  lab (`demo.labtech`), hospital admin (`demo.hospital.admin`), developer
  (`demo.developer`). All logins used the `/login` demo buttons. **Platform admin was
  not crawled:** `DemoAccessController::loginAs` refuses one-click login for every
  `RequirePlatformAdmin::PLATFORM_ROLES` account by design, and admin needs MFA locally.
- **Per page:** HTTP status, console errors, CSP violations (`Refused to …`), same-origin
  4xx/5xx sub-resources, broken `<img>`s, inline `on*=` attributes, raw
  translation keys in `innerText` (matched against every `lang/en/*.php` namespace),
  and horizontal overflow at 390×844.
- **Link check:** every same-origin `<a href>` collected for each role (144 to 254 per
  role) was re-fetched inside that role's session.
- **Visual check:** screenshots at 1366×768 and 390×844, EN and FR, for the public shell,
  the patient portal, the pharmacy desk and the facility-admin desk. Every element
  past the viewport edge was listed.
- **Static checks:** inline `on*=` handlers, `<script>` without `@cspNonce`, purple
  hex values, hardcoded English between tags, and `route('…')` names used in views
  against `route:list` (all resolve; the only two "misses" are a dynamic
  `'portals.pharmacy.orders.' . $step` and one already wrapped in `Route::has`).

## Results (1,428 page loads)
| Role | 200 | 403 | 401 | 404 | other |
|---|---|---|---|---|---|
| anon | 235 | 0 | 1 | 2 | 0 |
| patient | 169 | 66 | 1 | 2 | 0 |
| pharmacist | 140 | 94 | 1 | 2 | 1 (PDF download, aborted nav) |
| lab | 152 | 83 | 1 | 2 | 0 |
| hospital admin | 166 | 69 | 1 | 2 | 0 |
| developer | 168 | 67 | 1 | 2 | 0 |

- **0 × 500, 0 CSP violations, 0 inline handlers, 0 failing assets, 0 broken links**
  (1,113 links checked), and no phone-width page overflow.
- The 403s are role gates working as intended (admin/other-desk pages). The 401 is
  `/v1/prescriptions` (bearer API). The 404s are `/demo-access` and
  `/demo-access/public`, which 404 by design because `OPESCARE_PUBLIC_DEMO_MODE` is off.
- Every "raw key" hit was a false positive: demo e-mail local parts such as
  `demo.patient`, webhook event names such as `document.issued`, and the
  `api.opescare.health` host. No untranslated `namespace.key` reached a screen.
- `php scripts/i18n-audit.php` reported 0 errors before and after the fixes.
- One 500 storm during the first crawl attempt was Postgres in crash recovery on
  the host (≈400 databases being fsynced after a restart by another session), not
  an app bug. Nothing was changed for it.

## Issues and fixes
| Page | Issue | Fix |
|---|---|---|
| All `layouts.finder` pages (`/`, `/about`, `/care-map`, `/developers`, …), **FR** at every desktop width | The inline header nav needs about 1,650px in French and about 1,200px in English, but the header row was the 1,100px content column. In FR the nav overflowed the viewport by 157–706px (login button and language switch off-screen). In EN it overflowed at 961–1,280px. | `public/assets/finder.css`: inline nav, header login and burger breakpoint moved from 961px to 1300px. Header row widened to 1,320px there. In FR the burger panel stays until 1,760px, and the row is 1,720px above that. |
| Burger panel (phones, and FR desktop), "Connexion/Log in" CTA | `.f-nav-panel a` out-ranked `.f-btn--primary`, so the CTA showed dark ink text on brand blue, with the icon and label pushed apart. | `finder.css`: a scoped rule keeps the CTA centred with white text. |
| Staff desks (`layouts.portal`, `body.od`), **FR** | The sidebar brand line "par OPES Health Systems" was `nowrap` and ran under the content edge. | `public/css/od.css`: `.od-brand__by` wraps (line-height 1.25). |
| `/login` demo panel | Hardcoded English: the sandbox notice and the Staff / Facility / Patient / Developer tabs. | New `public.demo.tab_*` and `public.demo.sandbox_notice` keys (EN + FR). |
| `/demo-access/internal`, `/demo-access/public` | The "View public/internal demo" switch linked to a page that 404s when that demo mode is off (a broken link on every load). Labels such as "Email:", "Password:" and "Return to OpesCare" were hardcoded English. | The switch renders only when the target mode is enabled. Labels now use `public.demo.label_*` and `public.demo.return_home`. |
| `/patient` dashboard (and every page that redirects there) | `<img id="temp-qr-img" src="">` resolved to the page URL and counted as a broken image on 68 page loads. The JS sets `src` when a QR is issued. | `src=""` removed. |

## Tests
`tests/Feature/Web/WebAuditRenderingTest.php` covers four things: the FR demo
panel is translated, the internal demo hides the public-demo link when that mode
is off and shows it when it is on, and no Blade view ships `<img src="">`. These
tests and `tests/Feature/DemoAccessTest.php` ran on `opescare_test_a4` and passed
(9/9).

## Verification
- `php scripts/i18n-audit.php`: ALL FILES PASS.
- `php artisan view:cache`: OK, then `view:clear`.
- `php artisan route:list`: OK.

## Not done / follow-ups
- Platform-admin pages were not crawled (see Method).
- The lab demo account (`demo.labtech`) lands on `/select-facility`, so its crawl ran
  without a facility context.
- FR KPI titles on the facility-admin home are truncated with an ellipsis (for
  example "Identifiants de santé…"). They are readable but clipped. This is a
  copy-length issue, left alone.
- Pack-unit strings that come from stock data ("30 tablets") stay in English in FR.
  They are data, not UI.
