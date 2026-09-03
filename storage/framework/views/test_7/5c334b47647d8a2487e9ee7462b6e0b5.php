
<link rel="stylesheet" href="<?php echo e(asset('assets/finder.css')); ?>">
<style>
/* ---- page scaffold ---- */
.pf{max-width:720px;margin-inline:auto;width:100%}
.pf--wide{max-width:1060px}
.pf--form{max-width:640px}
.pf-head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:20px}
.pf-head h1{font-size:1.375rem;line-height:1.2;font-weight:800;letter-spacing:-.015em;margin:0;color:var(--f-ink)}
.pf-sub{margin:4px 0 0;color:var(--f-muted);font-size:.9375rem}
.pf-stack{display:grid;gap:12px}
.pf-section{margin-bottom:24px}
.pf-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.pf-card-head{display:flex;align-items:center;gap:8px;font-weight:750;font-size:1rem;margin:0 0 12px;color:var(--f-ink)}
.pf-card-head .f-icon{color:var(--f-brand)}
.pf-card-head .f-badge{margin-left:auto}

/* ---- alerts (page-level notes; flash bars belong to the shell) ---- */
.pf-alert{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:var(--f-r);font-size:.9375rem;background:var(--f-wash);color:var(--f-brand-ink);margin-bottom:16px}
.pf-alert--good{background:var(--f-good-wash);color:var(--f-good)}
.pf-alert--warn{background:var(--f-warn-wash);color:var(--f-warn)}
.pf-alert--danger{background:var(--f-danger-wash);color:var(--f-danger)}
.pf-alert .f-icon{margin-top:2px}
.pf-alert p{margin:0}

/* ---- appointment cards ---- */
.pf-appt{display:flex;gap:14px;align-items:flex-start}
.pf-appt__date{flex:none;width:58px;border-radius:var(--f-r);background:var(--f-wash);color:var(--f-brand-ink);text-align:center;padding:8px 4px}
.pf-appt__date .d{font-size:1.375rem;font-weight:800;line-height:1.15}
.pf-appt__date .m{font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
.pf-appt__body{flex:1;min-width:0}
.pf-appt__title{font-weight:750;color:var(--f-ink);display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center;margin:0}
.pf-appt__meta{display:flex;flex-wrap:wrap;gap:4px 16px;margin-top:6px;color:var(--f-muted);font-size:.875rem}
.pf-appt__meta span{display:inline-flex;align-items:center;gap:5px;min-width:0}
.pf-appt__actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.pf-badge--muted{background:var(--f-line);color:var(--f-muted)}

/* ---- tappable rows (messages, notifications) ---- */
.pf-rows{display:grid;gap:10px}
a.pf-row{display:flex;align-items:center;gap:12px;min-height:60px}
.pf-row__body{flex:1;min-width:0}
.pf-row__title{font-weight:700;color:var(--f-ink);display:flex;flex-wrap:wrap;align-items:center;gap:6px 8px}
.pf-row__snippet{margin:2px 0 0;color:var(--f-muted);font-size:.875rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pf-row__aside{flex:none;display:flex;align-items:center;gap:6px;color:var(--f-muted);font-size:.8125rem;text-align:right}
.pf-unread{box-shadow:inset 4px 0 0 var(--f-brand)}

/* ---- booking wizard ---- */
.pf-steps{display:none;gap:6px;margin:4px 0 16px}
.pf-js .pf-steps{display:flex}
.pf-steps span{flex:1;height:4px;border-radius:2px;background:var(--f-line)}
.pf-steps span.is-done{background:var(--f-brand)}
.pf-step-label{display:none;font-size:.8125rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--f-muted);margin:0 0 6px}
.pf-js .pf-step-label{display:block}
.pf-wizard-nav{display:none;gap:8px;margin-top:18px}
.pf-js .pf-wizard-nav{display:flex}
.pf-js .pf-nojs-only{display:none}

/* ---- choice cards (radio groups) ---- */
.pf-choices{display:grid;gap:8px}
.pf-choice{position:relative;display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid var(--f-line);border-radius:var(--f-r);background:var(--f-surface);cursor:pointer;min-height:48px;font-weight:600;color:var(--f-ink-soft);-webkit-tap-highlight-color:transparent}
.pf-choice input{position:absolute;opacity:0;pointer-events:none}
.pf-choice:has(input:checked){border-color:var(--f-brand);background:var(--f-wash);color:var(--f-brand-ink)}
.pf-choice:has(input:focus-visible){outline:3px solid var(--f-brand);outline-offset:2px}
.pf-choice__check{margin-left:auto;color:var(--f-brand);opacity:0;flex:none}
.pf-choice:has(input:checked) .pf-choice__check{opacity:1}

/* ---- summary / key-value lists ---- */
.pf-summary,.pf-kv{display:grid;margin:0}
.pf-summary>div,.pf-kv>div{display:flex;justify-content:space-between;align-items:baseline;gap:16px;padding:10px 0;border-bottom:1px solid var(--f-line)}
.pf-summary>div:last-child,.pf-kv>div:last-child{border-bottom:0}
.pf-summary dt,.pf-kv dt{color:var(--f-muted);font-size:.875rem;margin:0;flex:none}
.pf-summary dd,.pf-kv dd{margin:0;font-weight:700;text-align:right;color:var(--f-ink);word-break:break-word}

/* ---- modal ---- */
.pf-modal-backdrop{position:fixed;inset:0;z-index:120;background:rgba(15,23,42,.5);display:flex;align-items:flex-end;justify-content:center;padding:16px}
@media (min-width:640px){.pf-modal-backdrop{align-items:center}}
.pf-modal{background:var(--f-surface);border-radius:var(--f-r);padding:20px;max-width:420px;width:100%;box-shadow:var(--f-shadow)}
.pf-modal h3{display:flex;align-items:center;gap:8px;font-size:1.0625rem;margin:0 0 10px;color:var(--f-ink)}
.pf-modal p{color:var(--f-ink-soft)}
.pf-modal__actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;margin-top:16px}

/* ---- danger button variants (contract has none) ---- */
.pf-btn--danger{background:var(--f-danger);border-color:var(--f-danger);color:#FFFFFF}
.pf-btn--danger:hover{background:var(--f-danger);border-color:var(--f-danger);color:#FFFFFF;filter:brightness(.9)}
.pf-btn--danger-ghost{color:var(--f-danger)}
.pf-btn--danger-ghost:hover{border-color:var(--f-danger);color:var(--f-danger);background:var(--f-danger-wash)}

/* ---- health-ID hero ---- */
.pf-hid{background:linear-gradient(135deg,var(--f-brand-ink),var(--f-brand));color:#FFFFFF;border-radius:var(--f-r);padding:20px;box-shadow:var(--f-shadow)}
.pf-hid__row{display:flex;gap:16px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap}
.pf-hid__label{display:inline-flex;align-items:center;gap:8px;font-size:.75rem;font-weight:750;letter-spacing:.08em;text-transform:uppercase;opacity:.85}
.pf-hid__value{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:clamp(1.05rem,3.5vw,1.45rem);font-weight:700;letter-spacing:.04em;margin:10px 0 14px;word-break:break-all}
.pf-hid__meta{display:flex;gap:20px;flex-wrap:wrap}
.pf-hid__meta-label{font-size:.6875rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;opacity:.75}
.pf-hid__meta-value{font-size:.9375rem;font-weight:650}
.pf-hid__qr{flex:none;background:#FFFFFF;border-radius:var(--f-r);padding:8px;text-align:center;color:var(--f-brand-ink)}
.pf-hid__qr img{width:88px;height:88px;border-radius:6px}
.pf-hid__qr span{display:block;font-size:.6875rem;font-weight:700;margin-top:4px}

/* ---- quick actions ---- */
.pf-qa{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
@media (min-width:640px){.pf-qa{grid-template-columns:repeat(3,1fr)}}
@media (min-width:960px){.pf-qa{grid-template-columns:repeat(5,1fr)}}
a.pf-qa__item{display:flex;flex-direction:column;align-items:center;gap:8px;padding:14px 8px;text-decoration:none;text-align:center;font-weight:650;font-size:.875rem;color:var(--f-ink-soft)}
.pf-qa__icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--f-wash);color:var(--f-brand)}

/* ---- misc widgets ---- */
.pf-progress{height:6px;border-radius:3px;background:var(--f-line);overflow:hidden;margin:10px 0 14px}
.pf-progress span{display:block;height:100%;background:var(--f-brand);border-radius:3px}
.pf-tiles{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
@media (min-width:640px){.pf-tiles{grid-template-columns:repeat(4,1fr)}}
.pf-tile__label{font-size:.75rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--f-muted)}
.pf-tile__value{font-size:1.25rem;font-weight:800;color:var(--f-ink);margin-top:2px;line-height:1.3}
.pf-avatar{flex:none;width:40px;height:40px;border-radius:50%;background:var(--f-wash);color:var(--f-brand-ink);display:flex;align-items:center;justify-content:center;font-weight:800}
.pf-switch{position:relative;display:inline-flex;flex:none;width:48px;height:28px;cursor:pointer}
.pf-switch input{position:absolute;opacity:0;width:100%;height:100%;margin:0;cursor:pointer}
.pf-switch i{position:absolute;inset:0;border-radius:999px;background:var(--f-line);transition:background .15s ease;pointer-events:none}
.pf-switch i::after{content:"";position:absolute;top:3px;left:3px;width:22px;height:22px;border-radius:50%;background:#FFFFFF;box-shadow:0 1px 2px rgba(15,23,42,.25);transition:transform .15s ease}
.pf-switch input:checked+i{background:var(--f-good)}
.pf-switch input:checked+i::after{transform:translateX(20px)}
.pf-switch input:focus-visible+i{outline:3px solid var(--f-brand);outline-offset:2px}
.pf-check{display:flex;gap:10px;align-items:flex-start;padding:10px 0;cursor:pointer}
.pf-check input{width:20px;height:20px;margin-top:2px;accent-color:var(--f-brand);flex:none}
.pf-setting{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:56px;padding:8px 0;border-bottom:1px solid var(--f-line)}
.pf-setting:last-child{border-bottom:0}
.pf-setting__label{display:flex;align-items:center;gap:10px;font-weight:650;color:var(--f-ink)}
.pf-setting__label .f-icon{color:var(--f-brand)}

/* ---- tables that must stay tables ---- */
.pf-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.pf-table{width:100%;border-collapse:collapse;font-size:.9375rem}
.pf-table th{font-size:.75rem;font-weight:750;letter-spacing:.05em;text-transform:uppercase;color:var(--f-muted);text-align:left;padding:8px 10px;border-bottom:1px solid var(--f-line)}
.pf-table td{padding:10px;border-bottom:1px solid var(--f-line);color:var(--f-ink-soft)}
.pf-table tr:last-child td{border-bottom:0}
.pf-table input[type="checkbox"]{width:20px;height:20px;accent-color:var(--f-brand)}

/* ---- pagination (Laravel links()) ---- */
.pf-pagination{margin-top:16px}
.pf-pagination nav{display:flex;justify-content:center}
.pf-pagination svg{width:20px;height:20px}
.pf-pagination a,.pf-pagination span[aria-current] > span{min-width:40px;min-height:40px;display:inline-flex;align-items:center;justify-content:center}

/* ---- two-column layout (desktop) ---- */
.pf-cols{display:grid;gap:16px}
@media (min-width:960px){.pf-cols{grid-template-columns:minmax(0,1fr) 380px;align-items:start}}

/* ---- message bubbles ---- */
.pf-msgs{display:grid;gap:10px}
.pf-msg{max-width:85%;padding:10px 14px;border-radius:var(--f-r);background:var(--f-surface);border:1px solid var(--f-line)}
.pf-msg--mine{background:var(--f-wash);border-color:transparent;margin-left:auto}
.pf-msg__who{font-size:.75rem;font-weight:750;letter-spacing:.03em;text-transform:uppercase;color:var(--f-muted);margin-bottom:2px}
.pf-msg__body{white-space:pre-wrap;color:var(--f-ink);overflow-wrap:anywhere}
.pf-msg__at{font-size:.75rem;color:var(--f-muted);margin-top:4px}
</style>
<?php /**PATH C:\laragon\www\opescare-finder\resources\views/portals/patient/_finder_head.blade.php ENDPATH**/ ?>