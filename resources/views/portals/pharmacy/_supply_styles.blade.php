{{--
    Supply-side screen styles — SHARED by every screen that feeds the public
    finders: pharmacy stock reporting, blood-bank entry, the staff
    appointment desk, facility team, and the claimed-listing editor.

    Lives under portals/pharmacy/ because that tree belongs wholly to the
    supply-side surface; include from any supply screen's @section('head'):

        @include('portals.pharmacy._supply_styles')

    Design contract: foundation tokens first (--f-* from finder.css), portal
    tokens (--p-*) as fallback, hex only as a last resort. These screens are
    used on a phone behind a counter — every control is a >=44px target.
--}}
<style>
/* ── Coverage widget ─────────────────────────────────────────── */
.fs-cov{background:var(--p-surface,#fff);border:1px solid var(--p-border,#E2E8F0);border-left:4px solid var(--f-brand,var(--p-primary,#0F4C81));border-radius:var(--p-radius-lg,.75rem);padding:1rem 1.25rem;margin-bottom:var(--p-space-6,1.5rem)}
.fs-cov__head{display:flex;align-items:center;gap:.5rem;font-size:.75rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--f-brand,var(--p-primary,#0F4C81));margin-bottom:.35rem}
.fs-cov__head .f-icon{color:inherit}
.fs-cov__count{font-size:.95rem;color:var(--p-text,#0F172A);font-weight:600}
.fs-cov__bar{height:8px;border-radius:999px;background:var(--p-surface-2,#F1F5F9);overflow:hidden;margin:.6rem 0 .4rem}
.fs-cov__fill{height:100%;border-radius:999px;background:var(--f-good,var(--p-success,#15803D));transition:width .3s ease}
.fs-cov__hint{font-size:.8rem;color:var(--p-text-muted,#64748B);margin:0}

/* ── Big search-your-catalog bar ─────────────────────────────── */
.fs-search{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:var(--p-space-4,1rem)}
.fs-search__box{position:relative;flex:1 1 240px;min-width:0}
.fs-search__box .f-icon{position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--p-text-muted,#64748B);pointer-events:none}
.fs-search__input{width:100%;min-height:48px;padding:.6rem .9rem .6rem 2.6rem;font-size:1rem;border:1.5px solid var(--p-border,#E2E8F0);border-radius:var(--p-radius,.5rem);background:var(--p-surface,#fff);color:var(--p-text,#0F172A)}
.fs-search__input:focus{outline:none;border-color:var(--f-brand,var(--p-primary,#0F4C81));box-shadow:0 0 0 3px var(--f-wash,rgba(15,76,129,.12))}
.fs-search select.filter-select{min-height:48px}
.fs-search .btn{min-height:48px}

/* ── Segmented availability control (2-tap update: tap status, tap save) ── */
.fs-seg{display:inline-flex;flex-wrap:wrap;gap:.35rem}
.fs-seg__opt{position:relative;display:inline-flex}
.fs-seg__opt input{position:absolute;inset:0;opacity:0;margin:0;cursor:pointer}
.fs-seg__opt span{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:.35rem .7rem;border:1.5px solid var(--p-border,#E2E8F0);border-radius:999px;font-size:.78rem;font-weight:650;color:var(--p-text-2,#334155);background:var(--p-surface,#fff);white-space:nowrap;cursor:pointer;-webkit-tap-highlight-color:transparent}
.fs-seg__opt input:focus-visible+span{outline:3px solid var(--f-brand,var(--p-primary,#0F4C81));outline-offset:2px}
.fs-seg__opt--good input:checked+span{background:var(--f-good-wash,#E7F4EE);border-color:var(--f-good,var(--p-success,#15803D));color:var(--f-good,var(--p-success,#15803D))}
.fs-seg__opt--warn input:checked+span{background:var(--f-warn-wash,#FBF0E5);border-color:var(--f-warn,var(--p-warning,#B45309));color:var(--f-warn,var(--p-warning,#B45309))}
.fs-seg__opt--danger input:checked+span{background:var(--f-danger-wash,#FBEAE7);border-color:var(--f-danger,var(--p-danger,#B91C1C));color:var(--f-danger,var(--p-danger,#B91C1C))}

/* ── Big touch targets on row controls ───────────────────────── */
.fs-tap{min-height:44px}
.fs-tap.btn,a.fs-tap{display:inline-flex;align-items:center;justify-content:center;gap:.4rem}
.fs-num{min-height:44px;font-size:1rem;max-width:9rem}

/* ── Blood group chip grid (mirrors the public finder's blood grid) ── */
.fs-bgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem}
.fs-bchip{position:relative;display:flex}
.fs-bchip input{position:absolute;inset:0;opacity:0;margin:0;cursor:pointer}
.fs-bchip span{flex:1;display:flex;align-items:center;justify-content:center;min-height:52px;border:1.5px solid var(--p-border,#E2E8F0);border-radius:.625rem;font-size:1.05rem;font-weight:750;color:var(--p-text-2,#334155);background:var(--p-surface,#fff);cursor:pointer;-webkit-tap-highlight-color:transparent}
.fs-bchip input:focus-visible+span{outline:3px solid var(--f-brand,var(--p-primary,#0F4C81));outline-offset:2px}
.fs-bchip input:checked+span{background:var(--f-danger-wash,#FBEAE7);border-color:var(--f-danger,var(--p-danger,#B91C1C));color:var(--f-danger,var(--p-danger,#B91C1C));box-shadow:inset 0 0 0 1px var(--f-danger,var(--p-danger,#B91C1C))}
.fs-bgroup-tag{display:inline-flex;align-items:center;justify-content:center;min-width:3rem;min-height:2.4rem;padding:.15rem .5rem;border:1.5px solid var(--f-danger,var(--p-danger,#B91C1C));border-radius:.625rem;background:var(--f-danger-wash,#FBEAE7);color:var(--f-danger,var(--p-danger,#B91C1C));font-size:1rem;font-weight:750}

/* ── Units stepper ───────────────────────────────────────────── */
.fs-stepper{display:inline-flex;align-items:stretch;gap:.4rem}
.fs-stepper button{width:52px;min-height:48px;border:1.5px solid var(--p-border,#E2E8F0);border-radius:var(--p-radius,.5rem);background:var(--p-surface-2,#F1F5F9);color:var(--p-text,#0F172A);font-size:1.4rem;font-weight:700;line-height:1;cursor:pointer;-webkit-tap-highlight-color:transparent}
.fs-stepper button:active{background:var(--p-border,#E2E8F0)}
.fs-stepper input{width:6rem;min-height:48px;text-align:center;font-size:1.15rem;font-weight:700;border:1.5px solid var(--p-border,#E2E8F0);border-radius:var(--p-radius,.5rem);color:var(--p-text,#0F172A)}
.fs-stepper input:focus{outline:none;border-color:var(--f-brand,var(--p-primary,#0F4C81))}

/* ── Publish-to-finder note ──────────────────────────────────── */
.fs-publish-note{display:flex;align-items:flex-start;gap:.5rem;padding:.6rem .8rem;border-radius:var(--p-radius,.5rem);background:var(--f-good-wash,#E7F4EE);color:var(--f-good,var(--p-success,#15803D));font-size:.82rem;font-weight:600;line-height:1.45;margin-top:.75rem}
.fs-publish-note .f-icon{flex-shrink:0;margin-top:.1rem}

/* ── Screening state chip ────────────────────────────────────── */
.fs-screen-ok{display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .6rem;border-radius:999px;background:var(--f-good-wash,#E7F4EE);color:var(--f-good,var(--p-success,#15803D));font-size:.72rem;font-weight:700}

/* ── Appointment desk actions ────────────────────────────────── */
.fs-act{display:flex;gap:.4rem;flex-wrap:wrap}
.fs-act form{display:inline-flex;margin:0}
.fs-act-btn{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;min-height:44px;padding:.45rem .85rem;border-radius:var(--p-radius,.5rem);border:1.5px solid transparent;font-size:.82rem;font-weight:700;cursor:pointer;-webkit-tap-highlight-color:transparent;background:var(--p-surface,#fff)}
.fs-act-btn--good{background:var(--f-good,var(--p-success,#15803D));border-color:var(--f-good,var(--p-success,#15803D));color:#fff}
.fs-act-btn--good:hover{filter:brightness(1.08)}
.fs-act-btn--muted{background:var(--p-surface-2,#F1F5F9);border-color:var(--p-border,#E2E8F0);color:var(--p-text-2,#334155)}
.fs-act-btn--muted:hover{border-color:var(--p-text-muted,#64748B)}
.fs-act-btn--danger{background:transparent;border-color:var(--f-danger,var(--p-danger,#B91C1C));color:var(--f-danger,var(--p-danger,#B91C1C))}
.fs-act-btn--danger:hover{background:var(--f-danger-wash,#FBEAE7)}
.fs-tel{display:inline-flex;align-items:center;gap:.35rem;min-height:44px;padding:.3rem .5rem;margin:-.3rem -.5rem;border-radius:var(--p-radius,.5rem);color:var(--f-brand,var(--p-primary,#0F4C81));font-weight:650;text-decoration:none;white-space:nowrap}
.fs-tel:hover{background:var(--f-wash,rgba(15,76,129,.08))}

/* ── Day grouping headers (today-first list) ─────────────────── */
.fs-day-head{display:flex;align-items:center;gap:.5rem;margin:var(--p-space-6,1.5rem) 0 var(--p-space-3,.75rem);font-size:.8rem;font-weight:750;letter-spacing:.06em;text-transform:uppercase;color:var(--p-text-2,#334155)}
.fs-day-head:first-of-type{margin-top:0}
.fs-day-head .f-icon{color:var(--f-brand,var(--p-primary,#0F4C81))}
.fs-day-head .fs-day-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.6rem;padding:.1rem .45rem;border-radius:999px;background:var(--f-wash,#E9F1F8);color:var(--f-brand,var(--p-primary,#0F4C81));font-size:.75rem}

/* ── Missing-phone callout (listing editor) ──────────────────── */
.fs-callout{display:flex;gap:.7rem;align-items:flex-start;padding:.9rem 1.1rem;border:1px solid var(--f-warn,var(--p-warning,#B45309));border-left-width:4px;border-radius:var(--p-radius,.5rem);background:var(--f-warn-wash,#FBF0E5);margin-bottom:var(--p-space-4,1rem)}
.fs-callout .f-icon{color:var(--f-warn,var(--p-warning,#B45309));flex-shrink:0;margin-top:.15rem}
.fs-callout strong{display:block;color:var(--f-warn,var(--p-warning,#B45309));font-size:.9rem;margin-bottom:.15rem}
.fs-callout p{margin:0;font-size:.82rem;color:var(--p-text-2,#334155);line-height:1.5}

/* ── Mobile: stack the segmented control & actions comfortably ── */
@media (max-width:640px){
  .fs-act{width:100%}
  .fs-act form{flex:1}
  .fs-act .fs-act-btn{width:100%}
  .fs-seg{width:100%}
  .fs-seg__opt{flex:1}
  .fs-seg__opt span{width:100%}
  .fs-num{max-width:100%}
}
</style>
