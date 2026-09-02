
content = r'''
<!-- ===== INSURANCE PORTAL ===== -->
<div class="portal-pane" id="portal-insurance">
  <div class="portal-wrap">
    <div class="portal-sidebar">
      <div class="sb-brand"><div class="sb-logo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div><div class="sb-name">OpesCare</div><div class="sb-sub">Insurance Portal</div></div></div>
      <div class="sb-rb"><div class="role-badge">Insurance Admin</div></div>
      <nav class="sb-nav">
        <div class="sb-sec">Overview</div>
        <div class="sb-link active" id="ins-nav-dashboard" onclick="goPage('insurance','dashboard')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</div>
        <div class="sb-sec">Network</div>
        <div class="sb-link" id="ins-nav-providers" onclick="goPage('insurance','providers')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Providers</div>
        <div class="sb-link" id="ins-nav-plans" onclick="goPage('insurance','plans')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>Plans</div>
        <div class="sb-sec">Patients</div>
        <div class="sb-link" id="ins-nav-policies" onclick="goPage('insurance','policies')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>Policies</div>
        <div class="sb-sec">Claims</div>
        <div class="sb-link" id="ins-nav-preauths" onclick="goPage('insurance','preauths')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Pre-Authorization</div>
        <div class="sb-link" id="ins-nav-claims" onclick="goPage('insurance','claims')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Claims Processing</div>
        <div class="sb-sec">Reports</div>
        <div class="sb-link" id="ins-nav-analytics" onclick="goPage('insurance','analytics')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Analytics</div>
      </nav>
      <div class="sb-foot"><div class="sb-user"><div class="sb-av">IA</div><div><div class="sb-uname">Activa Santé SA</div><div class="sb-urole">Insurance Admin</div></div></div></div>
    </div>
    <div class="portal-main">
      <div class="portal-topbar"><span class="tb-crumb">Insurance</span><span class="tb-sep">/</span><span class="tb-page" id="ins-breadcrumb">Dashboard</span><div class="tb-right"><div class="tb-av">IA</div><span class="tb-uname">Activa Santé</span></div></div>
      <div class="portal-content">
        <!-- INSURANCE DASHBOARD -->
        <div class="portal-page active" id="ins-page-dashboard">
          <div class="stat-grid" style="grid-template-columns:repeat(7,1fr)">
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div><div class="sc-val">48</div><div class="sc-lbl">Providers</div></div>
            <div class="stat-card teal"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/></svg></div><div class="sc-val">12</div><div class="sc-lbl">Active Plans</div></div>
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><div class="sc-val">8,421</div><div class="sc-lbl">Policies</div></div>
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/></svg></div><div class="sc-val">34</div><div class="sc-lbl">Pending Pre-Auth</div></div>
            <div class="stat-card danger"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div class="sc-val">127</div><div class="sc-lbl">Open Claims</div></div>
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg></div><div class="sc-val">2,041</div><div class="sc-lbl">Paid Claims</div></div>
            <div class="stat-card info"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div class="sc-val">184M</div><div class="sc-lbl">Total Value (XAF)</div></div>
          </div>
          <div class="two-col">
            <div class="panel"><div class="panel-hd"><span class="panel-title">Recent Claims</span></div><table class="data-table"><thead><tr><th>Claim #</th><th>Patient</th><th>Amount (XAF)</th><th>Status</th></tr></thead><tbody>
              <tr><td class="mono">CLM-2025-0892</td><td>Mbarga Alain</td><td>45,000</td><td><span class="badge badge-ok">Approved</span></td></tr>
              <tr><td class="mono">CLM-2025-0901</td><td>Fouda Marie Claire</td><td>125,000</td><td><span class="badge badge-warn">Under Review</span></td></tr>
              <tr><td class="mono">CLM-2025-0876</td><td>Kamga Patrick</td><td>38,500</td><td><span class="badge badge-ok">Paid</span></td></tr>
              <tr><td class="mono">CLM-2025-0855</td><td>Ngono Suzanne</td><td>62,000</td><td><span class="badge badge-ok">Paid</span></td></tr>
            </tbody></table></div>
            <div class="panel"><div class="panel-hd"><span class="panel-title">Pending Pre-Auths</span></div><table class="data-table"><thead><tr><th>Ref #</th><th>Patient</th><th>Amount (XAF)</th><th>Status</th></tr></thead><tbody>
              <tr><td class="mono">PA-2025-0042</td><td>Fouda Marie Claire</td><td>285,000</td><td><span class="badge badge-warn">Pending</span></td></tr>
              <tr><td class="mono">PA-2025-0041</td><td>Biya Jean-Pierre</td><td>120,000</td><td><span class="badge badge-warn">Pending</span></td></tr>
              <tr><td class="mono">PA-2025-0040</td><td>Tchouangang Nathalie</td><td>75,000</td><td><span class="badge badge-ok">Approved</span></td></tr>
            </tbody></table></div>
          </div>
        </div>
        <!-- PROVIDERS -->
        <div class="portal-page" id="ins-page-providers">
          <div class="page-header"><div><h2>Providers</h2></div><button class="btn btn-primary">Add Provider</button></div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Name</th><th>Type</th><th>Plans</th><th>Contact</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><strong>CNPS Cameroun</strong></td><td>Government</td><td>4</td><td>cnps.cm · +237 222 22 20</td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Activa Assurances</strong></td><td>Private (HMO)</td><td>6</td><td>activa.cm · +237 233 50 00</td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Sanlam Cameroun</strong></td><td>Private</td><td>3</td><td>sanlam.cm · +237 222 23 30</td><td><span class="badge badge-ok">Active</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td><strong>Garant Assurances</strong></td><td>Mutual</td><td>2</td><td>garant.cm</td><td><span class="badge badge-warn">Limited</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- PLANS -->
        <div class="portal-page" id="ins-page-plans">
          <div class="page-header"><div><h2>Plans</h2></div><button class="btn btn-primary">Add Plan</button></div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Plan Name</th><th>Provider</th><th>Coverage %</th><th>Max Annual (XAF)</th><th>Deductible (XAF)</th><th>Status</th></tr></thead>
              <tbody>
                <tr><td><strong>Plan Standard Maladie</strong></td><td>CNPS Cameroun</td><td>80%</td><td>2,000,000</td><td>10,000</td><td><span class="badge badge-ok">Active</span></td></tr>
                <tr><td><strong>Santé Plus</strong></td><td>Activa Assurances</td><td>60%</td><td>1,500,000</td><td>25,000</td><td><span class="badge badge-ok">Active</span></td></tr>
                <tr><td><strong>Assurance Hospitalisation</strong></td><td>Sanlam Cameroun</td><td>90%</td><td>5,000,000</td><td>50,000</td><td><span class="badge badge-ok">Active</span></td></tr>
                <tr><td><strong>Plan Maternité</strong></td><td>CNPS Cameroun</td><td>100%</td><td>800,000</td><td>0</td><td><span class="badge badge-ok">Active</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- POLICIES -->
        <div class="portal-page" id="ins-page-policies">
          <div class="page-header"><div><h2>Policies</h2></div></div>
          <div class="filter-bar">
            <input class="form-control mono" placeholder="Search Health ID..." style="width:220px">
            <select class="form-control"><option>All Providers</option><option>CNPS</option><option>Activa</option></select>
            <select class="form-control"><option>All Statuses</option><option>Active</option><option>Expired</option></select>
            <button class="btn btn-secondary btn-sm">Search</button>
          </div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Patient</th><th>Provider</th><th>Plan</th><th>Policy #</th><th>Coverage</th><th>Valid Until</th><th>Status</th></tr></thead>
              <tbody>
                <tr><td><strong>Mbarga Alain</strong></td><td>CNPS</td><td>Plan Standard</td><td class="mono">POL-CM-00234</td><td>80%</td><td>31 Dec 2025</td><td><span class="badge badge-ok">Active</span></td></tr>
                <tr><td><strong>Fouda Marie Claire</strong></td><td>Activa</td><td>Santé Plus</td><td class="mono">ACT-20298-CM</td><td>60%</td><td>20 Jun 2025</td><td><span class="badge badge-warn">Expiring</span></td></tr>
                <tr><td><strong>Kamga Patrick</strong></td><td>CNPS</td><td>Plan Standard</td><td class="mono">POL-CM-00892</td><td>80%</td><td>31 Dec 2025</td><td><span class="badge badge-ok">Active</span></td></tr>
                <tr><td><strong>Ngono Suzanne</strong></td><td>Sanlam</td><td>Assurance Hosp.</td><td class="mono">SAN-CM-1042</td><td>90%</td><td>30 Jun 2025</td><td><span class="badge badge-ok">Active</span></td></tr>
                <tr><td><strong>Biya Jean-Pierre</strong></td><td>CNPS</td><td>Plan Standard</td><td class="mono">POL-CM-01124</td><td>80%</td><td>31 Dec 2024</td><td><span class="badge badge-danger">Expired</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- PRE-AUTHS -->
        <div class="portal-page" id="ins-page-preauths">
          <div class="page-header"><div><h2>Pre-Authorization</h2></div></div>
          <div class="filter-bar">
            <select class="form-control"><option>All Statuses</option><option>Pending</option><option>Approved</option><option>Rejected</option></select>
            <input class="form-control" type="date" style="width:150px">
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Ref #</th><th>Patient</th><th>Facility</th><th>Procedure</th><th>Amount (XAF)</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td class="mono">PA-2025-0042</td><td><strong>Fouda Marie Claire</strong></td><td>CHU Yaoundé</td><td>Coronary angiography</td><td>285,000</td><td>14 Jun</td><td><span class="badge badge-warn">Pending</span></td><td><button class="btn btn-primary btn-xs">Review</button></td></tr>
                <tr><td class="mono">PA-2025-0041</td><td><strong>Biya Jean-Pierre</strong></td><td>CHU Yaoundé</td><td>Abdominal surgery</td><td>120,000</td><td>13 Jun</td><td><span class="badge badge-warn">Pending</span></td><td><button class="btn btn-primary btn-xs">Review</button></td></tr>
                <tr><td class="mono">PA-2025-0040</td><td><strong>Tchouangang Nathalie</strong></td><td>Clinique Paix</td><td>MRI Brain</td><td>75,000</td><td>12 Jun</td><td><span class="badge badge-ok">Approved</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td class="mono">PA-2025-0038</td><td><strong>Kamga Patrick</strong></td><td>Hôpital Central</td><td>Laparoscopy</td><td>180,000</td><td>10 Jun</td><td><span class="badge badge-danger">Rejected</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
              </tbody>
            </table>
          </div>
          <!-- Pre-auth Detail -->
          <div class="panel" style="border-left:3px solid var(--warn)">
            <div class="panel-hd"><span class="panel-title">Review — PA-2025-0042 · Fouda Marie Claire</span></div>
            <div class="panel-body">
              <div class="two-col">
                <div>
                  <div style="background:var(--sur2);border-radius:var(--rad);padding:14px;margin-bottom:12px;font-size:13px">
                    <div><strong>Patient:</strong> Fouda Marie Claire (CM-HID-3RT8-KP11-W2X4)</div>
                    <div><strong>Policy:</strong> ACT-20298-CM · Activa Santé Plus (60% coverage)</div>
                    <div><strong>Facility:</strong> CHU de Yaoundé</div>
                    <div><strong>Procedure:</strong> Coronary angiography (CPT 93454)</div>
                    <div><strong>Requesting Doctor:</strong> Dr. Ndongo Emmanuel (Cardiology)</div>
                    <div><strong>Requested Amount:</strong> 285,000 XAF</div>
                    <div><strong>Clinical Justification:</strong> Unstable angina with elevated troponin. Requires urgent coronary imaging to rule out STEMI.</div>
                  </div>
                  <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:8px">Supporting Documents</div>
                  <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--sur2);border-radius:var(--rads);margin-bottom:4px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--ok)" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg><span style="font-size:12px">Troponin result (14 Jun) — Uploaded</span></div>
                  <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--sur2);border-radius:var(--rads);margin-bottom:4px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--ok)" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg><span style="font-size:12px">ECG 12-lead (14 Jun) — Uploaded</span></div>
                  <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--sur2);border-radius:var(--rads)"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--warn)" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span style="font-size:12px">Prior auth form — Pending</span></div>
                </div>
                <div class="panel" style="border:none;box-shadow:none;background:var(--sur2);margin-bottom:0">
                  <div class="panel-body">
                    <div style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:10px">REVIEWER DECISION</div>
                    <div style="display:flex;gap:8px;margin-bottom:12px">
                      <button class="btn btn-ok btn-sm">Approve Full</button>
                      <button class="btn btn-warn btn-sm">Partial</button>
                      <button class="btn btn-danger btn-sm">Reject</button>
                    </div>
                    <div class="form-group"><label class="form-label">Approved Amount (XAF)</label><input class="form-control" value="171,000" placeholder="60% of 285,000"></div>
                    <div class="form-group"><label class="form-label">Notes / Conditions</label><textarea class="form-control" rows="3" placeholder="Decision notes..."></textarea></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- CLAIMS -->
        <div class="portal-page" id="ins-page-claims">
          <div class="page-header"><div><h2>Claims Processing</h2></div><div class="ph-actions"><button class="btn btn-secondary btn-sm">Export</button></div></div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Claim #</th><th>Patient</th><th>Facility</th><th>Services</th><th>Amount (XAF)</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td class="mono">CLM-2025-0901</td><td><strong>Fouda Marie Claire</strong></td><td>CHU Yaoundé</td><td>Emergency, Troponin, CBC</td><td>125,000</td><td>14 Jun</td><td><span class="badge badge-warn">Under Review</span></td><td><button class="btn btn-primary btn-xs">Process</button></td></tr>
                <tr><td class="mono">CLM-2025-0892</td><td><strong>Mbarga Alain</strong></td><td>CHU Yaoundé</td><td>Consultation, ECG</td><td>45,000</td><td>10 Jun</td><td><span class="badge badge-ok">Approved</span></td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td class="mono">CLM-2025-0876</td><td><strong>Kamga Patrick</strong></td><td>CHU Yaoundé</td><td>Consultation, HbA1c</td><td>38,500</td><td>12 Jun</td><td><span class="badge badge-ok">Paid</span></td><td><button class="btn btn-secondary btn-xs">Receipt</button></td></tr>
                <tr><td class="mono">CLM-2025-0855</td><td><strong>Ngono Suzanne</strong></td><td>Hôpital Central</td><td>Consultation, Blood Cx</td><td>62,000</td><td>08 Jun</td><td><span class="badge badge-ok">Paid</span></td><td><button class="btn btn-secondary btn-xs">Receipt</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- INSURANCE ANALYTICS -->
        <div class="portal-page" id="ins-page-analytics">
          <div class="page-header"><div><h2>Analytics</h2></div></div>
          <div class="stat-grid cols-3">
            <div class="stat-card ok"><div class="sc-val">184,200,000</div><div class="sc-lbl">Total Claims Value YTD (XAF)</div></div>
            <div class="stat-card"><div class="sc-val">73.2%</div><div class="sc-lbl">Claims Approval Rate</div></div>
            <div class="stat-card warn"><div class="sc-val">14.1%</div><div class="sc-lbl">Claims Loss Ratio</div></div>
          </div>
          <div class="two-col">
            <div class="panel"><div class="panel-hd"><span class="panel-title">Top Diagnoses by Cost</span></div><div class="panel-body">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span>Hypertension (I10)</span><div style="display:flex;align-items:center;gap:8px"><div class="progress" style="width:120px"><div class="progress-fill" style="width:85%"></div></div><span style="font-size:12px">48.2M XAF</span></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span>Malaria (B54)</span><div style="display:flex;align-items:center;gap:8px"><div class="progress" style="width:120px"><div class="progress-fill" style="width:65%"></div></div><span style="font-size:12px">36.8M XAF</span></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span>Pneumonia (J18.9)</span><div style="display:flex;align-items:center;gap:8px"><div class="progress" style="width:120px"><div class="progress-fill" style="width:50%"></div></div><span style="font-size:12px">28.4M XAF</span></div></div>
            </div></div>
            <div class="panel"><div class="panel-hd"><span class="panel-title">Fraud Indicators</span></div><div class="panel-body">
              <div class="alert alert-warn"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg><div>2 providers with anomalous billing patterns. <a style="color:var(--warn);cursor:pointer;font-weight:600">Review →</a></div></div>
              <div style="font-size:13px;color:var(--muted)">No critical fraud alerts at this time.</div>
            </div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== LAB PORTAL ===== -->
<div class="portal-pane" id="portal-lab">
  <div class="portal-wrap">
    <div class="portal-sidebar">
      <div class="sb-brand"><div class="sb-logo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg></div><div><div class="sb-name">OpesCare</div><div class="sb-sub">Lab Portal</div></div></div>
      <div class="sb-rb"><div class="role-badge">Lab Technician</div></div>
      <nav class="sb-nav">
        <div class="sb-sec">Overview</div>
        <div class="sb-link active" id="lab-nav-dashboard" onclick="goPage('lab','dashboard')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</div>
        <div class="sb-link" id="lab-nav-critical" onclick="goPage('lab','critical')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>Critical Values</div>
        <div class="sb-sec">Workflow</div>
        <div class="sb-link" id="lab-nav-orders" onclick="goPage('lab','orders')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg>Orders</div>
        <div class="sb-link" id="lab-nav-samples" onclick="goPage('lab','samples')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4"/></svg>Sample Collection</div>
        <div class="sb-link" id="lab-nav-entry" onclick="goPage('lab','entry')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Result Entry</div>
        <div class="sb-link" id="lab-nav-validation" onclick="goPage('lab','validation')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>Validation</div>
        <div class="sb-sec">Reports</div>
        <div class="sb-link" id="lab-nav-reports" onclick="goPage('lab','reports')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>Reports</div>
        <div class="sb-link" id="lab-nav-qc" onclick="goPage('lab','qc')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>Quality Control</div>
      </nav>
      <div class="sb-foot"><div class="sb-user"><div class="sb-av">TK</div><div><div class="sb-uname">Tech Kouam</div><div class="sb-urole">Lab Technician · CHU</div></div></div></div>
    </div>
    <div class="portal-main">
      <div class="portal-topbar"><span class="tb-crumb">Lab</span><span class="tb-sep">/</span><span class="tb-page" id="lab-breadcrumb">Dashboard</span><div class="tb-right"><div class="tb-av">TK</div><span class="tb-uname">Tech Kouam</span></div></div>
      <div class="portal-content">
        <!-- LAB DASHBOARD -->
        <div class="portal-page active" id="lab-page-dashboard">
          <div class="stat-grid cols-4">
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/></svg></div><div class="sc-val">18</div><div class="sc-lbl">Pending Orders</div></div>
            <div class="stat-card teal"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div class="sc-val">7</div><div class="sc-lbl">In Progress</div></div>
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg></div><div class="sc-val">42</div><div class="sc-lbl">Completed Today</div></div>
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg></div><div class="sc-val">2.8h</div><div class="sc-lbl">Avg TAT</div></div>
          </div>
          <div class="alert alert-danger"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg><div><strong>2 Critical Values</strong> require immediate notification to attending physicians. <a style="color:var(--danger);cursor:pointer;font-weight:600" onclick="goPage('lab','critical')">View Critical Values →</a></div></div>
          <div class="two-col">
            <div class="panel"><div class="panel-hd"><span class="panel-title">Orders by Test Type (Today)</span></div><div class="panel-body">
              <div class="bar-chart">
                <div class="bar-wrap"><div class="bar-val">12</div><div class="bar" style="height:80%"></div><div class="bar-lbl">CBC</div></div>
                <div class="bar-wrap"><div class="bar-val">8</div><div class="bar" style="height:53%"></div><div class="bar-lbl">HbA1c</div></div>
                <div class="bar-wrap"><div class="bar-val">15</div><div class="bar" style="height:100%"></div><div class="bar-lbl">Malaria</div></div>
                <div class="bar-wrap"><div class="bar-val">6</div><div class="bar" style="height:40%"></div><div class="bar-lbl">Lipid</div></div>
                <div class="bar-wrap"><div class="bar-val">4</div><div class="bar" style="height:27%"></div><div class="bar-lbl">Culture</div></div>
                <div class="bar-wrap"><div class="bar-val">9</div><div class="bar" style="height:60%"></div><div class="bar-lbl">Urine</div></div>
              </div>
            </div></div>
            <div class="panel"><div class="panel-hd"><span class="panel-title">Today's Workload by Technician</span></div><table class="data-table"><thead><tr><th>Technician</th><th>Assigned</th><th>Completed</th><th>Pending</th></tr></thead><tbody>
              <tr><td>Tech Kouam</td><td>18</td><td>12</td><td>6</td></tr>
              <tr><td>Tech Mbemba</td><td>15</td><td>10</td><td>5</td></tr>
              <tr><td>Tech Atangana</td><td>12</td><td>9</td><td>3</td></tr>
            </tbody></table></div>
          </div>
        </div>
        <!-- CRITICAL VALUES -->
        <div class="portal-page" id="lab-page-critical">
          <div class="page-header"><div><h2>Critical Values</h2><p>Requires immediate physician notification</p></div></div>
          <div class="alert alert-danger"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg><div>All critical values must be reported to the ordering physician within 30 minutes per laboratory protocol.</div></div>
          <div class="panel" style="border-left:3px solid var(--danger)"><div class="panel-body">
            <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:12px">
              <div style="flex:1">
                <div style="font-size:14px;font-weight:700;color:var(--danger)">Troponin I — CRITICAL HIGH</div>
                <div style="font-size:13px;color:var(--muted)">Patient: Fouda Marie Claire (CM-HID-3RT8-KP11-W2X4)</div>
                <div style="font-size:13px;margin-top:4px">Value: <strong style="color:var(--danger)">4.82 ng/mL</strong> · Reference: &lt;0.04 ng/mL · Flag: <span class="badge badge-danger">CRITICAL HIGH</span></div>
                <div style="font-size:12px;color:var(--muted);margin-top:4px">Order: ORD-94782 · CHU de Yaoundé · 14 Jun 09:12</div>
              </div>
              <div>
                <div style="font-size:12px;color:var(--muted)">Ordering Physician</div>
                <div style="font-size:13px;font-weight:600">Dr. Ndongo Emmanuel</div>
                <div style="font-size:12px;color:var(--muted)">Not yet notified</div>
                <button class="btn btn-danger btn-sm" style="margin-top:8px">Notify Now</button>
              </div>
            </div>
          </div></div>
          <div class="panel" style="border-left:3px solid var(--danger)"><div class="panel-body">
            <div style="display:flex;align-items:flex-start;gap:16px">
              <div style="flex:1">
                <div style="font-size:14px;font-weight:700;color:var(--danger)">Blood Glucose — CRITICAL HIGH</div>
                <div style="font-size:13px;color:var(--muted)">Patient: Kamga Patrick (CM-HID-KM44-PP91-X3Z8)</div>
                <div style="font-size:13px;margin-top:4px">Value: <strong style="color:var(--danger)">28.4 mmol/L</strong> · Reference: 3.9–6.1 mmol/L · Flag: <span class="badge badge-danger">PANIC HIGH</span></div>
                <div style="font-size:12px;color:var(--muted);margin-top:4px">Order: ORD-94801 · CHU de Yaoundé · 14 Jun 10:01</div>
              </div>
              <div>
                <div style="font-size:12px;color:var(--muted)">Ordering Physician</div>
                <div style="font-size:13px;font-weight:600">Dr. Abena Martin</div>
                <div style="font-size:12px;color:var(--ok)">Notified 10:08</div>
                <button class="btn btn-ok btn-sm" style="margin-top:8px">Acknowledge</button>
              </div>
            </div>
          </div></div>
        </div>
        <!-- LAB ORDERS -->
        <div class="portal-page" id="lab-page-orders">
          <div class="page-header"><div><h2>Lab Orders</h2></div></div>
          <div class="filter-bar">
            <select class="form-control"><option>All Priorities</option><option>STAT</option><option>Urgent</option><option>Routine</option></select>
            <select class="form-control"><option>All Statuses</option><option>Pending</option><option>Processing</option><option>Resulted</option></select>
            <input class="form-control" type="date" style="width:150px">
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Order #</th><th>Patient</th><th>Test Panel</th><th>Doctor</th><th>Priority</th><th>Ordered</th><th>Status</th><th>Sample</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td class="mono">ORD-94782</td><td><strong>Fouda Marie Claire</strong></td><td>Troponin I, CBC, Metabolic</td><td>Dr. Ndongo</td><td><span class="badge badge-danger">STAT</span></td><td>14 Jun 09:12</td><td><span class="badge badge-warn">Processing</span></td><td class="mono">SMP-94782</td><td><button class="btn btn-secondary btn-xs">View</button> <button class="btn btn-secondary btn-xs">Label</button></td></tr>
                <tr><td class="mono">ORD-94801</td><td><strong>Kamga Patrick</strong></td><td>Fasting Glucose, HbA1c</td><td>Dr. Abena</td><td><span class="badge badge-warn">Urgent</span></td><td>14 Jun 10:01</td><td><span class="badge badge-ok">Resulted</span></td><td class="mono">SMP-94801</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td class="mono">ORD-93001</td><td><strong>Mbarga Alain</strong></td><td>HbA1c, Lipid Panel</td><td>Dr. Ndongo</td><td><span class="badge badge-info">Routine</span></td><td>10 Jun 10:30</td><td><span class="badge badge-ok">Resulted</span></td><td class="mono">SMP-93001</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td class="mono">ORD-92548</td><td><strong>Ngono Suzanne</strong></td><td>Blood Culture × 2</td><td>Dr. Fopa</td><td><span class="badge badge-warn">Urgent</span></td><td>08 Jun 11:00</td><td><span class="badge badge-ok">Resulted</span></td><td class="mono">SMP-92548</td><td><button class="btn btn-secondary btn-xs">View</button></td></tr>
                <tr><td class="mono">ORD-94820</td><td><strong>Biya Jean-Pierre</strong></td><td>Malaria RDT, CBC</td><td>Dr. Abena</td><td><span class="badge badge-info">Routine</span></td><td>14 Jun 11:20</td><td><span class="badge badge-warn">Pending</span></td><td>—</td><td><button class="btn btn-primary btn-xs">Receive Sample</button></td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- SAMPLE COLLECTION -->
        <div class="portal-page" id="lab-page-samples">
          <div class="page-header"><div><h2>Sample Collection</h2></div><button class="btn btn-primary">Log Collection</button></div>
          <div class="panel">
            <table class="data-table">
              <thead><tr><th>Sample ID</th><th>Patient</th><th>Order #</th><th>Type</th><th>Collected By</th><th>Time</th><th>Quality</th><th>Status</th></tr></thead>
              <tbody>
                <tr><td class="mono">SMP-94782</td><td><strong>Fouda Marie Claire</strong></td><td class="mono">ORD-94782</td><td>Venous Blood</td><td>Tech Kouam</td><td>09:15</td><td><span class="badge badge-ok">Acceptable</span></td><td><span class="badge badge-warn">Processing</span></td></tr>
                <tr><td class="mono">SMP-94801</td><td><strong>Kamga Patrick</strong></td><td class="mono">ORD-94801</td><td>Venous Blood</td><td>Tech Mbemba</td><td>10:05</td><td><span class="badge badge-ok">Good</span></td><td><span class="badge badge-ok">Resulted</span></td></tr>
                <tr><td class="mono">SMP-93001</td><td><strong>Mbarga Alain</strong></td><td class="mono">ORD-93001</td><td>Venous Blood</td><td>Tech Kouam</td><td>10:32</td><td><span class="badge badge-ok">Good</span></td><td><span class="badge badge-ok">Resulted</span></td></tr>
              </tbody>
            </table>
          </div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Log Sample Collection</span></div><div class="panel-body">
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Patient HID (scan or type)</label><input class="form-control mono" placeholder="CM-HID-XXXX-XXXX-XXXX"></div>
              <div class="form-group"><label class="form-label">Order #</label><input class="form-control mono" placeholder="ORD-XXXXX"></div>
            </div>
            <div class="form-row cols-3">
              <div class="form-group"><label class="form-label">Specimen Type</label><select class="form-control"><option>Venous Blood</option><option>Urine</option><option>Stool</option><option>Swab</option></select></div>
              <div class="form-group"><label class="form-label">Collection Time</label><input class="form-control" type="time" value="10:42"></div>
              <div class="form-group"><label class="form-label">Quality Assessment</label><select class="form-control"><option>Good</option><option>Acceptable</option><option>Haemolysed</option><option>Insufficient</option></select></div>
            </div>
            <button class="btn btn-primary">Log Collection &amp; Print Label</button>
          </div></div>
        </div>
        <!-- RESULT ENTRY -->
        <div class="portal-page" id="lab-page-entry">
          <div class="page-header"><div><h2>Result Entry</h2></div></div>
          <div class="filter-bar">
            <input class="form-control mono" placeholder="Search order or sample ID..." style="width:220px">
            <button class="btn btn-primary btn-sm">Load Order</button>
          </div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Order ORD-94782 — CBC — Fouda Marie Claire</span></div><div class="panel-body">
            <table class="data-table">
              <thead><tr><th>Test Parameter</th><th>Value</th><th>Unit</th><th>Reference Range</th><th>Flag (auto)</th></tr></thead>
              <tbody>
                <tr><td>Hemoglobin</td><td><input class="form-control" style="width:80px" value="11.2"></td><td>g/dL</td><td>12.0 – 16.0</td><td><span class="badge badge-warn">L</span></td></tr>
                <tr><td>Hematocrit</td><td><input class="form-control" style="width:80px" value="34.1"></td><td>%</td><td>36 – 48</td><td><span class="badge badge-warn">L</span></td></tr>
                <tr><td>WBC</td><td><input class="form-control" style="width:80px" value="14.2"></td><td>×10³/μL</td><td>4.5 – 11.0</td><td><span class="badge badge-danger">H</span></td></tr>
                <tr><td>Platelets</td><td><input class="form-control" style="width:80px" value="298"></td><td>×10³/μL</td><td>150 – 400</td><td><span class="badge badge-ok">N</span></td></tr>
                <tr><td>Troponin I</td><td><input class="form-control" style="width:80px" value="4.82"></td><td>ng/mL</td><td>&lt;0.04</td><td><span class="badge badge-danger">CRITICAL</span></td></tr>
              </tbody>
            </table>
            <div style="margin-top:12px;display:flex;gap:8px">
              <button class="btn btn-secondary">Save Draft</button>
              <button class="btn btn-primary">Submit for Validation</button>
            </div>
          </div></div>
        </div>
        <!-- VALIDATION -->
        <div class="portal-page" id="lab-page-validation">
          <div class="page-header"><div><h2>Result Validation</h2></div></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Order #</th><th>Patient</th><th>Entered By</th><th>Tests</th><th>Critical Flags</th><th>Actions</th></tr></thead><tbody>
            <tr><td class="mono">ORD-94782</td><td><strong>Fouda Marie Claire</strong></td><td>Tech Kouam</td><td>Troponin, CBC</td><td><span class="badge badge-danger">2 Critical</span></td><td><button class="btn btn-primary btn-sm">Validate</button></td></tr>
            <tr><td class="mono">ORD-94801</td><td><strong>Kamga Patrick</strong></td><td>Tech Mbemba</td><td>Glucose, HbA1c</td><td><span class="badge badge-danger">1 Panic</span></td><td><button class="btn btn-primary btn-sm">Validate</button></td></tr>
          </tbody></table></div>
          <div class="panel" style="border-left:3px solid var(--warn)"><div class="panel-hd"><span class="panel-title">Validate — ORD-94782 · Fouda Marie Claire</span></div><div class="panel-body">
            <div class="alert alert-danger"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg><div>Troponin I = 4.82 ng/mL is a CRITICAL value. Physician notification mandatory before release.</div></div>
            <div class="form-group"><label class="form-label">Validator Notes</label><textarea class="form-control" placeholder="QC check passed. All instruments calibrated. Delta check reviewed..."></textarea></div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px"><input type="checkbox" style="accent-color:var(--b)"><span style="font-size:13px">Critical values have been communicated to Dr. Ndongo Emmanuel at 10:14 by phone</span></div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px"><input type="checkbox" style="accent-color:var(--b)"><span style="font-size:13px">I, Tech Kouam, validate these results as correct and authorize release to patient record</span></div>
            <div class="toggle-wrap"><div class="toggle-info"><h4>Release to Patient Portal</h4><p>Patient will be notified via app and SMS</p></div><div class="toggle on"></div></div>
            <div style="margin-top:12px"><button class="btn btn-ok">Validate &amp; Release Results</button></div>
          </div></div>
        </div>
        <!-- LAB REPORTS -->
        <div class="portal-page" id="lab-page-reports">
          <div class="page-header"><div><h2>Lab Reports</h2></div></div>
          <div class="filter-bar">
            <input class="form-control" type="date" style="width:150px">
            <input class="form-control" placeholder="Patient name or HID..." style="width:200px">
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel"><table class="data-table"><thead><tr><th>Report #</th><th>Patient</th><th>Tests</th><th>Date</th><th>Validated By</th><th>Released</th><th>Actions</th></tr></thead><tbody>
            <tr><td class="mono">RPT-94001</td><td><strong>Mbarga Alain</strong></td><td>CBC, HbA1c, Lipid</td><td>10 Jun 2025</td><td>Sr. Biologist Esso</td><td><span class="badge badge-ok">Released</span></td><td><button class="btn btn-secondary btn-xs">PDF</button> <button class="btn btn-secondary btn-xs">Share</button></td></tr>
            <tr><td class="mono">RPT-93842</td><td><strong>Ngono Suzanne</strong></td><td>Blood Culture</td><td>08 Jun 2025</td><td>Sr. Biologist Esso</td><td><span class="badge badge-ok">Released</span></td><td><button class="btn btn-secondary btn-xs">PDF</button> <button class="btn btn-secondary btn-xs">Share</button></td></tr>
            <tr><td class="mono">RPT-94782</td><td><strong>Fouda Marie Claire</strong></td><td>Troponin, CBC</td><td>14 Jun 2025</td><td>Tech Kouam</td><td><span class="badge badge-warn">Pending</span></td><td><button class="btn btn-secondary btn-xs" disabled>PDF</button></td></tr>
          </tbody></table></div>
        </div>
        <!-- QC -->
        <div class="portal-page" id="lab-page-qc">
          <div class="page-header"><div><h2>Quality Control</h2></div><button class="btn btn-primary">Log QC Run</button></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">QC Run Log</span></div><table class="data-table"><thead><tr><th>Run Date</th><th>Instrument</th><th>Control Lot</th><th>Level</th><th>Result</th><th>Status</th></tr></thead><tbody>
            <tr><td>14 Jun 2025</td><td>Sysmex XN-1000</td><td class="mono">LOT-2024-0421</td><td>Level 2</td><td>Within ±2SD</td><td><span class="badge badge-ok">Pass</span></td></tr>
            <tr><td>14 Jun 2025</td><td>Roche Cobas</td><td class="mono">LOT-2024-0389</td><td>Level 1</td><td>Within ±2SD</td><td><span class="badge badge-ok">Pass</span></td></tr>
            <tr><td>13 Jun 2025</td><td>Sysmex XN-1000</td><td class="mono">LOT-2024-0421</td><td>Level 1</td><td>+2.4 SD (warning)</td><td><span class="badge badge-warn">Warning</span></td></tr>
          </tbody></table></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Levy-Jennings Chart — Hemoglobin Control</span></div><div class="panel-body">
            <div class="map-placeholder" style="height:120px"><span>Levy-Jennings Chart — Hemoglobin Control Level 2</span></div>
            <div class="legend" style="margin-top:8px"><div class="legend-item"><div class="legend-dot" style="background:var(--b)"></div>Observed</div><div class="legend-item"><div class="legend-dot" style="background:var(--ok)"></div>Mean</div><div class="legend-item"><div class="legend-dot" style="background:var(--warn)"></div>±2SD</div><div class="legend-item"><div class="legend-dot" style="background:var(--danger)"></div>±3SD</div></div>
          </div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== PHARMACY PORTAL ===== -->
<div class="portal-pane" id="portal-pharmacy">
  <div class="portal-wrap">
    <div class="portal-sidebar">
      <div class="sb-brand"><div class="sb-logo"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18"/><path d="M9 12h6"/><path d="M12 9v6"/></svg></div><div><div class="sb-name">OpesCare</div><div class="sb-sub">Pharmacy Portal</div></div></div>
      <div class="sb-rb"><div class="role-badge">Pharmacist</div></div>
      <nav class="sb-nav">
        <div class="sb-sec">Overview</div>
        <div class="sb-link active" id="pha-nav-dashboard" onclick="goPage('pharmacy','dashboard')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard</div>
        <div class="sb-sec">Dispensing</div>
        <div class="sb-link" id="pha-nav-pending" onclick="goPage('pharmacy','pending')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/></svg>Pending Rx</div>
        <div class="sb-link" id="pha-nav-dispense" onclick="goPage('pharmacy','dispense')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>Dispense</div>
        <div class="sb-link" id="pha-nav-history" onclick="goPage('pharmacy','history')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>History</div>
        <div class="sb-sec">Inventory</div>
        <div class="sb-link" id="pha-nav-stock" onclick="goPage('pharmacy','stock')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>Stock</div>
        <div class="sb-link" id="pha-nav-addstock" onclick="goPage('pharmacy','addstock')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>Add Stock</div>
        <div class="sb-link" id="pha-nav-expiry" onclick="goPage('pharmacy','expiry')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>Expiry Alert</div>
        <div class="sb-link" id="pha-nav-reorder" onclick="goPage('pharmacy','reorder')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,3 21,3 21,9"/><path d="M21 3L9 15l-6-6"/></svg>Reorder</div>
        <div class="sb-sec">Reports</div>
        <div class="sb-link" id="pha-nav-daily" onclick="goPage('pharmacy','daily')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>Daily Summary</div>
        <div class="sb-link" id="pha-nav-movement" onclick="goPage('pharmacy','movement')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/></svg>Stock Movement</div>
      </nav>
      <div class="sb-foot"><div class="sb-user"><div class="sb-av">PN</div><div><div class="sb-uname">Pharmacien Nkeng</div><div class="sb-urole">Pharmacie Centrale</div></div></div></div>
    </div>
    <div class="portal-main">
      <div class="portal-topbar"><span class="tb-crumb">Pharmacy</span><span class="tb-sep">/</span><span class="tb-page" id="pha-breadcrumb">Dashboard</span><div class="tb-right"><div class="tb-av">PN</div><span class="tb-uname">Nkeng</span></div></div>
      <div class="portal-content">
        <!-- PHARMACY DASHBOARD -->
        <div class="portal-page active" id="pha-page-dashboard">
          <div class="stat-grid cols-4">
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/></svg></div><div class="sc-val">14</div><div class="sc-lbl">Pending Rx</div></div>
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/></svg></div><div class="sc-val">87</div><div class="sc-lbl">Dispensed Today</div></div>
            <div class="stat-card danger"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg></div><div class="sc-val">5</div><div class="sc-lbl">Low Stock Alerts</div></div>
            <div class="stat-card warn"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/></svg></div><div class="sc-val">8</div><div class="sc-lbl">Expiring in 30d</div></div>
          </div>
          <div class="two-col">
            <div class="panel"><div class="panel-hd"><span class="panel-title">Pending Prescriptions (Urgent First)</span></div><table class="data-table"><thead><tr><th>Rx #</th><th>Patient</th><th>Drugs</th><th>Priority</th></tr></thead><tbody>
              <tr><td class="mono">RX-2025-0892</td><td><strong>Fouda Marie Claire</strong></td><td>Aspirin 300mg, Nitroglycerine</td><td><span class="badge badge-danger">STAT</span></td></tr>
              <tr><td class="mono">RX-2025-0891</td><td><strong>Mbarga Alain</strong></td><td>Amlodipine 5mg</td><td><span class="badge badge-info">Routine</span></td></tr>
              <tr><td class="mono">RX-2025-0890</td><td><strong>Kamga Patrick</strong></td><td>Metformine 850mg</td><td><span class="badge badge-info">Routine</span></td></tr>
            </tbody></table></div>
            <div class="panel"><div class="panel-hd"><span class="panel-title">Stock Alerts</span></div><div class="panel-body" style="padding:0">
              <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);font-size:12px"><span class="badge drug-out" style="margin-right:8px">OUT</span>Artémether-Luméfantrine 80/480mg — 0 units</div>
              <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);font-size:12px"><span class="badge drug-low" style="margin-right:8px">LOW</span>Amoxicillin 500mg — 12 boxes (min: 50)</div>
              <div style="padding:10px 14px;border-bottom:1px solid var(--bdr);font-size:12px"><span class="badge drug-expiring" style="margin-right:8px">EXPIRING</span>Cotrimoxazole 480mg — Exp: 01 Jul 2025</div>
              <div style="padding:10px 14px;font-size:12px"><span class="badge drug-low" style="margin-right:8px">LOW</span>Paracétamol 500mg — 24 boxes (min: 100)</div>
            </div></div>
          </div>
        </div>
        <!-- PENDING RX -->
        <div class="portal-page" id="pha-page-pending">
          <div class="page-header"><div><h2>Pending Prescriptions</h2></div></div>
          <div class="filter-bar">
            <select class="form-control"><option>All Priorities</option><option>STAT</option><option>Routine</option></select>
            <input class="form-control" placeholder="Patient name or HID...">
            <button class="btn btn-secondary btn-sm">Filter</button>
          </div>
          <div class="panel"><table class="data-table"><thead><tr><th>Rx #</th><th>Patient</th><th>Medications Count</th><th>Doctor</th><th>Date</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            <tr><td class="mono">RX-2025-0892</td><td><strong>Fouda Marie Claire</strong></td><td>2 medications</td><td>Dr. Ndongo</td><td>14 Jun 2025</td><td><span class="badge badge-danger">STAT</span></td><td><span class="badge badge-warn">Pending</span></td><td><button class="btn btn-primary btn-sm">Start Dispense</button></td></tr>
            <tr><td class="mono">RX-2025-0891</td><td><strong>Mbarga Alain</strong></td><td>1 medication</td><td>Dr. Ndongo</td><td>10 Jun 2025</td><td><span class="badge badge-info">Routine</span></td><td><span class="badge badge-warn">Pending</span></td><td><button class="btn btn-primary btn-sm">Start Dispense</button></td></tr>
            <tr><td class="mono">RX-2025-0890</td><td><strong>Kamga Patrick</strong></td><td>2 medications</td><td>Dr. Abena</td><td>12 Jun 2025</td><td><span class="badge badge-info">Routine</span></td><td><span class="badge badge-warn">Pending</span></td><td><button class="btn btn-primary btn-sm">Start Dispense</button></td></tr>
          </tbody></table></div>
        </div>
        <!-- DISPENSE -->
        <div class="portal-page" id="pha-page-dispense">
          <div class="page-header"><div><h2>Dispense</h2></div></div>
          <div class="filter-bar"><input class="form-control mono" placeholder="Patient HID to lookup prescriptions..." style="width:280px"><button class="btn btn-primary btn-sm">Search</button></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">Rx RX-2025-0892 — Fouda Marie Claire — STAT</span></div><div class="panel-body">
            <div style="background:var(--dangerl);border:1px solid rgba(185,28,28,.2);border-radius:var(--rad);padding:12px;margin-bottom:14px;font-size:13px;color:var(--danger)">⚠ ALLERGY: Penicillin, Sulfa Drugs — Check all dispensed drugs</div>
            <table class="data-table">
              <thead><tr><th>Drug</th><th>Dosage</th><th>Qty to Dispense</th><th>Batch #</th><th>Lot #</th><th>Expiry</th><th>Confirmed</th></tr></thead>
              <tbody>
                <tr><td><strong>Aspirin</strong></td><td>300mg</td><td><input class="form-control" style="width:70px" value="1"></td><td class="mono"><input class="form-control" style="width:100px" value="ASP-2024-441"></td><td class="mono">LOT-4412</td><td>Dec 2026</td><td><input type="checkbox" style="accent-color:var(--b)"></td></tr>
                <tr><td><strong>Nitroglycerine</strong></td><td>0.5mg SL</td><td><input class="form-control" style="width:70px" value="10"></td><td class="mono"><input class="form-control" style="width:100px" value="NTG-2025-001"></td><td class="mono">LOT-0011</td><td>Aug 2025</td><td><input type="checkbox" style="accent-color:var(--b)"></td></tr>
              </tbody>
            </table>
            <div class="form-group" style="margin-top:12px"><label class="form-label">Counselling Notes</label><textarea class="form-control" placeholder="Aspirin: swallow whole. Nitroglycerine: place under tongue, do not swallow, max 3 doses 5 min apart..."></textarea></div>
            <div style="display:flex;gap:8px;margin-top:12px"><button class="btn btn-ok">Confirm Dispense</button><button class="btn btn-secondary">Print Receipt</button></div>
          </div></div>
        </div>
        <!-- HISTORY -->
        <div class="portal-page" id="pha-page-history">
          <div class="page-header"><div><h2>Dispensing History</h2></div></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Rx #</th><th>Patient</th><th>Medications</th><th>Dispensed By</th><th>Date</th><th>Amount (XAF)</th><th>Payment</th></tr></thead><tbody>
            <tr><td class="mono">RX-2025-0889</td><td><strong>Ngono Suzanne</strong></td><td>Amoxicillin 500mg ×21, Paracétamol ×10</td><td>Ph. Nkeng</td><td>08 Jun 2025</td><td>8,500</td><td>MTN MoMo</td></tr>
            <tr><td class="mono">RX-2025-0885</td><td><strong>Biya Jean-Pierre</strong></td><td>Artémether-Luméfantrine ×6</td><td>Ph. Nkeng</td><td>05 Jun 2025</td><td>12,000</td><td>Orange Money</td></tr>
            <tr><td class="mono">RX-2025-0878</td><td><strong>Mbarga Alain</strong></td><td>Amlodipine 5mg ×30</td><td>Ph. Nkeng</td><td>01 Jun 2025</td><td>7,500</td><td>Cash</td></tr>
          </tbody></table></div>
        </div>
        <!-- STOCK -->
        <div class="portal-page" id="pha-page-stock">
          <div class="page-header"><div><h2>Stock</h2></div><button class="btn btn-primary" onclick="goPage('pharmacy','addstock')">Add Stock</button></div>
          <div class="filter-bar"><input class="form-control" placeholder="Search drug..." style="width:200px"><select class="form-control"><option>All Categories</option><option>Antibiotic</option><option>Cardiovascular</option><option>Antidiabetic</option><option>Antimalarial</option></select><select class="form-control"><option>All Statuses</option><option>In Stock</option><option>Low</option><option>Out</option><option>Expiring</option></select><button class="btn btn-secondary btn-sm">Filter</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Drug Name</th><th>Generic</th><th>Category</th><th>Stock Qty</th><th>Unit</th><th>Batch #</th><th>Expiry</th><th>Reorder Level</th><th>Status</th></tr></thead><tbody>
            <tr><td><strong>Amoxicillin 500mg</strong></td><td>Amoxicillin</td><td>Antibiotic</td><td>12</td><td>Box (12 caps)</td><td class="mono">AMX-2024-88</td><td>Jun 2026</td><td>50</td><td><span class="drug-badge drug-low">Low</span></td></tr>
            <tr><td><strong>Metformine 850mg</strong></td><td>Metformin</td><td>Antidiabetic</td><td>142</td><td>Box (30 tabs)</td><td class="mono">MET-2025-04</td><td>Mar 2027</td><td>30</td><td><span class="drug-badge drug-instock">In Stock</span></td></tr>
            <tr><td><strong>Amlodipine 5mg</strong></td><td>Amlodipine</td><td>Cardiovascular</td><td>88</td><td>Box (30 tabs)</td><td class="mono">AML-2025-11</td><td>Sep 2026</td><td>20</td><td><span class="drug-badge drug-instock">In Stock</span></td></tr>
            <tr><td><strong>Artémether-Luméfantrine</strong></td><td>AL 80/480mg</td><td>Antimalarial</td><td>0</td><td>Pack (6 tabs)</td><td class="mono">—</td><td>—</td><td>100</td><td><span class="drug-badge drug-out">Out of Stock</span></td></tr>
            <tr><td><strong>Cotrimoxazole 480mg</strong></td><td>Co-trimoxazole</td><td>Antibiotic</td><td>45</td><td>Box (20 tabs)</td><td class="mono">CTX-2024-22</td><td>01 Jul 2025</td><td>20</td><td><span class="drug-badge drug-expiring">Expiring</span></td></tr>
            <tr><td><strong>Paracétamol 500mg</strong></td><td>Paracetamol</td><td>Analgesic</td><td>24</td><td>Box (20 tabs)</td><td class="mono">PCM-2025-01</td><td>Dec 2026</td><td>100</td><td><span class="drug-badge drug-low">Low</span></td></tr>
          </tbody></table></div>
        </div>
        <!-- ADD STOCK -->
        <div class="portal-page" id="pha-page-addstock">
          <div class="page-header"><div><h2>Add Stock</h2></div></div>
          <div class="panel"><div class="panel-hd"><span class="panel-title">New Stock Entry</span></div><div class="panel-body">
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Drug Name <span>*</span></label><select class="form-control"><option>Amoxicillin 500mg</option><option>Metformine 850mg</option><option>Amlodipine 5mg</option><option>Artémether-Luméfantrine</option><option>+ Add New Drug</option></select></div>
              <div class="form-group"><label class="form-label">Generic Name</label><input class="form-control" placeholder="INN name" value="Amoxicillin"></div>
            </div>
            <div class="form-row cols-3">
              <div class="form-group"><label class="form-label">Category</label><select class="form-control"><option>Antibiotic</option><option>Antimalarial</option><option>Antidiabetic</option><option>Cardiovascular</option><option>Analgesic</option></select></div>
              <div class="form-group"><label class="form-label">Batch # <span>*</span></label><input class="form-control mono" placeholder="e.g. AMX-2025-99"></div>
              <div class="form-group"><label class="form-label">Lot # <span>*</span></label><input class="form-control mono" placeholder="e.g. LOT-9901"></div>
            </div>
            <div class="form-row cols-3">
              <div class="form-group"><label class="form-label">Quantity <span>*</span></label><input class="form-control" type="number" placeholder="Number of units/boxes"></div>
              <div class="form-group"><label class="form-label">Unit Cost (XAF) <span>*</span></label><input class="form-control" placeholder="e.g. 2,500"></div>
              <div class="form-group"><label class="form-label">Expiry Date <span>*</span></label><input class="form-control" type="date"></div>
            </div>
            <div class="form-row cols-2">
              <div class="form-group"><label class="form-label">Supplier</label><input class="form-control" placeholder="Supplier / distributor name"></div>
              <div class="form-group"><label class="form-label">Storage Conditions</label><select class="form-control"><option>Room temperature</option><option>Refrigerated (2-8°C)</option><option>Frozen</option><option>Cool &amp; dark</option></select></div>
            </div>
            <div class="form-group"><label class="form-label">Received By</label><input class="form-control" value="Ph. Nkeng"></div>
            <button class="btn btn-primary">Add to Stock</button>
          </div></div>
        </div>
        <!-- EXPIRY -->
        <div class="portal-page" id="pha-page-expiry">
          <div class="page-header"><div><h2>Expiry Alert</h2><p>Drugs expiring within 90 days</p></div></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Drug</th><th>Batch #</th><th>Qty</th><th>Expiry Date</th><th>Days Left</th><th>Actions</th></tr></thead><tbody>
            <tr><td><strong>Cotrimoxazole 480mg</strong></td><td class="mono">CTX-2024-22</td><td>45 boxes</td><td style="color:var(--danger);font-weight:700">01 Jul 2025</td><td><span class="badge badge-danger">17 days</span></td><td><button class="btn btn-danger btn-xs">Mark Disposal</button> <button class="btn btn-secondary btn-xs">Return Supplier</button></td></tr>
            <tr><td><strong>Nitroglycerine 0.5mg SL</strong></td><td class="mono">NTG-2025-001</td><td>20 packs</td><td style="color:var(--warn);font-weight:600">31 Aug 2025</td><td><span class="badge badge-warn">78 days</span></td><td><button class="btn btn-warn btn-xs" style="background:var(--warnl);color:var(--warn);border:1px solid rgba(180,83,9,.2)">Mark Disposal</button> <button class="btn btn-secondary btn-xs">Return Supplier</button></td></tr>
            <tr><td><strong>Vaccine Tetanus (DT)</strong></td><td class="mono">DT-2024-CM</td><td>10 vials</td><td style="color:var(--warn);font-weight:600">15 Sep 2025</td><td><span class="badge badge-warn">93 days</span></td><td><button class="btn btn-warn btn-xs" style="background:var(--warnl);color:var(--warn);border:1px solid rgba(180,83,9,.2)">Mark Disposal</button> <button class="btn btn-secondary btn-xs">Return Supplier</button></td></tr>
          </tbody></table></div>
        </div>
        <!-- REORDER -->
        <div class="portal-page" id="pha-page-reorder">
          <div class="page-header"><div><h2>Reorder List</h2><p>Drugs below reorder level</p></div><button class="btn btn-primary">Generate Purchase Order</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Drug</th><th>Current Stock</th><th>Reorder Level</th><th>Suggested Qty</th><th>Supplier</th><th>Est. Cost (XAF)</th></tr></thead><tbody>
            <tr><td><strong>Artémether-Luméfantrine 80/480mg</strong></td><td style="color:var(--danger);font-weight:700">0</td><td>100 packs</td><td><input class="form-control" style="width:80px" value="200"></td><td>PharmaCM Distrib.</td><td>240,000</td></tr>
            <tr><td><strong>Amoxicillin 500mg</strong></td><td style="color:var(--warn);font-weight:700">12 boxes</td><td>50 boxes</td><td><input class="form-control" style="width:80px" value="100"></td><td>PharmaCM Distrib.</td><td>350,000</td></tr>
            <tr><td><strong>Paracétamol 500mg</strong></td><td style="color:var(--warn);font-weight:700">24 boxes</td><td>100 boxes</td><td><input class="form-control" style="width:80px" value="200"></td><td>LABOREX CM</td><td>80,000</td></tr>
          </tbody></table></div>
        </div>
        <!-- DAILY SUMMARY -->
        <div class="portal-page" id="pha-page-daily">
          <div class="page-header"><div><h2>Daily Summary</h2><p>14 Jun 2025</p></div><button class="btn btn-secondary btn-sm">Export PDF</button></div>
          <div class="stat-grid cols-3">
            <div class="stat-card ok"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/></svg></div><div class="sc-val">87</div><div class="sc-lbl">Total Dispensed</div></div>
            <div class="stat-card teal"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/></svg></div><div class="sc-val">1,245,000</div><div class="sc-lbl">Total Revenue (XAF)</div></div>
            <div class="stat-card"><div class="sc-ic"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/></svg></div><div class="sc-val">14.3</div><div class="sc-lbl">Avg Bill (XAF ×1000)</div></div>
          </div>
          <div class="two-col">
            <div class="panel"><div class="panel-hd"><span class="panel-title">Top 5 Drugs Dispensed</span></div><div class="panel-body">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span>Paracétamol 500mg</span><div style="display:flex;align-items:center;gap:8px"><div class="progress" style="width:100px"><div class="progress-fill" style="width:90%"></div></div><strong>22</strong></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span>Amoxicillin 500mg</span><div style="display:flex;align-items:center;gap:8px"><div class="progress" style="width:100px"><div class="progress-fill" style="width:75%"></div></div><strong>18</strong></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span>Metformine 850mg</span><div style="display:flex;align-items:center;gap:8px"><div class="progress" style="width:100px"><div class="progress-fill" style="width:60%"></div></div><strong>15</strong></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span>Artémether-Luméfantrine</span><div style="display:flex;align-items:center;gap:8px"><div class="progress" style="width:100px"><div class="progress-fill" style="width:50%"></div></div><strong>12</strong></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center"><span>Amlodipine 5mg</span><div style="display:flex;align-items:center;gap:8px"><div class="progress" style="width:100px"><div class="progress-fill" style="width:40%"></div></div><strong>10</strong></div></div>
            </div></div>
            <div class="panel"><div class="panel-hd"><span class="panel-title">Payment Method Breakdown</span></div><div class="panel-body">
              <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--bdr)"><span>MTN MoMo</span><div style="text-align:right"><div style="font-size:16px;font-weight:700">620,000 XAF</div><div style="font-size:12px;color:var(--muted)">42 payments (49.8%)</div></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--bdr)"><span>Orange Money</span><div style="text-align:right"><div style="font-size:16px;font-weight:700">380,500 XAF</div><div style="font-size:12px;color:var(--muted)">28 payments (30.5%)</div></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0"><span>Cash</span><div style="text-align:right"><div style="font-size:16px;font-weight:700">244,500 XAF</div><div style="font-size:12px;color:var(--muted)">17 payments (19.7%)</div></div></div>
              <div style="margin-top:12px"><div style="font-size:12px;color:var(--muted);margin-bottom:4px">Dispensing by Hour</div>
                <div class="bar-chart" style="height:80px">
                  <div class="bar-wrap"><div class="bar" style="height:30%"></div><div class="bar-lbl">8h</div></div>
                  <div class="bar-wrap"><div class="bar" style="height:60%"></div><div class="bar-lbl">9h</div></div>
                  <div class="bar-wrap"><div class="bar" style="height:100%"></div><div class="bar-lbl">10h</div></div>
                  <div class="bar-wrap"><div class="bar" style="height:80%"></div><div class="bar-lbl">11h</div></div>
                  <div class="bar-wrap"><div class="bar" style="height:50%"></div><div class="bar-lbl">12h</div></div>
                  <div class="bar-wrap"><div class="bar" style="height:70%"></div><div class="bar-lbl">14h</div></div>
                  <div class="bar-wrap"><div class="bar" style="height:90%"></div><div class="bar-lbl">15h</div></div>
                  <div class="bar-wrap"><div class="bar" style="height:40%"></div><div class="bar-lbl">16h</div></div>
                </div>
              </div>
            </div></div>
          </div>
        </div>
        <!-- STOCK MOVEMENT -->
        <div class="portal-page" id="pha-page-movement">
          <div class="page-header"><div><h2>Stock Movement</h2></div><button class="btn btn-secondary btn-sm">Export</button></div>
          <div class="filter-bar"><input class="form-control" type="date" style="width:150px"><input class="form-control" type="date" style="width:150px"><button class="btn btn-secondary btn-sm">Apply</button></div>
          <div class="panel"><table class="data-table"><thead><tr><th>Drug</th><th>Type</th><th>Qty</th><th>Reference</th><th>By</th><th>Date</th></tr></thead><tbody>
            <tr><td><strong>Amoxicillin 500mg</strong></td><td><span class="badge badge-ok">IN</span></td><td>+50 boxes</td><td class="mono">PO-2025-0441</td><td>Ph. Nkeng</td><td>01 Jun 2025</td></tr>
            <tr><td><strong>Amoxicillin 500mg</strong></td><td><span class="badge badge-danger">OUT</span></td><td>-38 boxes</td><td class="mono">RX-0889, 0884...</td><td>Ph. Nkeng</td><td>01-08 Jun 2025</td></tr>
            <tr><td><strong>Metformine 850mg</strong></td><td><span class="badge badge-ok">IN</span></td><td>+200 boxes</td><td class="mono">PO-2025-0390</td><td>Ph. Nkeng</td><td>15 May 2025</td></tr>
            <tr><td><strong>Artémether-Luméfantrine</strong></td><td><span class="badge badge-danger">OUT</span></td><td>-200 packs</td><td class="mono">RX-0780..0885</td><td>Ph. Nkeng</td><td>Apr-May 2025</td></tr>
            <tr><td><strong>Paracétamol 500mg</strong></td><td><span class="badge badge-info">ADJUST</span></td><td>-6 boxes</td><td>Damaged (wet)</td><td>Ph. Nkeng</td><td>10 Jun 2025</td></tr>
          </tbody></table></div>
        </div>
      </div>
    </div>
  </div>
</div>
'''

with open(r'C:\laragon\www\opescare\apps\api-laravel\public\theme-preview\parts\insurance_lab_pharmacy.html', 'w', encoding='utf-8') as f:
    f.write(content)
print("Insurance+Lab+Pharmacy written:", len(content), "chars")
