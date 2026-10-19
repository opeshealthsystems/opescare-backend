# Wave 3 — patient and guardian portal certification (2026-10-19)

Crawl: every sidebar link for three demo accounts, at 390 / 768 / 1280 / 1440 px, in EN and FR,
on a local dev server. Checks per page: HTTP status, exactly one `<h1>`, horizontal overflow,
WCAG AA text contrast (4.5:1, 3:1 large), raw translation keys, broken icons/images,
third-party requests. Raw data: `results-before.json`, `results-after.json`. Screenshots: a
representative subset in `shots-before/` and `shots-after/` (the full set was 47 MB).

Note: the "before" pass ran after the `od-patient.css` grey/green/amber/red swap had landed,
so it captures what was still failing after that swap.

| Role | Pages | Checks | Before | After |
|---|---|---|---|---|
| demo.patient | 21 | 168 | 21 pages: notification badge 4.23:1; /patient/dependents label 4.27:1 | 0 findings (one transient 30 s timeout at 768 px EN, page passed at the other 7 sizes) |
| demo.guardian | 16 | 128 | badge 4.23:1 on every page; dependents label | 0 findings |
| demo.grandmother | 21 | 168 | same as patient | 0 findings |

All pages: status 200, one h1, no overflow, no raw keys, no broken icons, in both locales.

## Fixes
| Finding | Fix |
|---|---|
| Portal greys/greens/ambers/reds below AA | `public/css/od-patient.css`: #6B7C93→#5B6B82, #1E8E3E→#17723A, #B7791F→#8A5A12, #D93025→#B3261E (16 occurrences, same values as the staff desks) |
| Bell badge white on #E5383B (4.23:1) | `public/css/od.css`: badge background #B3261E |
| Guardian "Go to" label #64748B on wash (4.27:1) | `portals/guardian/dashboard.blade.php`: #5B6B82 |
| FR counts shown as "2,737" | `App\Support\LocaleNumber::format()` + `@num()` Blade directive; FR uses a no-break space ("2 737"). Used on the care-map directory count, landing count/meta, and FCFA prices on medicine/lab/imaging finders and facility profiles |
| "Appeler le 112" wrapped inside half-width buttons | `care_map/emergency.blade.php`: label is nowrap; buttons stack full width below 520 px. Verified at 360 px FR: 64 px tall, no clipping |
| Google Fonts on docs/public/demo layouts, care-map admin, and an `@import` in helix-v2.css | Replaced with self-hosted `public/css/od-fonts.css` (Inter 400–800); Outfit → Inter; JetBrains Mono / Fira Code fall back to system monospace. CSP `style-src`/`font-src` no longer allow fonts.googleapis.com / fonts.gstatic.com |

Rendered HTML of `/`, `/care-map`, `/care-map/directory`, `/demo-access`, `/docs` contains no
`fonts.googleapis`, `gstatic` or `cdn.` references.

## Still open
- The patient dashboard map loads tiles from `tile.openstreetmap.org` (the only third-party
  request in the crawl). Self-hosting tiles is a separate decision.
