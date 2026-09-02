
content = r'''
<!-- ===== HEALTH ORG PORTAL ===== -->
<div class="portal-pane" id="portal-healthorg">
  <div class="portal-wrap">
    <div class="portal-sidebar">
      <div class="sb-brand"><div class="sb-logo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg></div><div><div class="sb-name">OpesCare</div><div class="sb-sub">Health Org Portal</div></div></div>
      <div class="sb-rb"><div class="role-badge">Health Org Admin</div></div>
      <nav class="sb-nav">
        <div class="sb-sec">Overview</div>
        <div class="sb-link active" id="horg-nav-dashboard" onclick="goPage('healthorg','dashboard')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</div>
        <div class="sb-sec">Programs</div>
        <div class="sb-link" id="horg-nav-programs" onclick="goPage('healthorg','programs')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Programs</div>
        <div class="sb-sec">Outreach</div>
        <div class="sb-link" id="horg-nav-sites" onclick="goPage('healthorg','sites')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3,11 22,2 13,21 11,13 3,11"/></svg>Outreach Sites</div>
        <div class="sb-link" id="horg-nav-schedule" onclick="goPage('healthorg','schedule')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Schedule</div>
        <div class="sb-sec">Public Health</div>
        <div class="sb-link" id="horg-nav-reports" onclick="goPage('healthorg','reports')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>Reports</div>
        <div class="sb-link" id="horg-nav-signals" onclick="goPage('healthorg','signals')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>Outbreak Signals</div>
        <div class="sb-link" id="horg-nav-disease-map" onclick="goPage('healthorg','disease-map')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Disease Map</div>
        <div class="sb-sec">Tools</div>
        <div class="sb-link" id="horg-nav-care-map" onclick="goPage('healthorg','care-map')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3,11 22,2 13,21 11,13 3,11"/></svg>Care Map</div>
        <div class="sb-link" id="horg-nav-export" onclick="goPage('healthorg','export')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Data Export</div>
      </nav>
      <div class="sb-foot"><div class="sb-user"><div class="sb-av">RS</div><div><div class="sb-uname">Réseau Santé CM</div><div class="sb-urole">Health Organization</div></div></div></div>
    </div>
    <div class="portal-main">
      <div class="portal-topbar"><span class="tb-crumb">Health Org</span><span class="tb-sep">/</span><span class="tb-page" id="horg-breadcrumb">Dashboard</span><div class="tb-right"><div class="tb-av">RS</div><span class="tb-uname">Réseau Santé CM</span></div></div>
      <div class="portal-content">
        <!-- HEALTH ORG DASHBOARD -->
        <div class="portal-page active" id="horg-page-dashboard">
          <div class="alert alert-info"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><div>Public Health API access enabled. Your organization can submit epidemiological reports and access aggregated health data. <a style="color:var(--info);cursor:pointer;font-weight:600">API Documentation →</a></div></div>
          <div class="stat-grid cols-5">
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="sc-val">124,820</div><div class="sc-lbl">Registered Patients</div></div>
            <div class="stat-card teal"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div class="sc-val">38</div><div class="sc-lbl">Active Facilities</div></div>
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg></div><div class="sc-val">7</div><div class="sc-lbl">Active Programs</div></div>
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div><div class="sc-val">3</div><div class="sc-lbl">Draft Reports</div></div>
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/></svg></div><div class="sc-val">12</div><div class="sc-lbl">Submitted Reports</div></div>
          </div>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px">
            <div class="qa-card" onclick="goPage('healthorg','programs')"><div class="qa-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg></div><div class="qa-lbl">Manage Programs</div></div>
            <div class="qa-card" onclick="goPage('healthorg','reports')"><div class="qa-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div><div class="qa-lbl">Submit Report</div></div>
            <div class="qa-card" onclick="goPage('healthorg','signals')"><div class="qa-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div><div class="qa-lbl">Outbreak Signals</div></div>
            <div class="qa-card" onclick="goPage('healthorg','disease-map')"><div class="qa-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div class="qa-lbl">Disease Map</div></div>
            <div class="qa-card" onclick="goPage('healthorg','sites')"><div class="qa-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3,11 22,2 13,21 11,13 3,11"/></svg></div><div class="qa-lbl">Outreach Sites</div></div>
            <div class="qa-card" onclick="goPage('healthorg','export')"><div class="qa-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/></svg></div><div class="qa-lbl">Export Data</div></div>
          </div>
        </div>
        <!-- PROGRAMS -->
        <div class="portal-page" id="horg-page-programs">
          <div class="page-header"><div><h2>Programs</h2></div><button class="btn btn-primary">Create Program</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Program Name</th><th>Type</th><th>Start</th><th>End</th><th>Facilities</th><th>Beneficiaries</th><th>Budget (XAF)</th><th>Status</th></tr></thead><tbody>
            <tr><td><strong>Vaccination Nationale Rougeole</strong></td><td>Vaccination</td><td>01 Jan 2025</td><td>31 Dec 2025</td><td>38</td><td>42,800</td><td>85,000,000</td><td><span class="badge badge-ok">Active</span></td></tr>
            <tr><td><strong>Dépistage Paludisme Centre</strong></td><td>Screening</td><td>01 Mar 2025</td><td>31 Aug 2025</td><td>12</td><td>18,420</td><td>24,000,000</td><td><span class="badge badge-ok">Active</span></td></tr>
            <tr><td><strong>Nutrition Maternelle Nord</strong></td><td>Nutrition</td><td>15 Feb 2025</td><td>14 Feb 2026</td><td>8</td><td>6,200</td><td>15,000,000</td><td><span class="badge badge-ok">Active</span></td></tr>
            <tr><td><strong>Sensibilisation VIH/SIDA</strong></td><td>Education</td><td>01 Jun 2025</td><td>30 Nov 2025</td><td>15</td><td>28,000</td><td>10,000,000</td><td><span class="badge badge-warn">Planned</span></td></tr>
          </tbody></table></div>
        </div>
        <!-- OUTREACH SITES -->
        <div class="portal-page" id="horg-page-sites">
          <div class="page-header"><div><h2>Outreach Sites</h2></div><div class="ph-actions"><button class="btn btn-secondary btn-sm">Schedule Visit</button><button class="btn btn-primary">Add Site</button></div></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Site Name</th><th>Location</th><th>Region</th><th>Next Visit</th><th>Frequency</th><th>Team Lead</th><th>Status</th></tr></thead><tbody>
            <tr><td><strong>Village Mbankomo Health Point</strong></td><td>Mbankomo</td><td>Centre</td><td>20 Jun 2025</td><td>Weekly</td><td>Infirmier Essama</td><td><span class="badge badge-ok">Active</span></td></tr>
            <tr><td><strong>Mfoundi Community Post</strong></td><td>Yaoundé</td><td>Centre</td><td>18 Jun 2025</td><td>Bi-weekly</td><td>Infirmier Bele</td><td><span class="badge badge-ok">Active</span></td></tr>
            <tr><td><strong>Nkolbisson Outreach Point</strong></td><td>Nkolbisson</td><td>Centre</td><td>25 Jun 2025</td><td>Monthly</td><td>Infirmier Tsogo</td><td><span class="badge badge-ok">Active</span></td></tr>
          </tbody></table></div>
        </div>
        <!-- OUTREACH SCHEDULE -->
        <div class="portal-page" id="horg-page-schedule">
          <div class="page-header"><div><h2>Outreach Schedule</h2><p>June 2025</p></div></div>
          <div class="panel"><div class="panel-body">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
              <button class="btn btn-secondary btn-sm">◀ May</button>
              <span style="font-size:16px;font-weight:700">June 2025</span>
              <button class="btn btn-secondary btn-sm">Jul ▶</button>
            </div>
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center">
              <div style="font-size:11px;font-weight:700;color:var(--muted);padding:6px">Sun</div><div style="font-size:11px;font-weight:700;color:var(--muted);padding:6px">Mon</div><div style="font-size:11px;font-weight:700;color:var(--muted);padding:6px">Tue</div><div style="font-size:11px;font-weight:700;color:var(--muted);padding:6px">Wed</div><div style="font-size:11px;font-weight:700;color:var(--muted);padding:6px">Thu</div><div style="font-size:11px;font-weight:700;color:var(--muted);padding:6px">Fri</div><div style="font-size:11px;font-weight:700;color:var(--muted);padding:6px">Sat</div>
              <div style="padding:8px;font-size:12px;color:var(--muted)">1</div><div style="padding:8px;font-size:12px;color:var(--muted)">2</div><div style="padding:8px;font-size:12px;color:var(--muted)">3</div><div style="padding:8px;font-size:12px;color:var(--muted)">4</div><div style="padding:8px;font-size:12px;color:var(--muted)">5</div><div style="padding:8px;font-size:12px;color:var(--muted)">6</div><div style="padding:8px;font-size:12px;color:var(--muted)">7</div>
              <div style="padding:8px;font-size:12px">8</div><div style="padding:8px;font-size:12px;background:var(--infol);border-radius:4px;position:relative"><div style="font-size:12px">9</div><div style="font-size:9px;background:var(--b);color:#fff;border-radius:2px;padding:1px 4px;margin-top:2px">Mbankomo</div></div><div style="padding:8px;font-size:12px">10</div><div style="padding:8px;font-size:12px">11</div><div style="padding:8px;font-size:12px">12</div><div style="padding:8px;font-size:12px">13</div><div style="padding:8px;font-size:12px;color:var(--muted)">14</div>
              <div style="padding:8px;font-size:12px;color:var(--muted)">15</div><div style="padding:8px;font-size:12px">16</div><div style="padding:8px;font-size:12px;background:var(--infol);border-radius:4px"><div style="font-size:12px">17</div><div style="font-size:9px;background:var(--t);color:#fff;border-radius:2px;padding:1px 4px;margin-top:2px">Mfoundi</div></div><div style="padding:8px;font-size:12px">18</div><div style="padding:8px;font-size:12px">19</div><div style="padding:8px;font-size:12px;background:var(--infol);border-radius:4px"><div style="font-size:12px;font-weight:700;color:var(--b)">20</div><div style="font-size:9px;background:var(--b);color:#fff;border-radius:2px;padding:1px 4px;margin-top:2px">Mbankomo</div></div><div style="padding:8px;font-size:12px;color:var(--muted)">21</div>
              <div style="padding:8px;font-size:12px;color:var(--muted)">22</div><div style="padding:8px;font-size:12px">23</div><div style="padding:8px;font-size:12px">24</div><div style="padding:8px;font-size:12px;background:var(--infol);border-radius:4px"><div style="font-size:12px">25</div><div style="font-size:9px;background:var(--warn);color:#fff;border-radius:2px;padding:1px 4px;margin-top:2px">Nkolbisson</div></div><div style="padding:8px;font-size:12px">26</div><div style="padding:8px;font-size:12px">27</div><div style="padding:8px;font-size:12px;color:var(--muted)">28</div>
              <div style="padding:8px;font-size:12px;color:var(--muted)">29</div><div style="padding:8px;font-size:12px">30</div>
            </div>
          </div></div>
        </div>
        <!-- REPORTS -->
        <div class="portal-page" id="horg-page-reports">
          <div class="page-header"><div><h2>Public Health Reports</h2></div><button class="btn btn-primary">Create Report</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Title</th><th>Type</th><th>Period</th><th>Submitted To</th><th>Date</th><th>Status</th></tr></thead><tbody>
            <tr><td><strong>Rapport Épidémio Semaine 23</strong></td><td>Weekly Epidemiological</td><td>Wk 23 2025</td><td>Ministère de la Santé</td><td>10 Jun 2025</td><td><span class="badge badge-ok">Submitted</span></td></tr>
            <tr><td><strong>Rapport Mensuel Maladies Mai</strong></td><td>Monthly Disease</td><td>May 2025</td><td>OMS / WHO</td><td>05 Jun 2025</td><td><span class="badge badge-ok">Submitted</span></td></tr>
            <tr><td><strong>Programme Vaccination Q2</strong></td><td>Program Progress</td><td>Apr–Jun 2025</td><td>UNICEF</td><td>—</td><td><span class="badge badge-warn">Draft</span></td></tr>
          </tbody></table></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Create Report</span></div><div class="panel-body">
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Report Title <span>*</span></label><input class="form-control" placeholder="e.g. Rapport Épidémio Semaine 24"></div>
              <div class="form-group"><label class="form-label">Report Type <span>*</span></label><select class="form-control"><option>Weekly Epidemiological</option><option>Monthly Disease</option><option>Program Progress</option><option>Outbreak Report</option></select></div>
            </div>
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Reporting Period</label><input class="form-control" placeholder="e.g. 09–15 Jun 2025"></div>
              <div class="form-group"><label class="form-label">Recipient</label><select class="form-control"><option>Ministère de la Santé CM</option><option>OMS / WHO</option><option>UNICEF</option><option>UNFPA</option></select></div>
            </div>
            <div class="form-group"><label class="form-label">Data Sections</label>
              <div class="checkbox-group" style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                <div class="checkbox-item"><input type="checkbox" checked> Morbidity data</div>
                <div class="checkbox-item"><input type="checkbox" checked> Mortality data</div>
                <div class="checkbox-item"><input type="checkbox"> Vaccination coverage</div>
                <div class="checkbox-item"><input type="checkbox"> Laboratory results</div>
              </div>
            </div>
            <div class="form-group"><label class="form-label">Narrative</label><textarea class="form-control" rows="4" placeholder="Summary of epidemiological situation, trends, actions taken..."></textarea></div>
            <div style="display:flex;gap:8px"><button class="btn btn-primary">Submit Report</button><button class="btn btn-secondary">Save Draft</button></div>
          </div></div>
        </div>
        <!-- OUTBREAK SIGNALS -->
        <div class="portal-page" id="horg-page-signals">
          <div class="page-header"><div><h2>Outbreak Signals</h2></div><button class="btn btn-danger">Raise New Signal</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Signal ID</th><th>Disease</th><th>Region</th><th>Cases</th><th>Deaths</th><th>CFR%</th><th>Alert Level</th><th>Date</th><th>Status</th></tr></thead><tbody>
            <tr><td class="mono">SIG-2025-041</td><td><strong>Malaria</strong></td><td>Far North</td><td>1,240</td><td>18</td><td>1.5%</td><td><span class="signal-level signal-orange">ORANGE</span></td><td>12 Jun 2025</td><td><span class="badge badge-warn">Active</span></td></tr>
            <tr><td class="mono">SIG-2025-039</td><td><strong>Cholera</strong></td><td>Littoral</td><td>42</td><td>3</td><td>7.1%</td><td><span class="signal-level signal-red">RED</span></td><td>08 Jun 2025</td><td><span class="badge badge-danger">Critical</span></td></tr>
            <tr><td class="mono">SIG-2025-035</td><td><strong>Mpox</strong></td><td>Centre</td><td>8</td><td>0</td><td>0.0%</td><td><span class="signal-level signal-orange">ORANGE</span></td><td>01 Jun 2025</td><td><span class="badge badge-warn">Monitoring</span></td></tr>
            <tr><td class="mono">SIG-2025-028</td><td><strong>Measles</strong></td><td>North West</td><td>124</td><td>2</td><td>1.6%</td><td><span class="signal-level signal-green">GREEN</span></td><td>15 May 2025</td><td><span class="badge badge-ok">Contained</span></td></tr>
          </tbody></table></div>
          <div class="panel" style="border-left:3px solid var(--danger)"><div class="panel-hd"><span class="panel-title">SIG-2025-039 — Cholera · Littoral Region</span></div><div class="panel-body">
            <div class="two-col">
              <div>
                <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:8px">EPI CURVE (weekly cases)</div>
                <div class="bar-chart" style="height:80px">
                  <div class="bar-wrap"><div class="bar-val" style="font-size:9px">2</div><div class="bar" style="height:20%;background:var(--warn)"></div><div class="bar-lbl">Wk20</div></div>
                  <div class="bar-wrap"><div class="bar-val" style="font-size:9px">5</div><div class="bar" style="height:40%;background:var(--warn)"></div><div class="bar-lbl">Wk21</div></div>
                  <div class="bar-wrap"><div class="bar-val" style="font-size:9px">12</div><div class="bar" style="height:80%;background:var(--danger)"></div><div class="bar-lbl">Wk22</div></div>
                  <div class="bar-wrap"><div class="bar-val" style="font-size:9px">18</div><div class="bar" style="height:100%;background:var(--danger)"></div><div class="bar-lbl">Wk23</div></div>
                  <div class="bar-wrap"><div class="bar-val" style="font-size:9px">5</div><div class="bar" style="height:40%;background:var(--warn)"></div><div class="bar-lbl">Wk24</div></div>
                </div>
              </div>
              <div>
                <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:8px">RESPONSE ACTIONS</div>
                <div class="checkbox-item" style="margin-bottom:8px"><input type="checkbox" checked style="accent-color:var(--b)"> Alert issued to regional health officer</div>
                <div class="checkbox-item" style="margin-bottom:8px"><input type="checkbox" checked style="accent-color:var(--b)"> Water source testing ordered</div>
                <div class="checkbox-item" style="margin-bottom:8px"><input type="checkbox"> Mobile response team deployed</div>
                <div class="checkbox-item"><input type="checkbox"> ORS distribution initiated</div>
              </div>
            </div>
          </div></div>
        </div>
        <!-- DISEASE MAP -->
        <div class="portal-page" id="horg-page-disease-map">
          <div class="page-header"><div><h2>Disease Burden Map</h2></div></div>
          <div style="display:grid;grid-template-columns:1fr 280px;gap:16px;height:500px">
            <div class="map-placeholder" style="height:100%"><span>🗺 Disease Burden Map — Cameroon (Colored by Region)</span></div>
            <div style="background:#fff;border:1px solid var(--bdr);border-radius:var(--rad);padding:14px">
              <div class="form-group"><label class="form-label">Disease</label><select class="form-control"><option>Malaria</option><option>Cholera</option><option>HIV</option><option>TB</option><option>Mpox</option></select></div>
              <div class="form-group"><label class="form-label">Date Range</label><input class="form-control" type="date"><input class="form-control" type="date" style="margin-top:4px"></div>
              <div class="form-group"><label class="form-label">Indicator</label><select class="form-control"><option>Incidence</option><option>Mortality</option><option>CFR</option></select></div>
              <hr class="divider">
              <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:8px">LEGEND</div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;font-size:12px"><div style="width:16px;height:16px;background:#FEE2E2;border:1px solid var(--bdr)"></div>High burden</div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;font-size:12px"><div style="width:16px;height:16px;background:#FEF3C7;border:1px solid var(--bdr)"></div>Medium burden</div>
              <div style="display:flex;align-items:center;gap:8px;font-size:12px"><div style="width:16px;height:16px;background:#DCFCE7;border:1px solid var(--bdr)"></div>Low burden</div>
              <hr class="divider">
              <div style="font-size:12px;font-weight:700;margin-bottom:6px">Top Regions</div>
              <div style="font-size:12px;display:flex;justify-content:space-between;padding:4px 0"><span>Far North</span><span class="badge badge-danger">1,240</span></div>
              <div style="font-size:12px;display:flex;justify-content:space-between;padding:4px 0"><span>Littoral</span><span class="badge badge-warn">892</span></div>
              <div style="font-size:12px;display:flex;justify-content:space-between;padding:4px 0"><span>Centre</span><span class="badge badge-info">744</span></div>
            </div>
          </div>
        </div>
        <!-- CARE MAP -->
        <div class="portal-page" id="horg-page-care-map">
          <div class="page-header"><div><h2>Care Map</h2></div></div>
          <div style="display:grid;grid-template-columns:1fr 260px;gap:14px;height:400px">
            <div class="map-placeholder" style="height:100%"><span>🗺 Facility Map — Cameroon</span></div>
            <div style="background:#fff;border:1px solid var(--bdr);border-radius:var(--rad);padding:14px">
              <div class="form-group" style="margin-bottom:10px"><label class="form-label">Type</label><select class="form-control"><option>All Types</option><option>Hospital</option><option>Clinic</option><option>Lab</option><option>Pharmacy</option></select></div>
              <div class="form-group" style="margin-bottom:10px"><label class="form-label">Region</label><select class="form-control"><option>All Regions</option><option>Centre</option><option>Littoral</option></select></div>
              <div class="form-group" style="margin-bottom:10px"><label class="form-label">Services</label><select class="form-control"><option>All Services</option><option>Emergency</option><option>Maternity</option><option>Paediatrics</option></select></div>
              <button class="btn btn-primary" style="width:100%;justify-content:center">Apply Filters</button>
            </div>
          </div>
        </div>
        <!-- DATA EXPORT -->
        <div class="portal-page" id="horg-page-export">
          <div class="page-header"><div><h2>Data Export</h2></div></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Export Configuration</span></div><div class="panel-body">
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Data Type <span>*</span></label><select class="form-control"><option>Patients</option><option>Immunizations</option><option>Visits</option><option>Reports</option></select></div>
              <div class="form-group"><label class="form-label">Format <span>*</span></label><select class="form-control"><option>CSV</option><option>JSON</option><option>FHIR R4 Bundle</option></select></div>
            </div>
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Date From</label><input class="form-control" type="date"></div>
              <div class="form-group"><label class="form-label">Date To</label><input class="form-control" type="date"></div>
            </div>
            <div class="form-group"><label class="form-label">Additional Filters</label><input class="form-control" placeholder="Region, program, facility..."></div>
            <button class="btn btn-primary">Generate Export</button>
          </div></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Download History</span></div><table class="data-table"><thead><tr><th>Export Type</th><th>Format</th><th>Records</th><th>Generated</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            <tr><td>Immunizations</td><td>CSV</td><td>18,420</td><td>10 Jun 2025</td><td><span class="badge badge-ok">Ready</span></td><td><button class="btn btn-primary btn-xs">Download</button></td></tr>
            <tr><td>Patients</td><td>FHIR R4</td><td>5,200</td><td>05 Jun 2025</td><td><span class="badge badge-ok">Ready</span></td><td><button class="btn btn-primary btn-xs">Download</button></td></tr>
          </tbody></table></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== DEVELOPER PORTAL ===== -->
<div class="portal-pane" id="portal-developer">
  <div class="portal-wrap">
    <div class="portal-sidebar">
      <div class="sb-brand"><div class="sb-logo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg></div><div><div class="sb-name">OpesCare</div><div class="sb-sub">Developer Portal</div></div></div>
      <div class="sb-rb"><div class="role-badge">Developer</div></div>
      <nav class="sb-nav">
        <div class="sb-sec">Overview</div>
        <div class="sb-link active" id="dev-nav-dashboard" onclick="goPage('developer','dashboard')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</div>
        <div class="sb-sec">Apps</div>
        <div class="sb-link" id="dev-nav-apps" onclick="goPage('developer','apps')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>My Apps</div>
        <div class="sb-link" id="dev-nav-app-create" onclick="goPage('developer','app-create')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>Create App</div>
        <div class="sb-sec">API</div>
        <div class="sb-link" id="dev-nav-analytics" onclick="goPage('developer','analytics')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Analytics</div>
        <div class="sb-link" id="dev-nav-rate-limits" onclick="goPage('developer','rate-limits')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>Rate Limits</div>
        <div class="sb-link" id="dev-nav-webhooks" onclick="goPage('developer','webhooks')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>Webhooks</div>
        <div class="sb-sec">Access</div>
        <div class="sb-link" id="dev-nav-production" onclick="goPage('developer','production')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Production Requests</div>
        <div class="sb-link" id="dev-nav-fhir" onclick="goPage('developer','fhir')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>FHIR Explorer</div>
        <div class="sb-link" id="dev-nav-sandbox" onclick="goPage('developer','sandbox')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg>Sandbox</div>
        <div class="sb-sec">Docs</div>
        <div class="sb-link" id="dev-nav-changelog" onclick="goPage('developer','changelog')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>Changelog</div>
      </nav>
      <div class="sb-foot"><div class="sb-user"><div class="sb-av">DV</div><div><div class="sb-uname">Dev Tchinda</div><div class="sb-urole">Developer</div></div></div></div>
    </div>
    <div class="portal-main">
      <div class="portal-topbar"><span class="tb-crumb">Developer</span><span class="tb-sep">/</span><span class="tb-page" id="dev-breadcrumb">Dashboard</span><div class="tb-right"><div class="tb-av">DV</div><span class="tb-uname">Dev Tchinda</span></div></div>
      <div class="portal-content">
        <!-- DEV DASHBOARD -->
        <div class="portal-page active" id="dev-page-dashboard">
          <div class="stat-grid cols-4">
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg></div><div class="sc-val">3</div><div class="sc-lbl">My Apps</div></div>
            <div class="stat-card teal"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg></div><div class="sc-val">48,291</div><div class="sc-lbl">API Requests (30d)</div></div>
            <div class="stat-card danger"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div class="sc-val">14</div><div class="sc-lbl">Errors (30d)</div></div>
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div class="sc-val">1</div><div class="sc-lbl">Pending Prod Requests</div></div>
          </div>
          <div class="two-col">
            <div class="panel"><div class="panel-hd"><span class="panel-title">My Apps</span><button class="btn btn-primary btn-sm" onclick="goPage('developer','app-create')">New App</button></div><table class="data-table"><thead><tr><th>App Name</th><th>Environment</th><th>Status</th><th>Requests</th></tr></thead><tbody>
              <tr><td><strong>HealthSync CM</strong></td><td><span class="badge badge-ok">Production</span></td><td><span class="badge badge-ok">Active</span></td><td>44,201</td></tr>
              <tr><td><strong>MomoHealth Pay</strong></td><td><span class="badge badge-info">Sandbox</span></td><td><span class="badge badge-ok">Active</span></td><td>3,890</td></tr>
              <tr><td><strong>PatientConnect</strong></td><td><span class="badge badge-info">Sandbox</span></td><td><span class="badge badge-warn">Pending</span></td><td>200</td></tr>
            </tbody></table></div>
            <div class="panel"><div class="panel-hd"><span class="panel-title">Quick Links</span></div><div class="panel-body" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <button class="btn btn-secondary" style="justify-content:flex-start" onclick="goPage('developer','fhir')"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"/><polyline points="8,6 2,12 8,18"/></svg>FHIR Explorer</button>
              <button class="btn btn-secondary" style="justify-content:flex-start" onclick="goPage('developer','sandbox')"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/></svg>Sandbox</button>
              <button class="btn btn-secondary" style="justify-content:flex-start" onclick="goPage('developer','webhooks')"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/></svg>Webhooks</button>
              <button class="btn btn-secondary" style="justify-content:flex-start" onclick="goPage('developer','changelog')"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>Changelog</button>
            </div></div>
          </div>
        </div>
        <!-- APPS -->
        <div class="portal-page" id="dev-page-apps">
          <div class="page-header"><div><h2>My Apps</h2></div><button class="btn btn-primary" onclick="goPage('developer','app-create')">New App</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>App Name</th><th>Client ID</th><th>Environment</th><th>Scopes</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody>
            <tr><td><strong>HealthSync CM</strong></td><td class="mono" style="font-size:11px">app_7KQP9MP4</td><td><span class="badge badge-ok">Production</span></td><td>read:patient, fhir:read</td><td><span class="badge badge-ok">Active</span></td><td>01 Jan 2025</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
            <tr><td><strong>MomoHealth Pay</strong></td><td class="mono" style="font-size:11px">app_3RT8KP11</td><td><span class="badge badge-info">Sandbox</span></td><td>read:patient, read:prescriptions</td><td><span class="badge badge-ok">Active</span></td><td>15 Mar 2025</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
            <tr><td><strong>PatientConnect</strong></td><td class="mono" style="font-size:11px">app_9MP2JB01</td><td><span class="badge badge-info">Sandbox</span></td><td>read:patient</td><td><span class="badge badge-warn">Pending Approval</span></td><td>01 Jun 2025</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
          </tbody></table></div>
        </div>
        <!-- APP CREATE -->
        <div class="portal-page" id="dev-page-app-create">
          <div class="page-header"><div><h2>Create New App</h2></div></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">App Details</span></div><div class="panel-body">
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">App Name <span>*</span></label><input class="form-control" placeholder="My Health App"></div>
              <div class="form-group"><label class="form-label">Website URL</label><input class="form-control" type="url" placeholder="https://myapp.cm"></div>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" rows="2" placeholder="What does your app do?"></textarea></div>
            <div class="form-group"><label class="form-label">Redirect URIs <span>*</span></label><textarea class="form-control" rows="3" placeholder="https://myapp.cm/callback&#10;https://myapp.cm/auth/callback"></textarea><p class="form-hint">One URI per line</p></div>
            <div class="form-group"><label class="form-label">Requested Scopes</label>
              <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">
                <div><div style="font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px">PATIENT DATA</div>
                  <div class="checkbox-item"><input type="checkbox" checked> read:patient</div>
                  <div class="checkbox-item"><input type="checkbox"> write:patient</div>
                </div>
                <div><div style="font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px">CLINICAL</div>
                  <div class="checkbox-item"><input type="checkbox" checked> read:lab_results</div>
                  <div class="checkbox-item"><input type="checkbox"> write:prescriptions</div>
                  <div class="checkbox-item"><input type="checkbox"> read:prescriptions</div>
                </div>
                <div><div style="font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px">FACILITY</div>
                  <div class="checkbox-item"><input type="checkbox"> read:facility</div>
                </div>
                <div><div style="font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px">FHIR</div>
                  <div class="checkbox-item"><input type="checkbox" checked> fhir:read</div>
                  <div class="checkbox-item"><input type="checkbox"> fhir:write</div>
                </div>
              </div>
            </div>
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Environment</label><select class="form-control"><option>Sandbox</option><option>Production (requires approval)</option></select></div>
            </div>
            <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:16px"><input type="checkbox" style="margin-top:2px;accent-color:var(--b)"><span style="font-size:12px;color:var(--muted)">I agree to the OpesCare Developer Terms of Service and will only access patient data with proper consent</span></div>
            <button class="btn btn-primary">Create App</button>
          </div></div>
        </div>
        <!-- APP DETAIL -->
        <div class="portal-page" id="dev-page-app-detail">
          <div class="page-header"><div><h2>HealthSync CM</h2><p>Production App</p></div></div>
          <div class="two-col">
            <div class="panel"><div class="panel-hd"><span class="panel-title">Credentials</span></div><div class="panel-body">
              <div class="form-group"><label class="form-label">Client ID</label><div style="display:flex;align-items:center;gap:8px"><input class="form-control mono" value="app_7KQP9MP4" readonly style="background:var(--sur2)"><button class="btn btn-secondary btn-sm">Copy</button></div></div>
              <div class="form-group"><label class="form-label">Client Secret</label><div style="display:flex;align-items:center;gap:8px"><input class="form-control mono" value="••••••••••••••••••••••••" readonly style="background:var(--sur2)"><button class="btn btn-secondary btn-sm">Reveal</button><button class="btn btn-warn btn-sm" style="background:var(--warnl);color:var(--warn);border:1px solid rgba(180,83,9,.2)">Rotate</button></div><p class="form-hint">⚠ Rotating the secret will invalidate the current secret immediately</p></div>
              <div class="form-group"><label class="form-label">Webhook URL</label><input class="form-control" placeholder="https://myapp.cm/webhooks/opescare"></div>
            </div></div>
            <div>
              <div class="panel"><div class="panel-hd"><span class="panel-title">Usage (30 days)</span></div><div class="panel-body" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;text-align:center">
                <div style="background:var(--sur2);border-radius:var(--rad);padding:12px"><div style="font-size:20px;font-weight:800;color:var(--b)">1,409</div><div style="font-size:12px;color:var(--muted)">Today</div></div>
                <div style="background:var(--sur2);border-radius:var(--rad);padding:12px"><div style="font-size:20px;font-weight:800;color:var(--b)">8,820</div><div style="font-size:12px;color:var(--muted)">This Week</div></div>
                <div style="background:var(--sur2);border-radius:var(--rad);padding:12px"><div style="font-size:20px;font-weight:800;color:var(--b)">44,201</div><div style="font-size:12px;color:var(--muted)">This Month</div></div>
                <div style="background:var(--sur2);border-radius:var(--rad);padding:12px"><div style="font-size:20px;font-weight:800;color:var(--ok)">99.97%</div><div style="font-size:12px;color:var(--muted)">Success Rate</div></div>
              </div></div>
              <div class="panel" style="border-left:3px solid var(--danger)"><div class="panel-body">
                <div style="font-size:13px;font-weight:700;color:var(--danger);margin-bottom:4px">Danger Zone</div>
                <button class="btn btn-danger btn-sm">Delete App</button>
              </div></div>
            </div>
          </div>
        </div>
        <!-- DEV ANALYTICS -->
        <div class="portal-page" id="dev-page-analytics">
          <div class="page-header"><div><h2>API Analytics</h2></div><div class="ph-actions"><input class="form-control" type="date" style="width:140px"><input class="form-control" type="date" style="width:140px"></div></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Request Volume (Last 30 Days)</span></div><div class="panel-body">
            <div class="bar-chart" style="height:100px">
              <div class="bar-wrap"><div class="bar" style="height:60%"></div><div class="bar-lbl" style="font-size:9px">15 May</div></div>
              <div class="bar-wrap"><div class="bar" style="height:75%"></div><div class="bar-lbl" style="font-size:9px">18</div></div>
              <div class="bar-wrap"><div class="bar" style="height:55%"></div><div class="bar-lbl" style="font-size:9px">21</div></div>
              <div class="bar-wrap"><div class="bar" style="height:80%"></div><div class="bar-lbl" style="font-size:9px">24</div></div>
              <div class="bar-wrap"><div class="bar" style="height:70%"></div><div class="bar-lbl" style="font-size:9px">27</div></div>
              <div class="bar-wrap"><div class="bar" style="height:90%"></div><div class="bar-lbl" style="font-size:9px">30</div></div>
              <div class="bar-wrap"><div class="bar" style="height:100%"></div><div class="bar-lbl" style="font-size:9px">02 Jun</div></div>
              <div class="bar-wrap"><div class="bar" style="height:85%"></div><div class="bar-lbl" style="font-size:9px">05</div></div>
              <div class="bar-wrap"><div class="bar" style="height:78%"></div><div class="bar-lbl" style="font-size:9px">08</div></div>
              <div class="bar-wrap"><div class="bar" style="height:92%;background:var(--tl)"></div><div class="bar-lbl" style="font-size:9px">14</div></div>
            </div>
          </div></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Endpoint Breakdown</span></div><table class="data-table"><thead><tr><th>Endpoint</th><th>Method</th><th>Calls</th><th>Errors</th><th>Avg Latency</th><th>P99</th></tr></thead><tbody>
            <tr><td class="mono">/fhir/Patient</td><td><span class="badge badge-ok">GET</span></td><td>18,429</td><td>3</td><td>42ms</td><td>145ms</td></tr>
            <tr><td class="mono">/fhir/Observation</td><td><span class="badge badge-ok">GET</span></td><td>9,821</td><td>1</td><td>55ms</td><td>187ms</td></tr>
            <tr><td class="mono">/appointments</td><td><span class="badge badge-b">POST</span></td><td>4,202</td><td>0</td><td>89ms</td><td>201ms</td></tr>
            <tr><td class="mono">/verify/health-id</td><td><span class="badge badge-ok">GET</span></td><td>12,735</td><td>2</td><td>28ms</td><td>98ms</td></tr>
          </tbody></table></div>
        </div>
        <!-- RATE LIMITS -->
        <div class="portal-page" id="dev-page-rate-limits">
          <div class="page-header"><div><h2>Rate Limits</h2></div></div>
          <div class="stat-grid cols-3">
            <div class="panel" style="border-left:3px solid var(--muted)"><div class="panel-body"><div style="font-size:16px;font-weight:700">Sandbox</div><div style="font-size:24px;font-weight:800;color:var(--muted);margin:4px 0">100/min</div><div class="progress"><div class="progress-fill" style="width:45%"></div></div><div style="font-size:12px;color:var(--muted);margin-top:4px">45 / 100 requests used</div></div></div>
            <div class="panel" style="border-left:3px solid var(--b)"><div class="panel-body"><div style="font-size:16px;font-weight:700">Standard</div><div style="font-size:24px;font-weight:800;color:var(--b);margin:4px 0">1,000/min</div><div class="progress"><div class="progress-fill" style="width:28%"></div></div><div style="font-size:12px;color:var(--muted);margin-top:4px">280 / 1,000 requests used</div></div></div>
            <div class="panel" style="border-left:3px solid var(--t)"><div class="panel-body"><div style="font-size:16px;font-weight:700">Premium</div><div style="font-size:24px;font-weight:800;color:var(--t);margin:4px 0">10,000/min</div><div class="progress"><div class="progress-fill ok" style="width:12%"></div></div><div style="font-size:12px;color:var(--muted);margin-top:4px">Contact sales for Premium</div></div></div>
          </div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Current Usage — HealthSync CM (Production)</span></div><table class="data-table"><thead><tr><th>Endpoint</th><th>Limit</th><th>Used (last min)</th><th>Status</th></tr></thead><tbody>
            <tr><td class="mono">/fhir/*</td><td>1,000/min</td><td>48</td><td><span class="badge badge-ok">OK</span></td></tr>
            <tr><td class="mono">/appointments</td><td>100/min</td><td>12</td><td><span class="badge badge-ok">OK</span></td></tr>
            <tr><td class="mono">/verify/*</td><td>500/min</td><td>201</td><td><span class="badge badge-warn">40% used</span></td></tr>
          </tbody></table></div>
        </div>
        <!-- WEBHOOKS -->
        <div class="portal-page" id="dev-page-webhooks">
          <div class="page-header"><div><h2>Webhooks</h2></div><button class="btn btn-primary">Add Webhook</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>URL</th><th>Events</th><th>Status</th><th>Last Triggered</th><th>Success Rate</th><th>Actions</th></tr></thead><tbody>
            <tr><td class="mono" style="font-size:11px">https://healthsync.cm/hooks/opes</td><td>patient.updated, lab.result_released</td><td><span class="badge badge-ok">Active</span></td><td>14 Jun 10:02</td><td>99.2%</td><td><button class="btn btn-secondary btn-xs">Test</button> <button class="btn btn-secondary btn-xs">Logs</button></td></tr>
            <tr><td class="mono" style="font-size:11px">https://momopay.cm/hooks/rx</td><td>prescription.issued</td><td><span class="badge badge-ok">Active</span></td><td>12 Jun 14:30</td><td>100%</td><td><button class="btn btn-secondary btn-xs">Test</button> <button class="btn btn-secondary btn-xs">Logs</button></td></tr>
          </tbody></table></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Add Webhook</span></div><div class="panel-body">
            <div class="form-group"><label class="form-label">Endpoint URL <span>*</span></label><input class="form-control" type="url" placeholder="https://myapp.cm/webhooks/opescare"></div>
            <div class="form-group"><label class="form-label">Secret (for HMAC verification)</label><input class="form-control mono" placeholder="whsec_..."></div>
            <div class="form-group"><label class="form-label">Events to Listen For</label>
              <div class="checkbox-group" style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                <div class="checkbox-item"><input type="checkbox" checked> patient.updated</div>
                <div class="checkbox-item"><input type="checkbox" checked> appointment.created</div>
                <div class="checkbox-item"><input type="checkbox"> lab.result_released</div>
                <div class="checkbox-item"><input type="checkbox" checked> prescription.issued</div>
                <div class="checkbox-item"><input type="checkbox"> appointment.cancelled</div>
                <div class="checkbox-item"><input type="checkbox"> patient.consent_changed</div>
              </div>
            </div>
            <div style="display:flex;gap:8px"><button class="btn btn-primary">Save Webhook</button><button class="btn btn-secondary">Send Test Event</button></div>
          </div></div>
        </div>
        <!-- PRODUCTION REQUESTS -->
        <div class="portal-page" id="dev-page-production">
          <div class="page-header"><div><h2>Production Requests</h2></div><button class="btn btn-primary">Request Access</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>App</th><th>Use Case</th><th>Status</th><th>Submitted</th><th>Reviewer</th><th>Notes</th></tr></thead><tbody>
            <tr><td><strong>HealthSync CM</strong></td><td>Patient health record sync for referrals</td><td><span class="badge badge-ok">Approved</span></td><td>15 Dec 2024</td><td>Admin Fouda</td><td>Approved with DPA signed</td></tr>
            <tr><td><strong>PatientConnect</strong></td><td>Patient self-service app</td><td><span class="badge badge-warn">Under Review</span></td><td>01 Jun 2025</td><td>Admin Biya</td><td>Awaiting DPA submission</td></tr>
          </tbody></table></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Request Production Access</span></div><div class="panel-body">
            <div class="form-group"><label class="form-label">Select App <span>*</span></label><select class="form-control"><option>PatientConnect (Sandbox)</option><option>MomoHealth Pay (Sandbox)</option></select></div>
            <div class="form-group"><label class="form-label">Use Case Description <span>*</span></label><textarea class="form-control" rows="3" placeholder="Describe how your app will use patient data..."></textarea></div>
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Expected Daily Volume</label><input class="form-control" placeholder="e.g. 5,000 API calls/day"></div>
              <div class="form-group"><label class="form-label">Patient Population</label><input class="form-control" placeholder="e.g. Cameroonian adult patients"></div>
            </div>
            <div class="form-group"><label class="form-label">Data Types Needed</label>
              <div class="checkbox-group" style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                <div class="checkbox-item"><input type="checkbox" checked> Patient demographics</div>
                <div class="checkbox-item"><input type="checkbox"> Lab results</div>
                <div class="checkbox-item"><input type="checkbox" checked> Appointments</div>
                <div class="checkbox-item"><input type="checkbox"> Prescriptions</div>
              </div>
            </div>
            <div class="form-group"><label class="form-label">Security Measures</label><textarea class="form-control" placeholder="Describe encryption, access controls, audit logging..."></textarea></div>
            <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:16px"><input type="checkbox" style="margin-top:2px;accent-color:var(--b)"><span style="font-size:12px;color:var(--muted)">I attest compliance with GDPR, Cameroonian health data law, and OpesCare Data Processing Agreement</span></div>
            <button class="btn btn-primary">Submit Production Request</button>
          </div></div>
        </div>
        <!-- FHIR EXPLORER -->
        <div class="portal-page" id="dev-page-fhir">
          <div class="page-header"><div><h2>FHIR R4 Explorer</h2></div></div>
          <div class="two-col">
            <div>
              <div class="panel"><div class="panel-hd"><span class="panel-title">Base URL</span></div><div class="panel-body">
                <div class="code-block"><span class="k">https://</span>api.opescare.cm<span class="k">/fhir/r4</span></div>
                <div style="font-size:12px;color:var(--muted)">All FHIR requests require <span class="mono" style="font-size:11px">Authorization: Bearer {access_token}</span></div>
              </div></div>
              <div class="panel"><div class="panel-hd"><span class="panel-title">Resources</span></div><div class="panel-body" style="padding:8px">
                <div class="fhir-resource"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Patient</div>
                <div class="fhir-resource"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>Observation</div>
                <div class="fhir-resource"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>MedicationRequest</div>
                <div class="fhir-resource"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/></svg>Appointment</div>
                <div class="fhir-resource"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18"/></svg>DiagnosticReport</div>
                <div class="fhir-resource"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 20h.01M7 20v-4M12 20v-8M17 20V8M22 4v16"/></svg>Immunization</div>
              </div></div>
            </div>
            <div>
              <div class="panel"><div class="panel-hd"><span class="panel-title">Try It — FHIR Request</span></div><div class="panel-body">
                <div style="display:flex;gap:8px;margin-bottom:10px">
                  <select class="form-control" style="width:90px"><option>GET</option><option>POST</option><option>PUT</option></select>
                  <input class="form-control mono" value="/fhir/r4/Patient/CM-HID-7KQ9-MP42-X8D1" style="font-size:12px">
                </div>
                <div class="form-group"><label class="form-label">Request Body (for POST/PUT)</label><textarea class="form-control mono" style="font-size:12px" rows="3" placeholder='{"resourceType": "Patient", ...}'></textarea></div>
                <button class="btn btn-primary btn-sm" style="margin-bottom:10px">Send Request</button>
                <div style="font-size:11px;font-weight:700;color:var(--muted);margin-bottom:4px">RESPONSE — 200 OK · 42ms</div>
                <div class="code-block" style="font-size:11px;max-height:200px;overflow-y:auto">{
  <span class="k">"resourceType"</span>: <span class="s">"Patient"</span>,
  <span class="k">"id"</span>: <span class="s">"CM-HID-7KQ9-MP42-X8D1"</span>,
  <span class="k">"name"</span>: [{
    <span class="k">"family"</span>: <span class="s">"Mbarga"</span>,
    <span class="k">"given"</span>: [<span class="s">"Alain Christophe"</span>]
  }],
  <span class="k">"birthDate"</span>: <span class="s">"1985-03-15"</span>,
  <span class="k">"gender"</span>: <span class="s">"male"</span>,
  <span class="k">"identifier"</span>: [{
    <span class="k">"system"</span>: <span class="s">"https://opescare.cm/health-id"</span>,
    <span class="k">"value"</span>: <span class="s">"CM-HID-7KQ9-MP42-X8D1"</span>
  }]
}</div>
              </div></div>
            </div>
          </div>
        </div>
        <!-- SANDBOX -->
        <div class="portal-page" id="dev-page-sandbox">
          <div class="page-header"><div><h2>Sandbox Environment</h2></div><button class="btn btn-danger btn-sm">Reset Sandbox</button></div>
          <div class="alert alert-info"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg><div>The sandbox environment uses synthetic Cameroonian patient data. No real patient data is used. Sandbox API calls do not count toward your production rate limits.</div></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Sandbox API Key</span></div><div class="panel-body"><div style="display:flex;align-items:center;gap:8px"><input class="form-control mono" value="sk_sandbox_cm_7KQ9MP42X8D1abc123def456" readonly style="background:var(--sur2);font-size:12px"><button class="btn btn-secondary btn-sm">Copy</button></div></div></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Test Patients</span></div><table class="data-table"><thead><tr><th>Name</th><th>Health ID</th><th>DOB</th><th>Conditions</th><th>Actions</th></tr></thead><tbody>
            <tr><td>Test Patient Alpha</td><td class="mono">TEST-HID-0001-AA01-T001</td><td>1985-03-15</td><td>Hypertension, Diabetes</td><td><button class="btn btn-secondary btn-xs">Copy HID</button></td></tr>
            <tr><td>Test Patient Beta</td><td class="mono">TEST-HID-0002-BB02-T002</td><td>1990-11-08</td><td>Malaria (active)</td><td><button class="btn btn-secondary btn-xs">Copy HID</button></td></tr>
            <tr><td>Test Patient Gamma</td><td class="mono">TEST-HID-0003-CC03-T003</td><td>1978-01-22</td><td>HIV, TB</td><td><button class="btn btn-secondary btn-xs">Copy HID</button></td></tr>
            <tr><td>Test Patient Delta</td><td class="mono">TEST-HID-0004-DD04-T004</td><td>1995-07-14</td><td>Pregnancy (30w)</td><td><button class="btn btn-secondary btn-xs">Copy HID</button></td></tr>
            <tr><td>Test Patient Epsilon</td><td class="mono">TEST-HID-0005-EE05-T005</td><td>2018-03-30</td><td>Paediatric — healthy</td><td><button class="btn btn-secondary btn-xs">Copy HID</button></td></tr>
          </tbody></table></div>
        </div>
        <!-- CHANGELOG -->
        <div class="portal-page" id="dev-page-changelog">
          <div class="page-header"><div><h2>API Changelog</h2></div></div>
          <div class="panel"><div class="panel-body">
            <div style="border-left:3px solid var(--b);padding-left:16px;margin-bottom:20px">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px"><span style="font-size:16px;font-weight:800;color:var(--txt)" class="mono">v2.4.0</span><span class="badge badge-ok">Feature</span><span style="font-size:12px;color:var(--muted)">14 Jun 2025</span></div>
              <div style="font-size:13px;color:var(--txt2)">Added FHIR R4 Immunization resource. Added MTN MoMo webhook event types. Improved Health ID verification response speed by 40%.</div>
            </div>
            <div style="border-left:3px solid var(--ok);padding-left:16px;margin-bottom:20px">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px"><span style="font-size:16px;font-weight:800;color:var(--txt)" class="mono">v2.3.2</span><span class="badge badge-b">Fix</span><span style="font-size:12px;color:var(--muted)">01 Jun 2025</span></div>
              <div style="font-size:13px;color:var(--txt2)">Fixed pagination bug in /fhir/r4/Patient endpoint. Fixed webhook delivery retry logic for 5xx errors.</div>
            </div>
            <div style="border-left:3px solid var(--warn);padding-left:16px;margin-bottom:20px">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px"><span style="font-size:16px;font-weight:800;color:var(--txt)" class="mono">v2.3.0</span><span class="badge badge-warn">Breaking</span><span style="font-size:12px;color:var(--muted)">15 May 2025</span></div>
              <div style="font-size:13px;color:var(--txt2)">BREAKING: /api/v1/patient now requires explicit <span class="mono">scope=read:patient</span> in token. Old tokens must be re-issued. See migration guide.</div>
            </div>
            <div style="border-left:3px solid var(--b);padding-left:16px">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px"><span style="font-size:16px;font-weight:800;color:var(--txt)" class="mono">v2.2.0</span><span class="badge badge-ok">Feature</span><span style="font-size:12px;color:var(--muted)">01 Apr 2025</span></div>
              <div style="font-size:13px;color:var(--txt2)">Added Developer Portal. Added sandbox environment with synthetic patient data. Added rate limit headers to all responses.</div>
            </div>
          </div></div>
        </div>
      </div>
    </div>
  </div>
</div>
'''

with open(r'C:\laragon\www\opescare\apps\api-laravel\public\theme-preview\parts\healthorg_developer.html', 'w', encoding='utf-8') as f:
    f.write(content)
print("HealthOrg+Developer written:", len(content), "chars")
