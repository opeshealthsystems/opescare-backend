# Wave 2 — public pages contrast and layout (2026-10-19)

Method: headless Chrome (Playwright) against a local `php -S 127.0.0.1:8062` dev
server, EN and FR, at 390 px and 1440 px. For every visible text node the script
blends the text colour (with inherited opacity) over the stacked background colours
and computes the WCAG 2.x contrast ratio; threshold 4.5:1, or 3:1 for large text
(>= 24 px, or >= 18.66 px bold). It also flags elements that run past the viewport.

Pages: `/`, `/login`, `/care-map`, `/care-map/emergency`, `/care-map/facility/{id}`,
`/network/medicine-finder`, `/network/blood-finder`, `/solutions/{patients,hospitals,pharmacies}`,
`/help`, `/contact`, `/privacy`, `/terms`, `/signup`, `/signup/patient`.

Screenshots are the viewport after the fix (`<locale>-<width>_<page>.png`).

| Page | Finding (before) | Ratio before | Fix | After |
|---|---|---|---|---|
| `/login` | Demo-account role captions (`.demo-login-btn small`) dimmed with `opacity: .75` | 3.24:1 | `public/css/od-public.css`: `opacity: 1` on the muted caption | 5.3:1 |
| `/care-map` | Count badges in the filter chips (`.chip .n`, `opacity: .65`) | 4.24:1 | `resources/views/care_map/directory.blade.php`: opacity `.8` | > 4.5:1 |
| `/care-map` (390 px, mainly FR) | Card action row: "Appeler / Itinéraire / Voir le profil" did not fit and "Voir le profil" was clipped by the card | layout | `.card-actions` gets `flex-wrap: wrap` (primary button keeps `margin-left:auto`, so it drops to its own right-aligned row) | see `fr-390_care-map.png` |
| `/login` | Hard-coded English caption "Receptionist · Operations Desk" in the demo panel | i18n | new key `ui_sweep.login.receptionist_ops` EN/FR | — |
| `/privacy` | Header said version 2.0, footer 2.1 | content | both 2.1, last updated 19 Oct 2026 | — |

Checked and not changed (not failures):

| Item | Why |
|---|---|
| `/login` wordmark "CARE" (gradient text, `#1C6FB8 → #2B8FD9`) | Script cannot read clipped-gradient text. 40 px / 900 weight is large text; the lightest stop is ~3.5:1 >= 3:1. Logotype, also exempt. |
| `/login` "Sign in" button | White on the brand gradient (`#0F4C81 → #1C6FB8`); the script sees only `background-image`. Lightest stop ~5.2:1. |
| `/care-map` pager "Previous" on page 1 | `aria-disabled="true"`, opacity .4. Disabled controls are exempt from 1.4.3. |
| `/care-map` filter chips running past 390/1440 px | Intentional horizontal scroller (`.chips { overflow-x: auto }`), not page overflow. No page had `scrollWidth > viewport`. |
| `/care-map/emergency` at 390 px FR | "Appeler le 112" wraps to two lines inside the big red buttons; still readable and full-size targets, left as is. |

Not covered: hover/focus states, placeholder text, text over photos, the Leaflet
map tiles. FR number formatting on `/care-map` ("2,737" instead of "2 737") is
noted, not fixed here.
