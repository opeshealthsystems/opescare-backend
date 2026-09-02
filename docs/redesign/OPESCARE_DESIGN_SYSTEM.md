# OpesCare Design System & Redesign Spec

**Status:** Authoritative — 2026-06-14. Source of truth for the platform UI redesign.
Grounded in the existing token system in `public/css/portal.css` (we EXTEND it).

---

## 1. Principles
- **One brand:** OpesCare navy/blue. Primary `#0F4C81`, light `#1A6AAF`, dark `#0A3560`, navy rail `#0F2744`, teal accent `#0F766E/#14B8A6`. No purple, ever.
- **Tokens, not hardcoded hex.** Every color/space/radius comes from a `--p-*` token. (Audit found 107 hardcoded hexes + 2,161 inline styles in admin — these get retired.)
- **One component kit.** Buttons, tables, pills, tabs, cards, modals are shared classes — not re-styled per page.
- **Lucide icons only**, never emoji.
- **Mobile-first.** Sidebar collapses to a drawer; grids reflow to one column; tap targets ≥44px.
- **Sentence case**, two font weights (400/500).

## 2. Tokens (already in portal.css `:root`)
Brand: `--p-primary`, `--p-primary-light`, `--p-primary-dark`, `--p-teal*`.
Semantic: `--p-danger*`, `--p-warning*`, `--p-success*`.
Surfaces: `--p-bg`, `--p-surface`, `--p-surface-2`, `--p-border`.
Text: `--p-text`, `--p-text-2`, `--p-text-muted`.
Sidebar: `--p-sidebar-bg`, `--p-sidebar-text`, `--p-sidebar-active-*`.
Spacing: `--p-space-1..12`. Radius: `--p-radius`, `--p-radius-sm`.
**Add if missing:** `--p-radius-lg: 0.75rem`, status-surface tints (see component kit).

## 3. Component kit (to add to portal.css — names are canonical)

### Buttons — replaces `btn`/`lite-btn`/`quick-action-btn`/inline
```
.opx-btn            base: inline-flex, gap .4rem, radius, 13px, padding .55rem .85rem, cursor
.opx-btn--primary   bg var(--p-primary), color #fff
.opx-btn--secondary bg var(--p-surface), border var(--p-border), color var(--p-text)
.opx-btn--danger    color var(--p-danger), border var(--p-border) (solid red only for confirm step)
.opx-btn--sm        smaller padding/font
.opx-icon-btn       square icon-only, transparent, hover bg surface-2 (needs aria-label)
```

### Status pills
```
.opx-pill                 11px, padding 2px 8px, radius-sm, inline-flex
.opx-pill--active/success  success tint bg + --p-success text
.opx-pill--pending/warning warning tint bg + --p-warning text
.opx-pill--info/onboarding info(blue) tint + --p-primary text
.opx-pill--danger/suspended danger tint + --p-danger text
.opx-pill--neutral         surface-2 bg + text-muted
```

### Data table
```
.opx-table         width100, border-collapse, 12–13px
.opx-table thead th surface-2 bg, text-muted, weight500, text-left, padding .5rem .65rem
.opx-table td       padding .55rem .65rem, border-top var(--p-border)
.opx-table--fixed   table-layout fixed (for narrow/mobile) + per-col widths
.opx-row-actions    right-aligned icon buttons
```

### Filter bar
```
.opx-filterbar   flex, gap .5rem, wrap, margin-bottom .75rem
.opx-search      flex-1, surface bg, border, radius, icon + input
.opx-filter      chip-style select (surface bg, border, chevron)
```

### Page header + breadcrumb
```
.opx-breadcrumb  11px text-muted, chevron separators
.opx-page-head   flex, title (h2 18px/500) + spacer + primary action
```

### Tabs
```
.opx-tabs       flex, border-bottom var(--p-border)
.opx-tab        padding .45rem .75rem, text-muted
.opx-tab--active border-bottom 2px var(--p-primary), color text, weight500
```

### Cards / surfaces
```
.opx-card     surface bg, 0.5px border, radius-lg, padding .75rem .9rem (raised/bounded)
.opx-stat     surface-2 bg, radius, padding .7rem; label 12px muted + value 21px/500 (metric tile)
.opx-panel    .opx-card + header row (title 14px/500 + optional action)
```

### Entity header (detail pages)
```
.opx-entity-head  flex, icon/avatar (40px, primary bg) + title+status + actions
.opx-field-grid   grid auto-fit minmax(150px,1fr), gap .6rem of .opx-stat read-only fields
```

### Modal (confirm/action flow)
```
.opx-modal__backdrop  normal-flow faux viewport: min-height, rgba(15,39,68,.45), flex center
.opx-modal            surface bg, radius-lg, border, padding 1.1rem 1.25rem, max-width 360px
                      header: alert icon + title; body 13px; required reason input; footer right-aligned
                      destructive confirm = solid --p-danger button; logs to admin action log
```

### Shell (already exists; standardize)
- Navy sidebar `--p-sidebar-bg`, grouped nav labels (10px uppercase muted), active item left-border `--p-sidebar-active-border`.
- Topbar: search + context chip (facility/global) + bell + avatar.
- **Mobile:** sidebar → off-canvas drawer toggled by `ti-menu-2`; backdrop; content single-column.

## 4. Visualized patterns (20 mockups) → coverage
| Pattern | Screens |
|---|---|
| Shell + overview | Dashboard, System health |
| List/table | Facilities, Orgs, Users, Staff, Patients, Invoices, Subscriptions, Plans, Certifications, Code mappings, Dev accounts, Production requests, Support, Legal |
| Detail + action-flow + modal | every approve/suspend/activate/reset/reject/revoke/publish/change-plan |
| Toggle grid | Control center, Feature flags, Settings & modules, Maintenance |
| RBAC matrix | Roles & RBAC |
| KPI dashboard | KPI & trends, Reports, MINSANTE, readiness scorecards |
| Finance overview | Financial, Payments |
| Audit explorer | Security ops, Audit explorer, Emergency access, Action log, Closures, Complaints, Minor transitions |
| Connect | Connect, Bridge, CDSS lists |
| Onboarding wizard | Onboarding, Go-live readiness |
| Subscriptions & plans | Billing detail, plan tiers |
| CDSS / code mappings | Clinical governance |
| Mobile responsive | every screen's phone view |
| Staff/doctor portal | clinical workspace |
| Patient portal (mobile) | patient app |
| Login + facility selector | auth + multi-facility selection |
| Empty / 403 / loading | shared states for every screen |
| Pharmacy / Lab / Insurance / HealthOrg / Developer / Lite | each account-type portal |

## 5. Per-portal sidebar accent (role badge tint only; rail stays navy)
admin/platform = primary blue; facility-tier = teal; otherwise neutral. (RBAC two-plane stays enforced by middleware — design never grants access, it only reflects it.)

## 6. Implementation order
1. Extend `portal.css` with the `.opx-*` component kit (additive; keep existing classes working).
2. Refactor section by section, replacing inline styles + hardcoded hex with kit classes + tokens. **Facilities first** (exercises table + detail + modal).
3. After each section: run `tests/Feature/Rbac/AdminPagesSmokeTest.php` (no 500s) + visual check, then next.
4. Never change routes/controllers' behavior during a visual refactor — views/CSS only, except where a controller must pass data the view needs.
