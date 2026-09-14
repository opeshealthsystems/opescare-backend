{{--
    Styles shared by the two "my facility" settings pages
    (portals/pharmacy/settings/index and portals/facility/settings/index).
    Include from @section('head').
--}}
@include('portals.pharmacy._supply_styles')
<style>
  .lst-badges{display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:var(--p-space-3)}
  .lst-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:999px;font-size:.72rem;font-weight:700}
  .lst-badge i,.lst-badge svg{width:12px;height:12px}
  .lst-badge--claimed{background:rgba(15,76,129,.1);color:#0F4C81;border:1px solid rgba(15,76,129,.25)}
  .lst-badge--good{background:var(--f-good-wash,#E7F4EE);color:var(--f-good,#15803D);border:1px solid var(--f-good,#15803D)}
  .lst-badge--warn{background:var(--f-warn-wash,#FBF0E5);color:var(--f-warn,#B45309);border:1px solid var(--f-warn,#B45309)}
  .lst-badge--unverified{background:var(--f-wash,#F1F5F9);color:var(--f-muted,#64748B);border:1px solid var(--f-line,#E2E8F0)}
  .lst-note{font-size:.8rem;color:var(--p-text-muted);line-height:1.55;margin-bottom:var(--p-space-4)}
  .lst-hours-table td{padding:.4rem .5rem;vertical-align:middle}
  .lst-hours-table .form-control{padding:.35rem .5rem;font-size:.8rem}
  .lst-check{display:inline-flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--p-text-2);white-space:nowrap}
  .ps-tenant{display:flex;gap:1.25rem;flex-wrap:wrap;font-size:.82rem;color:var(--p-text-2);margin-bottom:var(--p-space-4)}
  .ps-tenant strong{color:var(--p-text)}
  .ps-map{margin:var(--p-space-4) 0}
  .ps-coords{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--p-space-4)}
  .ps-title-icon{display:inline-flex;align-items:center;gap:.5rem}
  .ps-title-icon i,.ps-title-icon svg{width:22px;height:22px;color:#0F4C81}
  fieldset.ps-locked{border:0;padding:0;margin:0;opacity:.55}
  .ps-photo{display:flex;gap:var(--p-space-5);flex-wrap:wrap;align-items:flex-start}
  .ps-photo__frame{width:180px;height:180px;flex:0 0 auto;border:1px solid var(--f-line,#E2E8F0);border-radius:12px;background:var(--f-wash,#F1F5F9);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;overflow:hidden;color:var(--f-muted,#64748B);font-size:.75rem;text-align:center;padding:.5rem}
  .ps-photo__frame img{width:100%;height:100%;object-fit:contain;display:block}
  .ps-photo__frame i,.ps-photo__frame svg{width:28px;height:28px}
  .ps-photo__form{flex:1 1 260px;min-width:0}
  .form-error{color:var(--p-danger)}
</style>
