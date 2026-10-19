# Wave 2: staff portal visual consistency pass (2026-10-19)

**Crawl:** 9 demo staff roles: hospital admin, facility admin, doctor, nurse, receptionist,
pharmacist, pharmacy stock manager, lab tech, imaging tech. There is no blood-bank demo
user, so blood stock was covered through the receptionist and doctor sidebars
(`/facility/inventory/blood`). The crawl visited every sidebar link: **139 role×page
combinations × 4 widths (390/768/1280/1440) × EN/FR = 1,112 page views**. Probes:
- page overflow and elements past the viewport
- tables that do not stack or scroll at phone width
- unrendered Lucide icons
- one visible `h1` per page
- leaf-text truncation
- WCAG AA contrast, computed from styles on every text node

**Before (first pass):** 0 overflow, 0 unstacked tables, 0 broken icons and 0 error statuses.
There were about 760 AA contrast failures across 14 selector families, and 2 pages had no `h1`.
**After (re-crawl at 390 + 1280, EN/FR, 556 views):** 0 contrast failures and 0 missing `h1`.
The only remaining hit is `label.od-sr`, a visually hidden screen-reader label, which is a false positive.

| # | Role | Page | Issue | Fix | Screenshots |
|---|---|---|---|---|---|
| 1 | all staff | every desk page (sub-headings, KPI subs, counts, table heads, breadcrumbs) | Muted text `#6B7C93` was 3.8:1 on the canvas and 4.26:1 on white, below AA | Muted token changed to `#5B6B82` (4.8+:1) in `od.css`, `od-legacy.css`, `od-clinic.css`, `od-lab.css`, `od-pro.css`, `od-stock.css` | [before](01-muted-contrast-before.png) / [after](01-muted-contrast-after.png); FR doctor [before](01b-muted-contrast-doctor-fr-before.png) / [after](01b-muted-contrast-doctor-fr-after.png) |
| 2 | all clinicians | `/select-facility` (audit P2-8) | It rendered the public marketing shell | Moved to `layouts.portal` with an `od-page__head` + `od-card` list. Ids, hidden input and JS kept | [390 EN](02-select-facility-after.png), [1280 FR](02b-select-facility-fr-1280-after.png); before: audit P2-8 |
| 3 | pharmacist, pharmacy manager | `/pharmacy`, `/pharmacy/stock`, `/pharmacy/prescriptions` (audit P2-7) | All three pages shared the `<title>` "Pharmacy Portal" | Stock uses `page_heading_stock`, prescriptions uses `page_heading_rx` (EN/FR) | [after](03-pharmacy-title-stock-after.png) |
| 4 | public, security pages | `/verify/qr`, `/verify/doc`, `/verify/health-id`, `/verify/rx` | Pages loaded Google Fonts | Removed the googleapis/gstatic preconnects and stylesheet. Inter now comes from the self-hosted `od-public-pages.css`; Lucide was already local `js/lucide.min.js`. The pages stay standalone | [health-id](04-verify-health-id-local-fonts-after.png), [qr](04b-verify-qr-local-fonts-after.png) |
| 5 | admins | `/facility/team/overview` and every `od-pill` / `badge-*` | Ready, pending and urgent pills were 3.3 to 4.2:1 | Status hex values `#1E8E3E→#17723A`, `#B7791F→#8A5A12`, `#D93025→#B3261E` across the staff CSS (adds `--od-*-ink` tokens) | [before](05-status-pill-contrast-before.png) / [after](05-status-pill-contrast-after.png) |
| 6 | all | `/verify/health-id` footer | `#94A3B8` on `#F0F4F8` was 2.3:1 | Changed to `#5B6B82` | [before](06-verify-footer-contrast-before.png) / [after](06-verify-footer-contrast-after.png) |
| 7 | pharmacist, pharmacy manager | stock batches, adjustments, `os-pill`, `os-in`, `os-src` | Green and amber text below AA | Same shared token change as #5 (`od-stock.css`) | [before](07-stock-status-contrast-before.png) / [after](07-stock-status-contrast-after.png) |
| 8 | receptionist, doctor | `/facility/inventory/blood` blood-group tags, screening clear, Confirm button | Red tags 4.18:1; white on green 4.21:1 | Same shared token change (`od-clinic.css`) | [before](08-blood-tags-contrast-before.png) / [after](08-blood-tags-contrast-after.png) |
| 9 | pharmacist, pharmacy manager | `/pharmacy` | No `h1` | Added a visually hidden `h1` (`public.pharmacy_portal.page_heading`), so the layout is unchanged | — (not visible) |
| 10 | all | 403 refusals | Six `abort(403, '…English…')` literals | Changed to `errors.abort.*` keys in EN and FR (`EnsurePortalAccess`, `RequirePlatformAdmin`, `DemoAccessController` ×3, `PartnerOnboardingController`) | — |
