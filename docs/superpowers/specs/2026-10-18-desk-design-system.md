# OPES desk design system (2026-10-18) — the contract every agent builds to

The owner rejected the 2026-10-13 restyle as generic. The bar is now pixel-level
fidelity to three references:

- `assets/2026-10-18-pharmacy-dashboard-reference-2.webp` (pharmacy, primary)
- `assets/2026-10-13-pharmacy-dashboard-reference.webp` (pharmacy, tablet frame)
- `assets/2026-10-18-lab-dashboard-reference.webp` (laboratory / diagnostic)

**Open and study the images before writing a line.** Measure from them. Compare
your screenshot to them side by side before you report. "Close enough" is a fail.

## What made the previous pass generic (do not repeat)
- Default system font, inconsistent sizes, ALL-CAPS labels, thin 1px borders on
  everything, coloured left borders on cards, KPI tiles 6-wide and cramped.
- Tiny buttons, mixed button styles, ghost text links where the reference has a
  solid button, Lucide icons at 16px where the reference uses 22-28px glyphs.
- Colour collisions: amber + red + blue + green tints all on one card; badges that
  repeat the card colour; purple anywhere.
- Bare tables with no avatars, no row numbers, no checkboxes, no kebab.

## Foundations
| Token | Value |
|---|---|
| Font | **Inter** (self-host `public/fonts/inter/*.woff2`, weights 400/500/600/700/800; `font-feature-settings: "cv11","ss01"`; never load from Google at runtime — CSP is `font-src 'self' fonts.gstatic.com` so self-host is safest) |
| Canvas | `#EEF3F9` |
| Card | `#FFFFFF`, radius **16px**, no border, shadow `0 1px 2px rgba(16,42,77,.04), 0 8px 24px rgba(16,42,77,.06)` |
| Primary | `#0F4C81` (buttons, active, links); hover `#0B3C68`; bright accent for the sidebar active pill `#1F6FD1` → `#1A5FB8` gradient |
| Sidebar | gradient `#0E3E73` → `#0A2E57`, width **256px**, item height **48px**, radius 12px, icon **22px** stroke 1.75, label 15px/500 white 88% |
| Ink | heading `#0F2340`, body `#1F3654`, muted `#6B7C93`, faint `#9AA9BC` |
| Lines | table rule `#EEF2F7`, input border `#DCE4EE` |
| Status pairs (bg / fg) | urgent `#FDECEC`/`#D93025` · normal `#E8F0FE`/`#1A5FB8` · pending `#FFF3DC`/`#B7791F` · ready/validated `#E6F4EA`/`#1E8E3E` · verifying/processing `#E8F0FE`/`#1A5FB8` · counselling/STAT `#E6F4F1`/`#0F7B6C` (teal — **never purple**) · low `#FDECEC`/`#D93025` · reorder `#FFF3DC`/`#B7791F` |

## Type scale (px / weight)
Page greeting name 30/800 · section title 20/700 (count in 400 muted) · KPI label
15/600 · KPI value 32/800 · KPI sub 13/500 · table head 13/600 muted, sentence
case · table body 14/500 · sub-line 12/500 muted · button 14/600 · pill 12/600 ·
sidebar 15/500. **No uppercase labels except the sidebar section headings** (11/700,
letter-spacing .08em, `#8FB1D9`).

## Components (class names are the contract)
- `.od-shell` layout: sidebar + topbar + content (max-width none, padding 24px, gap 20px)
- `.od-topbar`: 72px; search pill 48px tall radius 12px border `#DCE4EE` with left
  search icon and right **barcode-scan** icon button; bell 40px with red count dot;
  facility switcher (building icon + name + chevron, bordered 44px); user block
  (40px avatar circle, name 15/700, role 13 muted, chevron).
- `.od-brand`: product lockup in the sidebar head — mark + "OPES" 26/800 white +
  product word ("PHARMACY" / "LAB") in `#4FA3FF` + "by OPES Health Systems" 11px.
  The mark is a **custom inline SVG** per product (caduceus-in-ring for pharmacy,
  microscope-in-ring for lab) — no emoji, no Lucide here.
- `.od-kpi`: card 116px tall, padding 20px; icon circle **64px** filled with the
  pale tint and the glyph 28px in the strong colour; label / value + trend chip
  (`↗ +12%` green 14/700) / sub-line. Four across. One colour per tile.
- `.od-card` + `.od-card__head` (title 20/700 + count + right-aligned `View all`
  link 14/600 primary or icon button) + body.
- `.od-tabs`: pill tabs 40px tall, radius 10px; active = solid primary white text;
  inactive = white with `#DCE4EE` border, coloured 8px dot + label + count.
- `.od-table`: checkbox column, `#` column, avatar initials circle **36px** (pale
  tint from a fixed 6-colour rotation by name hash: blue, amber, teal, rose, sky,
  green — never purple), name 14/600 + id sub-line, pills, primary row button
  **`.od-btn--row` 36px tall, 96px wide, solid `#0F4C81`, radius 8px**, kebab 32px.
  Row height 56px. Selected row `#F2F7FD`.
- `.od-btn` 44px primary/secondary/ghost; `.od-btn--icon` 44px square.
- `.od-quick`: tiles 120px tall, radius 14px, solid primary (pharmacy) or white
  bordered with primary glyph (lab), glyph 30px, label 14/600, F-key hint 12px.
- `.od-detail`: patient header (64px avatar, name 20/700, ref • date • time),
  fact row with icons, verified chip, inner pill tabs, items table, prescriber
  cards with 48px icon circles.
- `.od-list`: alert rows with 32px tinted icon square, text + sub-line, right value
  + pill.
- `.od-donut` (lab workload): SVG donut 160px, centre total 32/800, legend rows.
- `.od-empty`: 48px tinted icon circle, title 16/700, one sentence, one button.

## Icons
Lucide is allowed **only** at 22-28px with stroke 1.75 for UI glyphs. Domain
glyphs the references show (pill bottle, prescription Rx, mortar, test tube,
microscope, barcode, analyser, fridge thermometer, caduceus) go in
`resources/views/components/od-icon.blade.php` as hand-drawn inline SVG, 24×24,
stroke `currentColor` 1.75, round caps, so they sit in the same visual family.

## Rules that still apply
EN/FR via `__()`, 1:1 lang, `php scripts/i18n-audit.php` clean; nonce CSP (no
inline `on*=`, `<script @cspNonce>`, data-* via `op-actions.js`); no inline
`style=`; facility from the portal context; never invent data — every number on
a dashboard comes from an existing query, an empty state when there is none;
XAF; every link goes to a real route.

## Owner directive 2026-10-18 — ONE flat card, everywhere
Every card on every desk page AND the landing page is identical: same background
(`#FFFFFF` on canvas `#EEF3F9`), same radius (16px), same padding (20px), same
title font (Inter 20/700 `#0F2340`), same body font (Inter 14/500 `#1F3654`).
**No 3D: no box-shadow, no drop shadow, no inset, no gradient, no bevel, no
coloured or thick borders, no hover lift/translate on cards.** Separation comes
from the canvas colour only; at most a 1px `#E6ECF3` hairline where two white
surfaces touch. KPI tiles, quick-action tiles and detail panels follow the same
rule (quick-action tiles may be solid `#0F4C81` but flat). Buttons are flat too
(no shadow). The sidebar gradient in the reference stays (it is a surface, not a card).
