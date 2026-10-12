# Production readiness audit — 2026-10-12

Read-only audit across every dashboard, the shared design system and security (five
parallel passes), followed the same day by a fix pass. Gates after the fix pass:
`php artisan test` (see commit message for the count) · `i18n-audit` PASS (1:1) ·
`view:cache` PASS · `composer audit` **0** · `npm audit` 0.

## Verdict

**Security: certifiable** for the items a code audit can see — no data route without
auth, facility identity never from request input, uploads type-sniffed and private,
nonce-only script CSP, zero known-vulnerable packages, legal HTML sanitised, session
cookie encrypted/httponly/secure. Two owner steps remain (host-only cookie on prod,
finish the git history scrub — commands in `HANDOVER.md`).

**Product completeness: not yet** — imaging has no way in (no `ImagingOrder` creation,
no web radiology report), clinics have no web referral desk, health centres have no web
immunisation desk. These are features, not defects; they are listed under "Still open".

**Styling: consistent but not unified** — purple is gone, every status page is branded
and bilingual, 1,293 dead English fallbacks removed; 1,068 inline `style=` attributes and
three button-class families remain as a design-system consolidation job.

## Fixed in the 2026-10-12 pass

| Area | What changed | Where |
|---|---|---|
| Dependencies | 31 advisories → 0: commonmark 2.8→2.10, guzzle 7.11→7.15, psr7, dompdf 3.1.6, flysystem 3.36, Laravel 13.15→13.35 (Symfony 8) | `composer.lock` |
| CSP | `script-src 'self' 'nonce-…'`, **no `'unsafe-inline'`**; 61 inline `<script>` → `@cspNonce`; 141 inline `on*=` handlers → `data-*` dispatched by one delegated script; 4 tests fail the build if either returns | `AddSecurityHeaders.php`, `AppServiceProvider.php`, `public/js/op-actions.js`, `ContentSecurityPolicyTest.php` |
| Stored XSS | Legal document HTML sanitised with HTMLPurifier at publish *and* render | `LegalHtmlSanitizer.php`, `LegalDocumentService.php`, `public/legal/show`, `admin/legal/show` |
| Mass assignment | `ClinicalCodeDictionary` `$guarded=[]` → explicit `$fillable` | `app/Models/ClinicalCodeDictionary.php` |
| Config | `.env.example` `SESSION_SECURE_COOKIE=true` | `.env.example` |
| Supply chain | unpinned `unpkg.com/lucide@latest` → self-hosted | `care_map/admin_dashboard.blade.php` |
| Brand | purple/indigo removed from auth, landing, docs, Redoc theme, maintenance page, demo cards; dead `.demo-btn-insurance` deleted | `auth.css`, `landing.css`, `docs.css`, `layouts/docs`, `docs/playground`, `errors/maintenance`, `layouts/demo` |
| Error pages | one branded, bilingual layout for 401/402/403/404/419/429/500/503; stock Laravel `layout`/`minimal` deleted; `lang` follows locale; favicon everywhere | `errors/_page.blade.php`, `lang/*/errors.php` |
| i18n | 1,293 unreachable `__('…') ?: 'English'` fallbacks stripped (every key verified present in EN **and** FR); `lang/fr/triage.php` accents restored (≈60 strings); 222 named-month `format()` → `translatedFormat()` with Carbon locale synced in `SetLocale` | views, `lang/fr/triage.php`, `SetLocale.php` |
| Scope | "Insurance Claim" access purpose removed from the staff desk; DHIS2 reporting recorded in `CLAUDE.md` as deliberate; dead `welcome.blade.php` deleted | `portals/staff/index`, `CLAUDE.md` |
| Lab / diagnostic (D-2) | imaging exclusion is now a model scope used by both the public lab finder and the lab manager's "published" count, so the two numbers agree | `LabTestAvailability::scopeExcludingImaging` |
| Pharmacy | Controlled-substances tab title; dispensed-at dates localised | `pharmacy/controlled`, `prescription_lookup` |
| A11y | icon-only delete/close buttons labelled (EN/FR) | `admin/appointments/index` |

Browser-verified on the dev server with the demo doctor and pharmacist: login,
facility picker (delegated click), blood-bank modal and steppers, pharmacy desk ×4,
admin 404 page — **0 console errors** under the nonce policy.

## Re-checked and found already correct (earlier audit docs are stale here)

- Pharmacy `dispense()` goes through `PharmacyDeskService`, which 404s a script routed to
  another pharmacy — not an open IDOR. The session-based facility lookup is the shared
  `PortalContextService` pattern the invariant allows.
- DHIS2 has a nav link (`_admin_nav.blade.php:208`); it was never URL-only.
- Clinic sidebar hides Wards unless the facility type admits patients (C-5); a role
  refusal is a 403 that names the reason (C-6); dental appointment type exists (D-4).
- Doctors/nurses seeing Blood inventory is a **deliberate** grant pinned by
  `StaffRoleMatrixTest` — change only as an owner decision.
- Status columns compared as strings (`'dispensed'`, `'validated'`…) are plain string
  columns on those models, not backed enums — not a violation of the enum rule.

## Still open

**Owner actions** — host-only session cookie on prod; finish the git history scrub
(`refs/original` + reflog + `gc`); Hostinger null MX; off-site backup keys.

**Features** — imaging order intake + web radiology report (I-5, I-6) and an imaging
role (I-4); clinic web referral desk (C-3); health-centre immunisation desk (HC-2) and a
page behind the "Pending Referrals" tile (HC-3); prescription-issued notification (C-4);
stale-edit guard on `FacilitySettingsController` (D-5).

**Design system** — 1,068 inline `style=` (worst: `patient/index` 50, `public/status` 36,
`public/privacy` 33, finders ~30 each); 6 layouts / 7 stylesheets / `btn--primary` vs
`f-btn` vs `btn-primary`; `--p-primary` vs `--color-primary`; duplicated modal JS in 7
admin views; `style-src 'unsafe-inline'` (CSS cannot execute code — a cleanliness item).

**Code quality** — 9 controllers over 900 lines (`MobileResultsInboxController` 1,726);
`.env.testing` ships a real-format `APP_KEY` (test-only).

**Not yet audited** — full IDOR sweep of every patient endpoint; N+1 queries; colour
contrast; unlabeled inputs; payment-callback signature verification; a phone-width
walk of every dashboard.
