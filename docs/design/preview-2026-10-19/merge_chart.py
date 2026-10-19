"""Build the merged patient-chart preview from the live chart HTML (preview only)."""
import pathlib

D = pathlib.Path(__file__).parent
src = (D / 'chart-live.html').read_text(encoding='utf-8')

CSS = """
<style>
/* Preview-only additions; colours come from od.css tokens. */
.od-page{position:relative}
.od-page::after{content:"";position:absolute;pointer-events:none;z-index:5;
 left:50%;top:640px;width:620px;height:620px;transform:translateX(-50%);
 background:url(img/seal.webp) center/contain no-repeat;filter:grayscale(1) brightness(1.15);opacity:.07;mix-blend-mode:multiply}
.mx-kpis{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0 0 14px}
.mx-kpis .od-kpi__value{white-space:nowrap}
.odc-tab-body .od-table{width:100%}.odc-tab-body .od-table td,.odc-tab-body .od-table th{white-space:nowrap}
.odc-tab-body .od-table td:first-child{white-space:normal}
.mx-row{display:grid;grid-template-columns:250px minmax(0,1fr);gap:14px;margin-bottom:14px}
.mx-row2{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px;margin-bottom:14px}
.mx-fig{position:relative;border-radius:12px;overflow:hidden;background:var(--od-side-bot) url(img/guilloche-texture.webp) center/cover}
.mx-fig::before{content:"";position:absolute;inset:0;background:rgba(10,46,87,.6)}
.mx-stage{position:relative;width:150px;margin:0 auto;padding:12px 0 4px;aspect-ratio:305/720;box-sizing:content-box}
.mx-stage img{width:100%;height:100%;object-fit:contain}
.mx-pin{position:absolute;width:12px;height:12px;margin:-6px 0 0 -6px;border-radius:50%;background:var(--od-urgent);border:2px solid #fff;animation:mxt 1.6s ease-out infinite}
@keyframes mxt{0%{box-shadow:0 0 0 0 rgba(179,38,30,.7)}75%{box-shadow:0 0 0 14px rgba(179,38,30,0)}100%{box-shadow:0 0 0 0 rgba(179,38,30,0)}}
@media (prefers-reduced-motion:reduce){.mx-pin{animation:none}}
.mx-fig p{position:relative;margin:0;padding:0 10px 8px;color:#C9D7E8;font-size:11px;text-align:center}
.mx-views{position:relative;display:flex;justify-content:center;gap:6px;padding:0 0 10px}
.mx-views span{font-size:11px;color:#fff;border:1px solid rgba(255,255,255,.35);border-radius:999px;padding:2px 10px}
.mx-views span.on{background:#fff;color:var(--od-primary)}
.mx-key{display:flex;flex-wrap:wrap;gap:14px;font-size:12px;color:var(--od-muted);margin-top:8px}
.mx-key i{display:inline-block;width:14px;height:3px;border-radius:2px;vertical-align:3px;margin-right:6px}
.mx-key b{display:inline-block;width:10px;height:10px;border-radius:3px;vertical-align:-1px;margin-right:6px}
.mx-pain{display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid var(--od-rule);font-size:13px}
.mx-pain:last-child{border-bottom:0}.mx-pain small{display:block;color:var(--od-muted);font-size:12px}
.mx-sev{font-weight:700;font-variant-numeric:tabular-nums;color:var(--od-ink-h);white-space:nowrap}
.mx-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--od-urgent);margin-right:8px}
.mx-trend{font-size:11px;color:var(--od-muted)}
.mx-donut{display:flex;align-items:center;gap:16px}
.mx-dl{display:grid;gap:6px;font-size:13px}.mx-dl span{color:var(--od-muted)}
.mx-tl{list-style:none;margin:0;padding:0}
.mx-tl li{position:relative;padding:0 0 14px 22px;font-size:13px}
.mx-tl li::before{content:"";position:absolute;left:5px;top:6px;width:8px;height:8px;border-radius:50%;background:var(--od-primary)}
.mx-tl li::after{content:"";position:absolute;left:8px;top:16px;bottom:0;width:2px;background:var(--od-rule)}
.mx-tl li:last-child::after{display:none}
.mx-tl small{display:block;color:var(--od-muted);font-size:12px}
.mx-num{font-variant-numeric:tabular-nums;white-space:nowrap}
.mx-head{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.mx-actions{display:flex;gap:8px;margin-left:auto}
.mx-cap{font-size:12px;color:var(--od-muted);margin:6px 0 0}
</style>
"""

ICON = lambda name: f'<i data-lucide="{name}"></i>'

HEADER_EXTRA = f"""
            <div class="mx-head">
                <span class="od-pill od-pill--urgent">{ICON('triangle-alert')} Allergy: penicillin (rash, moderate)</span>
                <span class="od-pill od-pill--ready">{ICON('shield-check')} Consent active · summary + results · until 18:40</span>
                <span class="od-pill od-pill--normal">{ICON('users')} Guardian: Marie D. (mother)</span>
                <div class="mx-actions"><a class="od-btn od-btn--secondary" href="#">{ICON('flask-conical')} Order test</a><a class="od-btn od-btn--secondary" href="#">{ICON('send')} Refer</a><a class="od-btn od-btn--primary" href="#">{ICON('stethoscope')} New encounter</a></div>
            </div>
"""

CONSENT_NOTE = f"""<div class="odc-note" role="status" style="border-color:var(--od-ready);background:var(--od-ready-bg);color:var(--od-ready-ink)">
                {ICON('shield-check')}
                <div><strong>Full record open under consent</strong>
                <p>Granted by the guardian at 14:40 for 4 hours, scope: summary and results. This view is written to the patient's access log.</p></div>
            </div>"""

TAB_BODY = f"""<div class="odc-tab-body">
<div class="mx-kpis">
  <div class="od-kpi od-tint--blue"><span class="od-kpi__icon">{ICON('heart-pulse')}</span><span class="od-kpi__body"><span class="od-kpi__label">Blood pressure</span><span class="od-kpi__value">102/66</span><span class="od-kpi__sub">mmHg · triage 09:12</span></span></div>
  <div class="od-kpi od-tint--teal"><span class="od-kpi__icon">{ICON('activity')}</span><span class="od-kpi__body"><span class="od-kpi__label">Pulse</span><span class="od-kpi__value">108</span><span class="od-kpi__sub">bpm · rising since 16 Oct</span></span></div>
  <div class="od-kpi od-tint--sky"><span class="od-kpi__icon">{ICON('wind')}</span><span class="od-kpi__body"><span class="od-kpi__label">SpO₂</span><span class="od-kpi__value">98%</span><span class="od-kpi__sub">room air</span></span></div>
  <div class="od-kpi od-tint--amber"><span class="od-kpi__icon">{ICON('thermometer')}</span><span class="od-kpi__body"><span class="od-kpi__label">Temperature</span><span class="od-kpi__value">38.6 °C</span><span class="od-kpi__sub">fever · 26 kg · 128 cm</span></span></div>
</div>

<div class="mx-row">
  <section class="od-card"><header class="od-card__head"><h2 class="od-card__title">Presenting complaint</h2></header><div class="od-card__body">
    <div class="mx-fig"><div class="mx-stage"><img src="img/child-boy-base.png" alt="Anatomy figure, boy"><span class="mx-pin" style="left:50%;top:8%"></span><span class="mx-pin" style="left:50%;top:41%;animation-delay:.6s"></span></div>
      <div class="mx-views"><span class="on">Front</span><span>Back</span></div><p>Figure follows sex and age band</p></div>
    <div class="mx-key"><span><b style="background:var(--od-urgent)"></b>Reported pain site</span></div>
    <div class="mx-pain"><div><span class="mx-dot"></span>Forehead · headache<small>2 days · guardian at check-in · worse</small></div><span class="mx-sev">5/10</span></div>
    <div class="mx-pain"><div><span class="mx-dot"></span>Abdomen · stomach pain<small>Since last night · nurse at triage · same</small></div><span class="mx-sev">7/10</span></div>
  </div></section>
  <div class="od-stack">
    <section class="od-card"><header class="od-card__head"><h2 class="od-card__title">Vitals trend · last 14 days</h2></header><div class="od-card__body">
      <svg viewBox="0 0 560 190" width="100%" role="img" aria-label="Temperature and pulse over 14 days">
        <g stroke="var(--od-rule)"><line x1="40" y1="20" x2="550" y2="20"/><line x1="40" y1="70" x2="550" y2="70"/><line x1="40" y1="120" x2="550" y2="120"/><line x1="40" y1="165" x2="550" y2="165"/></g>
        <g fill="var(--od-muted)" font-size="11" font-family="Inter"><text x="4" y="24">120</text><text x="4" y="74">100</text><text x="10" y="124">80</text><text x="40" y="184">6 Oct</text><text x="250" y="184">12 Oct</text><text x="500" y="184">19 Oct</text></g>
        <polyline fill="none" stroke="var(--od-teal)" stroke-width="2.5" points="40,110 125,108 210,112 295,104 380,96 465,70 550,52"/>
        <polyline fill="none" stroke="var(--od-pending)" stroke-width="2.5" stroke-dasharray="6 4" points="40,140 125,138 210,141 295,136 380,128 465,104 550,92"/>
        <g fill="#fff" stroke-width="2"><circle cx="550" cy="52" r="4" stroke="var(--od-teal)"/><circle cx="550" cy="92" r="4" stroke="var(--od-pending)"/></g>
      </svg>
      <div class="mx-key"><span><i style="background:var(--od-teal)"></i>Pulse (bpm)</span><span><i style="background:repeating-linear-gradient(90deg,var(--od-pending) 0 5px,transparent 5px 8px)"></i>Temperature (°C ×3, scaled)</span></div>
    </div></section>
    <section class="od-card"><header class="od-card__head"><h2 class="od-card__title">Recent results</h2><a class="od-card__link" href="#">All results</a></header><div class="od-card__body">
      <table class="od-table"><thead><tr><th>Test</th><th class="od-table__num">Result</th><th class="od-table__num">Range</th><th class="od-table__num">Date</th></tr></thead><tbody>
        <tr><td class="od-table__name">Malaria RDT</td><td class="od-table__num"><span class="od-pill od-pill--urgent">Positive</span></td><td class="od-table__num mx-num">Negative</td><td class="od-table__num mx-num">today</td></tr>
        <tr><td class="od-table__name">Haemoglobin</td><td class="od-table__num"><span class="od-pill od-pill--ready mx-num">11.8 g/dL</span></td><td class="od-table__num mx-num">11.5–15.5</td><td class="od-table__num mx-num">today</td></tr>
        <tr><td class="od-table__name">Blood culture</td><td class="od-table__num"><span class="od-pill od-pill--pending">Pending</span></td><td class="od-table__num">—</td><td class="od-table__num mx-num">today</td></tr>
      </tbody></table>
    </div></section>
  </div>
</div>

<div class="mx-row2">
  <section class="od-card"><header class="od-card__head"><h2 class="od-card__title">Encounters</h2><a class="od-card__link" href="#">All encounters</a></header><div class="od-card__body">
    <ol class="mx-tl">
      <li><b>Consultation · fever and abdominal pain</b><small>Today · Dr. Amara Diallo · Afamba · diagnosis pending results</small></li>
      <li><b>Triage · priority 2</b><small>Today 09:12 · Nurse Ewane · pain 7/10, temp 38.6 °C</small></li>
      <li><b>Immunisation · measles-rubella (MR2)</b><small>2 Sep 2026 · CSI Mvog-Ada</small></li>
      <li><b>Consultation · otitis media</b><small>14 Jun 2026 · Dr. K. · Clinique ESSEC · resolved</small></li>
    </ol>
  </div></section>
  <section class="od-card"><header class="od-card__head"><h2 class="od-card__title">Care across facilities · 12 months</h2></header><div class="od-card__body mx-donut">
    <svg viewBox="0 0 120 120" width="116" height="116" role="img" aria-label="Encounters by type">
      <g transform="rotate(-90 60 60)" fill="none" stroke-width="16">
        <circle cx="60" cy="60" r="44" stroke="var(--od-primary)" stroke-dasharray="110.6 276.5"/>
        <circle cx="60" cy="60" r="44" stroke="var(--od-teal)" stroke-dasharray="69.1 276.5" stroke-dashoffset="-110.6"/>
        <circle cx="60" cy="60" r="44" stroke="var(--od-pending)" stroke-dasharray="55.3 276.5" stroke-dashoffset="-179.7"/>
        <circle cx="60" cy="60" r="44" stroke="var(--od-faint)" stroke-dasharray="41.5 276.5" stroke-dashoffset="-235"/>
      </g>
      <text x="60" y="57" text-anchor="middle" font-size="20" font-weight="700" fill="var(--od-ink-h)" font-family="Inter">20</text>
      <text x="60" y="73" text-anchor="middle" font-size="10" fill="var(--od-muted)" font-family="Inter">encounters</text>
    </svg>
    <div class="mx-dl">
      <div><b style="display:inline-block;width:10px;height:10px;border-radius:3px;background:var(--od-primary);margin-right:8px"></b>Consultations <span>8</span></div>
      <div><b style="display:inline-block;width:10px;height:10px;border-radius:3px;background:var(--od-teal);margin-right:8px"></b>Lab visits <span>5</span></div>
      <div><b style="display:inline-block;width:10px;height:10px;border-radius:3px;background:var(--od-pending);margin-right:8px"></b>Pharmacy <span>4</span></div>
      <div><b style="display:inline-block;width:10px;height:10px;border-radius:3px;background:var(--od-faint);margin-right:8px"></b>Immunisations <span>3</span></div>
    </div>
  </div></section>
</div>

<div class="mx-row2">
  <section class="od-card" style="grid-column:1/-1"><header class="od-card__head"><h2 class="od-card__title">Medicines</h2><a class="od-card__link" href="#">Prescription history</a></header><div class="od-card__body">
    <table class="od-table"><thead><tr><th>Medicine</th><th>Dose</th><th>Status</th></tr></thead><tbody>
      <tr><td class="od-table__name">Paracetamol 250 mg</td><td>every 6 h · 3 days</td><td><span class="od-pill od-pill--normal">Partially dispensed</span></td></tr>
      <tr><td class="od-table__name">Amoxicillin 250 mg</td><td>every 8 h · 7 days</td><td><span class="od-pill od-pill--ready">Dispensed</span></td></tr>
    </tbody></table>
  </div></section>
  <section class="od-card"><header class="od-card__head"><h2 class="od-card__title">Problems and allergies</h2></header><div class="od-card__body">
    <ul class="od-list">
      <li class="od-list__row"><span class="od-list__icon od-tint--blue">{ICON('clipboard-list')}</span><span class="od-list__text"><span class="od-list__title">Recurrent otitis media</span><span class="od-list__sub">H66.9 · since 2025 · active</span></span></li>
      <li class="od-list__row"><span class="od-list__icon od-tint--amber">{ICON('triangle-alert')}</span><span class="od-list__text"><span class="od-list__title">Penicillin allergy</span><span class="od-list__sub">Rash · moderate · confirmed 2025</span></span></li>
    </ul>
  </div></section>
</div>

<div class="mx-row2">
  <section class="od-card"><header class="od-card__head"><h2 class="od-card__title">Referrals</h2></header><div class="od-card__body">
    <ul class="od-list">
      <li class="od-list__row"><span class="od-list__icon od-tint--blue">{ICON('send')}</span><span class="od-list__text"><span class="od-list__title">Paediatrics · Hôpital Central</span><span class="od-list__sub">Sent 15 Jun · accepted · completed</span></span></li>
    </ul>
  </div></section>
  <section class="od-card"><header class="od-card__head"><h2 class="od-card__title">Documents</h2><a class="od-card__link" href="#">All documents</a></header><div class="od-card__body">
    <ul class="od-list">
      <li class="od-list__row"><span class="od-list__icon od-tint--teal">{ICON('file-check')}</span><span class="od-list__text"><span class="od-list__title">Lab report · malaria RDT</span><span class="od-list__sub">Today · verifiable QR</span></span></li>
      <li class="od-list__row"><span class="od-list__icon od-tint--teal">{ICON('file-check')}</span><span class="od-list__text"><span class="od-list__title">Immunisation record</span><span class="od-list__sub">2 Sep 2026 · verifiable QR</span></span></li>
    </ul>
  </div></section>
</div>
<p class="mx-cap">Preview only, not deployed. Sample data. Every section respects consent scope: without a grant, only identity, allergies and current medicines show, as today.</p>
</div>
        </div>"""

ASIDE_EXTRA = f"""
        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">Consent and access</h2></header>
            <div class="od-card__body"><ul class="od-list">
                <li class="od-list__row"><span class="od-list__icon od-tint--green">{ICON('shield-check')}</span><span class="od-list__text"><span class="od-list__title">Active grant · until 18:40</span><span class="od-list__sub">Scope: summary, results · granted by guardian</span></span></li>
                <li class="od-list__row"><span class="od-list__icon od-tint--blue">{ICON('history')}</span><span class="od-list__text"><span class="od-list__title">Access log · 3 views today</span><span class="od-list__sub">You 14:42 · Nurse Ewane 09:12 · Lab 10:05</span></span></li>
            </ul><a class="od-btn od-btn--secondary od-btn--block" href="#">{ICON('key-round')} Request wider access</a></div>
        </section>
        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">Care team</h2></header>
            <div class="od-card__body"><ul class="od-list">
                <li class="od-list__row"><span class="od-avatar od-tint--sky">AD</span><span class="od-list__text"><span class="od-list__title">Dr. Amara Diallo</span><span class="od-list__sub">General doctor · Afamba (you)</span></span></li>
                <li class="od-list__row"><span class="od-avatar od-tint--teal">NE</span><span class="od-list__text"><span class="od-list__title">Nurse Ewane</span><span class="od-list__sub">Triage · Afamba</span></span></li>
            </ul></div>
        </section>
        <section class="od-card">
            <header class="od-card__head"><h2 class="od-card__title">Family and emergency</h2></header>
            <div class="od-card__body"><ul class="od-list">
                <li class="od-list__row"><span class="od-list__icon od-tint--blue">{ICON('users')}</span><span class="od-list__text"><span class="od-list__title">Marie D. · mother</span><span class="od-list__sub">Guardian · acts for this patient</span></span></li>
                <li class="od-list__row"><span class="od-list__icon od-tint--blue">{ICON('phone')}</span><span class="od-list__text"><span class="od-list__title">Emergency contact</span><span class="od-list__sub">Marie D. · on file</span></span></li>
            </ul></div>
        </section>
"""

out = src
# 1. styles
out = out.replace('</head>', CSS + '</head>', 1)
# 2. header extras after the facts block of the patient card
anchor = '</div>\n                </div>\n            </div>\n'
i = out.index('od-detail__facts')
j = out.index(anchor, i) + len(anchor)
out = out[:j] + HEADER_EXTRA + out[j:]
# 3. consent note instead of the "preview only" note
s = out.index('<div class="odc-note odc-note--pending"')
e = out.index('</div>\n            </div>', s) + len('</div>\n            </div>')
out = out[:s] + CONSENT_NOTE + out[e:]
# 4. summary tab body
s = out.index('<div class="odc-tab-body">')
e = out.index('\n    </section>\n\n    <aside class="od-stack">', s)
out = out[:s] + TAB_BODY + out[e:]
# 5. extra side cards after the privacy settings card
k = out.index('<aside class="od-stack">') + len('<aside class="od-stack">')
k = out.index('</section>', k) + len('</section>')
out = out[:k] + ASIDE_EXTRA + out[k:]
# 6. render the new Lucide placeholders
out = out.replace('</body>', '<script src="http://127.0.0.1:8081/js/lucide.min.js"></script><script>window.lucide&&lucide.createIcons()</script></body>', 1)
(D / 'chart-merged.html').write_text(out, encoding='utf-8')
print('written', len(out))
