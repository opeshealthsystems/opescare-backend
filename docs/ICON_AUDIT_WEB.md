# Web icon audit: no glowing icons (2026-10-19)

**Owner requirement:** remove the glow from every icon in the platform. An icon
is now a plain Lucide glyph in its own colour, with no tinted circle or
rounded-square chip behind it, no box-shadow, drop-shadow or text-shadow halo,
and no gradient blob. The glyph grows into the space the chip used: nav and
sidebar 20px, list rows and cards 24px, KPI tiles 28px, empty states 40px.

## How it is enforced
- **`public/css/icons-plain.css`** is the single authority. It lists every icon
  chip class found in the sweep. On each one it sets `background`, `box-shadow`,
  `filter`, `border`, `border-radius` and `padding` to none, sets the chip's size
  to `auto`, and resizes the glyph inside it. Every rule is `!important`, so it
  beats the per-page `<style>` blocks in views (`layouts/auth`, `layouts/demo`,
  `_finder_head`, `care_map/claim`, `demo/*`, `register/organization`). It never
  matches when the chip class sits on an `<svg>` itself (`:not(svg)`). Without
  that guard, `width:auto` blew up the topbar facility glyph.
- It is `@import`ed at the top of every base stylesheet, so every layout gets it
  without editing layouts that other sessions have uncommitted changes in:
  `od.css od-legacy.css helix-v2.css od-public.css od-public-pages.css auth.css docs.css
  login-page.css landing.css landing-v2.css public.css portal.css pharmacy.css admin.css
  od-pharmacy.css od-stock.css od-clinic.css od-lab.css od-patient.css od-pro.css od-admin.css`
  and `public/assets/finder.css`.
- Local fixes:
  - `od-patient.css` `.ods-a425013` (family invite icon). This rule was itself
    `!important`, so it was rewritten to plain.
  - The brand-mark drop-shadow was removed in `login-page.css` (`.lg-mark img`)
    and `auth.css` (`.f-auth-seal__mark`).

## Grep evidence
| Pattern | Scope | Result |
|---|---|---|
| `(icon\|ico)…{…background: tint/#E-#F/rgba}` | public/css, finder.css | 42 rules remain in source. Every one is a class in the icons-plain.css list, so all are neutralised at runtime. They were left in place to keep the diff against other sessions small. |
| `drop-shadow\|text-shadow` | public/css, finder.css | 1 remains: `docs-v2.css .dx-hero-seal`. That file is another session's untracked work, so it was not touched. It is a brand seal image, not an icon. |
| icon wrapper with inline `style="…background…"` before `<i data-lucide>` | resources/views | 0 |
| `<style>` icon chips with background | resources/views | 8 files. All classes are covered by icons-plain.css. `verify/document.blade.php .doc .icon` is kept as verifier security artwork. |

## Deliberately kept
- Buttons' own fills: `.od-btn--icon`, `.icon-btn`, `.btn-icon`, `.topbar-icon-btn`, `.os-icon-btn`.
- Input adornments.
- Photos, avatars and initials. `.odc-ico-avatar` is the exception: it is an icon-only tile, so it is stripped.
- The Health ID card (`.hc__logo`), the verifier pages and their security artwork.
- Text status pills and badges, and Leaflet markers.
- The sidebar brand tile, which is a logo, not a glyph.

## Captures (1366x768 `-d`, 390x844 `-m`)
`docs/visual-qa/web-icons-{before,after}-{landing,login,search,facility,patient,pharmacy,admin}-{d,m}.webp`
cover 7 screens × 2 viewports × before/after. Admin is the facility-admin desk;
pharmacy is the pharmacist desk at /pharmacy/stock. In the after set, KPI,
empty-state and list glyphs show with no chip, and the Health ID card is unchanged.

## Checks
- `scripts/i18n-audit.php`: ALL FILES PASS. No strings changed.
- `view:cache` ran, followed by `view:clear`.
- `tests/Feature/Web` on `opescare_test_a4`: 4 passed.

**Cache note:** `@import` does not inherit the `?v=` cache-buster of the file
that imports it. After a deploy, browsers revalidate icons-plain.css by their
normal HTTP caching.
